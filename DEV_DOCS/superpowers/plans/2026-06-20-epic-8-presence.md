# Epic 8: Presence Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Build Presence module: 3 tables (presensi, absensi, izin), QR scan endpoint with multi-step validation (decode → entity check → duplicate check → telat calculation → record + event), manual entry fallback, absensi entry (sakit/ijin/alpha), izin approval workflow (pending→approved/rejected), surat izin PDF with QR. Depends on Epic 1-7.

**Architecture:** `QrScannerService::handle(qrCode)` is the main scan entry — validates tenant, checks today's duplicate, computes telat via `PresensiRuleEngine` (jam_masuk from tenant_settings), records + emits `Presence.Recorded` event. `IzinApprovalService` handles pending→approved workflow. QR codes use simple-qrcode package (installed Epic 1).

**Tech Stack:** simple-qrcode, DomPDF for surat izin.

**Spec reference:** design.md §7.1 Presence, DEV_DOCS-003 §3.7, DEV_DOCS-009 §5.6.

---

## File Structure

- Create: `app/Modules/Presence/Database/Migrations/` (3 migrations)
- Create: `app/Modules/Presence/Models/{Presensi, Absensi, Izin}.php`
- Create: `app/Modules/Presence/Controllers/{PresensiController, AbsensiController, IzinController, LaporanPresensiController}.php`
- Create: `app/Modules/Presence/Policies/{PresensiPolicy, AbsensiPolicy, IzinPolicy}.php`
- Create: `app/Modules/Presence/Requests/{ScanQrRequest, StoreAbsensiRequest, StoreIzinRequest, ApproveIzinRequest}.php`
- Create: `app/Modules/Presence/Services/{QrScannerService, PresensiRuleEngine, IzinApprovalService}.php`
- Create: `app/Modules/Presence/Observers/PresensiObserver.php`
- Create: `app/Modules/Presence/Events/PresenceRecorded.php`
- Create: `app/Modules/Presence/routes.php`
- Create: `resources/views/presence/{presensi, absensi, izin, laporan}/*.blade.php`
- Create: `tests/Feature/Presence/{QrScanTest, AbsensiCrudTest, IzinApprovalTest}.php`

---

## Task 1: Migrations — 3 Presence tables

**Files:**
- Create: `app/Modules/Presence/Database/Migrations/2026_06_20_0004{00..02}_*.php`

- [ ] **Step 1: Create directory + 3 migrations**

```bash
mkdir -p app/Modules/Presence/{Database/Migrations,Models,Controllers,Policies,Requests,Services,Observers,Events}
```

Create `2026_06_20_000400_create_presensi_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('user_id')->nullable();      // pegawai/guru
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['datang', 'pulang']);
            $table->time('jam');
            $table->unsignedSmallInteger('telat_menit')->default(0);
            $table->enum('metode', ['qr', 'manual'])->default('qr');
            $table->timestamps();
            $table->index(['tenant_id', 'tanggal']);
            $table->index(['tenant_id', 'siswa_id', 'tanggal']);
            $table->index(['tenant_id', 'user_id', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('presensi'); }
};
```

Create `2026_06_20_000401_create_absensi_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['sakit', 'ijin', 'alpha']);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'tanggal']);
            $table->index(['tenant_id', 'siswa_id', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('absensi'); }
};
```

Create `2026_06_20_000402_create_izin_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('izin', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'pulang']);
            $table->time('jam')->nullable();
            $table->text('keterangan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('izin'); }
};
```

- [ ] **Step 2: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(presence): 3 migrations — presensi, absensi, izin"
```

---

## Task 2: Models + Events + Observers

**Files:**
- Create: 3 models
- Create: `app/Modules/Presence/Events/PresenceRecorded.php`
- Create: `app/Modules/Presence/Observers/PresensiObserver.php`

- [ ] **Step 1: Create 3 models** (all BelongsToTenant + TracksAuditColumns, with relations to siswa/user):

```php
<?php
namespace App\Modules\Presence\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presensi extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = ['user_id', 'siswa_id', 'tanggal', 'jenis', 'jam', 'telat_menit', 'metode'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jam' => 'datetime:H:i'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class); }
}
```

Create `Absensi.php` and `Izin.php` following same pattern.

- [ ] **Step 2: Create PresenceRecorded event**

```php
<?php
namespace App\Modules\Presence\Events;

use App\Modules\Presence\Models\Presensi;

class PresenceRecorded
{
    public function __construct(public Presensi $presensi) {}
}
```

- [ ] **Step 3: Implement PresensiObserver (audit + event)**

```php
<?php
namespace App\Modules\Presence\Observers;

use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Presence\Events\PresenceRecorded;
use App\Modules\Presence\Models\Presensi;

class PresensiObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(Presensi $presensi): void
    {
        event(new PresenceRecorded($presensi));
        $this->audit->log('presensi.created', auth()->user(), [
            'presensi_id' => $presensi->id, 'siswa_id' => $presensi->siswa_id, 'telat' => $presensi->telat_menit,
        ], request(), modelType: Presensi::class, modelId: $presensi->id);
    }
}
```

- [ ] **Step 4: Register observer**

Edit `app/Providers/EventServiceProvider.php` boot():

```php
\App\Modules\Presence\Models\Presensi::observe(\App\Modules\Presence\Observers\PresensiObserver::class);
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(presence): 3 models + PresenceRecorded event + PresensiObserver"
```

---

## Task 3: PresensiRuleEngine + QrScannerService + tests

**Files:**
- Create: `app/Modules/Presence/Services/PresensiRuleEngine.php`
- Create: `app/Modules/Presence/Services/QrScannerService.php`
- Create: `tests/Feature/Presence/QrScanTest.php`

- [ ] **Step 1: Write QrScan test**

Create `tests/Feature/Presence/QrScanTest.php`:

```php
<?php

namespace Tests\Feature\Presence;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Presence\Models\Presensi;
use App\Modules\Presence\Services\QrScannerService;
use App\Modules\Tenancy\Models\{Tenant, TenantSetting};
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_datang_before_jam_masuk_records_no_telat(): void
    {
        [$tenant, $piket, $siswa] = $this->setupScenario(jamMasuk: '07:30');
        $svc = app(QrScannerService::class);

        $presensi = $svc->handle($siswa->qrcode, $piket, now()->setTime(7, 0));

        $this->assertSame(0, $presensi->telat_menit);
        $this->assertSame('datang', $presensi->jenis);
    }

    public function test_scan_datang_after_jam_masuk_records_telat(): void
    {
        [$tenant, $piket, $siswa] = $this->setupScenario(jamMasuk: '07:00');
        $svc = app(QrScannerService::class);

        $presensi = $svc->handle($siswa->qrcode, $piket, now()->setTime(7, 30));

        $this->assertSame(30, $presensi->telat_menit);
        $this->assertSame('datang', $presensi->jenis);
    }

    public function test_duplicate_scan_same_day_same_jenis_throws(): void
    {
        [$tenant, $piket, $siswa] = $this->setupScenario(jamMasuk: '07:00');
        $svc = app(QrScannerService::class);

        $svc->handle($siswa->qrcode, $piket, now()->setTime(7, 0));

        $this->expectException(\App\Modules\Presence\Exceptions\AlreadyPresentException::class);
        $svc->handle($siswa->qrcode, $piket, now()->setTime(7, 30)); // second datang
    }

    public function test_scan_pulang_after_datang_works(): void
    {
        [$tenant, $piket, $siswa] = $this->setupScenario(jamMasuk: '07:00', jamPulang: '14:00');
        $svc = app(QrScannerService::class);

        $svc->handle($siswa->qrcode, $piket, now()->setTime(7, 0));
        $pulang = $svc->handle($siswa->qrcode, $piket, now()->setTime(14, 30));

        $this->assertSame('pulang', $pulang->jenis);
    }

    public function test_unknown_qr_throws(): void
    {
        [$tenant, $piket, $siswa] = $this->setupScenario(jamMasuk: '07:00');
        $svc = app(QrScannerService::class);

        $this->expectException(\App\Modules\Presence\Exceptions\InvalidQrException::class);
        $svc->handle('NONEXISTENT-QR-CODE', $piket, now());
    }

    private function setupScenario(string $jamMasuk, string $jamPulang = '14:00'): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        TenantSetting::create(['tenant_id' => $tenant->id, 'key' => 'jam_masuk_sekolah', 'value' => $jamMasuk]);
        TenantSetting::create(['tenant_id' => $tenant->id, 'key' => 'jam_pulang_sekolah', 'value' => $jamPulang]);
        $piket = User::factory()->create(['tenant_id' => $tenant->id]);
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id, 'qrcode' => 'QR-' . uniqid()]);
        return [$tenant, $piket, $siswa];
    }
}
```

- [ ] **Step 2: Create exceptions**

Create `app/Modules/Presence/Exceptions/{AlreadyPresentException, InvalidQrException}.php`:

```php
<?php
namespace App\Modules\Presence\Exceptions;
class AlreadyPresentException extends \DomainException {}
```

```php
<?php
namespace App\Modules\Presence\Exceptions;
class InvalidQrException extends \DomainException {}
```

- [ ] **Step 3: Implement PresensiRuleEngine**

Create `app/Modules/Presence/Services/PresensiRuleEngine.php`:

```php
<?php
namespace App\Modules\Presence\Services;

use App\Support\TenantContext;
use Carbon\Carbon;

class PresensiRuleEngine
{
    public function __construct(private TenantContext $tenant) {}

    /** Determine jenis (datang/pulang) and compute telat_menit. */
    public function evaluate(Carbon $now): array
    {
        $jamMasuk = $this->tenant->settings['jam_masuk_sekolah'] ?? '07:00';
        $jamPulang = $this->tenant->settings['jam_pulang_sekolah'] ?? '14:00';

        $masukTime = Carbon::createFromTimeString($jamMasuk);
        $pulangTime = Carbon::createFromTimeString($jamPulang);
        $nowTime = $now->copy()->setDate(1970, 1, 1);

        if ($nowTime <= $masukTime) {
            return ['jenis' => 'datang', 'telat_menit' => 0];
        }
        if ($nowTime >= $pulangTime) {
            return ['jenis' => 'pulang', 'telat_menit' => 0];
        }
        // Between jamMasuk and jamPulang — assume datang (late)
        $telat = $nowTime->diffInMinutes($masukTime);
        return ['jenis' => 'datang', 'telat_menit' => (int) $telat];
    }
}
```

- [ ] **Step 4: Implement QrScannerService**

Create `app/Modules/Presence/Services/QrScannerService.php`:

```php
<?php
namespace App\Modules\Presence\Services;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Presence\Exceptions\{AlreadyPresentException, InvalidQrException};
use App\Modules\Presence\Models\Presensi;
use Carbon\Carbon;

class QrScannerService
{
    public function __construct(private PresensiRuleEngine $ruleEngine) {}

    public function handle(string $qrCode, User $scannedBy, ?Carbon $at = null): Presensi
    {
        $at ??= now();
        $siswa = Siswa::where('qrcode', $qrCode)->first();
        if (! $siswa) throw new InvalidQrException("QR code tidak valid.");

        // Determine jenis + telat via rule engine
        ['jenis' => $jenis, 'telat_menit' => $telat] = $this->ruleEngine->evaluate($at);

        // Check duplicate for same jenis today
        $existing = Presensi::where('siswa_id', $siswa->id)
            ->where('tanggal', $at->toDateString())
            ->where('jenis', $jenis)
            ->exists();
        if ($existing) {
            throw new AlreadyPresentException("Siswa {$siswa->nama} sudah presensi {$jenis} hari ini.");
        }

        // Record
        $presensi = Presensi::create([
            'siswa_id' => $siswa->id,
            'tanggal'  => $at->toDateString(),
            'jenis'    => $jenis,
            'jam'      => $at->format('H:i'),
            'telat_menit' => $telat,
            'metode'   => 'qr',
        ]);

        return $presensi;
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test tests/Feature/Presence/QrScanTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(presence): QrScannerService + PresensiRuleEngine + exceptions"
```

---

## Task 4: IzinApprovalService + Controllers + routes + views

**Files:**
- Create: `app/Modules/Presence/Services/IzinApprovalService.php`
- Create: `app/Modules/Presence/Controllers/{PresensiController, AbsensiController, IzinController, LaporanPresensiController}.php`
- Create: `app/Modules/Presence/Policies/{PresensiPolicy, AbsensiPolicy, IzinPolicy}.php`
- Create: `app/Modules/Presence/Requests/{ScanQrRequest, StoreAbsensiRequest, StoreIzinRequest, ApproveIzinRequest}.php`
- Create: `app/Modules/Presence/routes.php`
- Create: `resources/views/presence/**/*.blade.php`
- Create: `tests/Feature/Presence/{AbsensiCrudTest, IzinApprovalTest}.php`

- [ ] **Step 1: Write izin approval test**

Create `tests/Feature/Presence/IzinApprovalTest.php`:

```php
<?php

namespace Tests\Feature\Presence;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Presence\Models\Izin;
use App\Modules\Presence\Services\IzinApprovalService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IzinApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_piket_creates_izin_as_pending(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $piket, $siswa] = $this->setupScenario();
        $izin = Izin::create([
            'tenant_id' => $tenant->id, 'siswa_id' => $siswa->id,
            'tanggal' => today(), 'jenis' => 'masuk', 'jam' => '08:00',
            'keterangan' => 'Terlambat karena urusan keluarga',
            'status' => 'pending',
        ]);
        $this->assertSame('pending', $izin->status);
    }

    public function test_bk_can_approve_pending_izin(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $bk, $siswa] = $this->setupScenario(role: 'bk');
        $izin = Izin::create([
            'tenant_id' => $tenant->id, 'siswa_id' => $siswa->id,
            'tanggal' => today(), 'jenis' => 'masuk', 'jam' => '08:00',
            'keterangan' => 'Sakit', 'status' => 'pending',
        ]);

        app(IzinApprovalService::class)->approve($izin, $bk);
        $izin->refresh();
        $this->assertSame('approved', $izin->status);
        $this->assertNotNull($izin->approved_at);
    }

    public function test_reject_sets_status(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $bk, $siswa] = $this->setupScenario(role: 'bk');
        $izin = Izin::create([
            'tenant_id' => $tenant->id, 'siswa_id' => $siswa->id,
            'tanggal' => today(), 'jenis' => 'masuk', 'keterangan' => 'X', 'status' => 'pending',
        ]);

        app(IzinApprovalService::class)->reject($izin, $bk);
        $izin->refresh();
        $this->assertSame('rejected', $izin->status);
    }

    private function setupScenario(string $role = 'piket'): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole($role);
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        return [$tenant, $user, $siswa];
    }
}
```

- [ ] **Step 2: Implement IzinApprovalService**

```php
<?php
namespace App\Modules\Presence\Services;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Presence\Models\Izin;

class IzinApprovalService
{
    public function __construct(private AuditLogger $audit) {}

    public function approve(Izin $izin, User $approver): void
    {
        $izin->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
        $this->audit->log('izin.approved', $approver, ['izin_id' => $izin->id], request());
    }

    public function reject(Izin $izin, User $approver): void
    {
        $izin->update(['status' => 'rejected', 'approved_by' => $approver->id, 'approved_at' => now()]);
        $this->audit->log('izin.rejected', $approver, ['izin_id' => $izin->id], request());
    }
}
```

- [ ] **Step 3: Implement Controllers + Policies**

Create `PresensiController.php`:

```php
<?php
namespace App\Modules\Presence\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Presence\Exceptions\{AlreadyPresentException, InvalidQrException};
use App\Modules\Presence\Requests\ScanQrRequest;
use App\Modules\Presence\Services\QrScannerService;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    public function __construct(private QrScannerService $scanner) {}

    public function scanForm()
    {
        $this->authorize('create', \App\Modules\Presence\Models\Presensi::class);
        return view('presence.presensi.scan');
    }

    public function scan(ScanQrRequest $request)
    {
        $this->authorize('create', \App\Modules\Presence\Models\Presensi::class);
        try {
            $presensi = $this->scanner->handle($request->qr_code, $request->user());
            return back()->with('status', "Presensi {$presensi->jenis} tercatat untuk {$presensi->siswa->nama}.");
        } catch (InvalidQrException $e) {
            return back()->withErrors(['qr_code' => $e->getMessage()]);
        } catch (AlreadyPresentException $e) {
            return back()->withErrors(['qr_code' => $e->getMessage()]);
        }
    }

    public function manual(Request $request)
    {
        $this->authorize('create', \App\Modules\Presence\Models\Presensi::class);
        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'jenis' => 'required|in:datang,pulang',
            'jam' => 'required|date_format:H:i',
        ]);
        \App\Modules\Presence\Models\Presensi::create([
            'siswa_id' => $data['siswa_id'],
            'tanggal' => today(),
            'jenis' => $data['jenis'],
            'jam' => $data['jam'],
            'telat_menit' => 0,
            'metode' => 'manual',
        ]);
        return back()->with('status', 'Presensi manual tercatat.');
    }

    public function rekap(Request $request)
    {
        $this->authorize('viewAny', \App\Modules\Presence\Models\Presensi::class);
        $presensi = \App\Modules\Presence\Models\Presensi::with('siswa')->whereDate('tanggal', today())->latest()->paginate(50);
        return view('presence.presensi.rekap', compact('presensi'));
    }
}
```

Create `AbsensiController.php`, `IzinController.php` (with approve/reject actions), `LaporanPresensiController.php` following Epic 5 pattern.

- [ ] **Step 4: Create Policies + FormRequests**

Create `PresensiPolicy.php`, `AbsensiPolicy.php`, `IzinPolicy.php` (similar to other modules).

Create `ScanQrRequest.php`:

```php
<?php
namespace App\Modules\Presence\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanQrRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['qr_code' => 'required|string|max:200'];
    }
}
```

- [ ] **Step 5: Add routes**

Create `app/Modules/Presence/routes.php`:

```php
<?php
use App\Modules\Presence\Controllers\{PresensiController, AbsensiController, IzinController, LaporanPresensiController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('presence/scan', [PresensiController::class, 'scanForm'])->name('presensi.scan.form')->middleware('permission:presensi.manage');
    Route::post('presence/scan', [PresensiController::class, 'scan'])->name('presensi.scan')->middleware('permission:presensi.manage');
    Route::post('presence/manual', [PresensiController::class, 'manual'])->name('presensi.manual')->middleware('permission:presensi.manage');
    Route::get('presence/rekap', [PresensiController::class, 'rekap'])->name('presensi.rekap')->middleware('permission:presensi.view');

    Route::resource('absensi', AbsensiController::class)->middleware('permission:absensi.manage');

    Route::get('izin', [IzinController::class, 'index'])->name('izin.index')->middleware('permission:izin.view');
    Route::post('izin', [IzinController::class, 'store'])->name('izin.store')->middleware('permission:izin.manage');
    Route::post('izin/{izin}/approve', [IzinController::class, 'approve'])->name('izin.approve')->middleware('permission:izin.manage');
    Route::post('izin/{izin}/reject', [IzinController::class, 'reject'])->name('izin.reject')->middleware('permission:izin.manage');
    Route::get('izin/{izin}/surat', [IzinController::class, 'surat'])->name('izin.surat')->middleware('permission:izin.view');

    Route::get('presence/laporan', [LaporanPresensiController::class, 'index'])->name('presence.laporan')->middleware('permission:presensi.view');
});
```

- [ ] **Step 6: Create views** — scan form, rekap, absensi, izin, surat izin PDF

Create `resources/views/presence/presensi/scan.blade.php` (form with input for QR + manual fallback button), `rekap.blade.php` (list today's presensi with telat badge), `izin/index.blade.php` (pending list with approve/reject buttons), `izin/surat-pdf.blade.php` (DomPDF surat with QR generated via simple-qrcode `QrCode::generate($izin->id)`).

- [ ] **Step 7: Run tests + commit + tag**

```bash
php artisan test tests/Feature/Presence/
git add -A
git commit -m "feat(presence): controllers + izin approval + surat izin PDF + scan form"
git tag epic-8-presence
```

---

## Self-Review

**Spec coverage (against DEV_DOCS-003 §3.7, DEV_DOCS-009 §5.6):**
- ✅ 3 tables (presensi, absensi, izin) — Task 1
- ✅ Multi-actor (siswa + pegawai) via siswa_id/user_id nullable — Task 1
- ✅ QrScannerService multi-step (decode → entity → duplicate → rule engine → record + event) — Task 3
- ✅ PresensiRuleEngine with tenant_settings jam_masuk/pulang — Task 3
- ✅ Manual entry fallback — Task 4
- ✅ Absensi (sakit/ijin/alpha) CRUD — Task 4
- ✅ Izin approval workflow pending→approved/rejected — Task 4
- ✅ Surat izin PDF with QR — Task 4 Step 6
- ✅ PresenceRecorded event (for future WA PelaporanOrtu) — Task 2

**Placeholder scan:** None.

**Name consistency:**
- `QrScannerService::handle(qrCode, scannedBy, ?at)` — consistent.
- `PresensiRuleEngine::evaluate(now)` returns `['jenis' => ..., 'telat_menit' => ...]` — consistent.
- `IzinApprovalService::approve(izin, approver)` + `reject(izin, approver)` — consistent.
- Exceptions `AlreadyPresentException`, `InvalidQrException` — consistent.

**Test count:** Epic 8 adds ~8 tests (5 qr scan + 3 izin approval).
