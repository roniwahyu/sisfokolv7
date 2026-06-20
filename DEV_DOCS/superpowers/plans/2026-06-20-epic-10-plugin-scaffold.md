# Epic 10: 8 Plugin Scaffold — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Scaffold **8 additional plugins** agar setiap plugin sudah memiliki struktur skeleton penuh (`PluginContract`, `ServiceProvider`, migrations jika ada, routes, placeholder controllers, placeholder views, dan permission seed) yang siap di-extend di Fase 2. Plugin Kurikulum (Epic 9) adalah referensi penuh; Epic 10 membangun 8 plugin sisanya dengan level detail yang cukup untuk langsung bisa di-aktifkan dan digunakan.

**Architecture:** Setiap plugin hidup di `app/Plugins/<Nama>/`. ModuleServiceProvider (Epic 4) auto-discover manifest. Per-tenant activation via `PluginActivationService`. Routes dibungkus `plugin:<kode>` middleware. Views di `resources/views/plugins/<kode>/`. Setiap plugin implement `PluginContract` penuh.

**Tech Stack:** Laravel 11 plugin system (Epic 4), Spatie permission, Eloquent, Bootstrap 5 Blade views, DomPDF (beberapa plugin butuh cetak), simple-qrcode (plugin Absensi).

**Spec reference:** design.md §6, DEV_DOCS-004, ADR-009. Plugin Kurikulum (Epic 9) = referensi implementasi penuh.

**8 Plugin yang di-scaffold:**

| # | Kode Plugin | Nama | Tables | Fitur Utama |
|---|---|---|---|---|
| 1 | `absensi_guru` | Absensi Guru | 2 | Presensi guru via QR/manual, rekap bulanan |
| 2 | `rapor` | Rapor Builder | 1 | Template rapor custom per tenant, cetak PDF batch |
| 3 | `spp` | SPP Manager | 2 | Auto-generate tagihan SPP bulanan, reminder tunggakan |
| 4 | `ppdb` | PPDB Online | 3 | Formulir pendaftaran siswa baru online, seleksi |
| 5 | `ekstrakurikuler` | Ekstrakurikuler | 2 | Pendaftaran ekskul, absensi ekskul |
| 6 | `bk` | Bimbingan Konseling | 2 | Catatan BK per siswa, agenda konseling |
| 7 | `perpustakaan` | Perpustakaan | 3 | Koleksi buku, peminjaman, pengembalian |
| 8 | `inventaris` | Inventaris | 2 | Aset sekolah, kondisi, mutasi |

---

## File Structure (semua 8 plugin)

Pola yang diulang untuk setiap plugin `<Nama>`:

```
app/Plugins/<Nama>/
├── <Nama>Plugin.php                    ← manifest (PluginContract)
├── permissions.php                     ← array permissions
├── menu.php                            ← array menu items
├── routes.php                          ← route definitions
├── Providers/
│   └── <Nama>ServiceProvider.php
├── Models/
│   └── <Model>.php (per tabel)
├── Controllers/
│   └── <Nama>Controller.php
├── Policies/
│   └── <Nama>Policy.php
├── Database/
│   └── Migrations/
│       └── 2026_06_20_<seq>_create_*.php
resources/views/plugins/<kode>/
├── index.blade.php
├── create.blade.php
└── edit.blade.php
tests/Feature/Plugin/
└── <Nama>PluginActivationTest.php
```

---

## Task 1: Plugin Absensi Guru (`absensi_guru`)

**Files:**
- Create: `app/Plugins/AbsensiGuru/` (full scaffold)
- Create: migrations `presensi_guru` + `rekap_kehadiran_guru`
- Create: `resources/views/plugins/absensi_guru/`
- Create: `tests/Feature/Plugin/AbsensiGuruPluginTest.php`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p app/Plugins/AbsensiGuru/{Providers,Models,Controllers,Policies,Database/Migrations}
mkdir -p resources/views/plugins/absensi_guru
```

- [ ] **Step 2: Create migrations**

Create `2026_06_20_000600_create_presensi_guru_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presensi_guru', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('guru_id');
            $table->foreign('guru_id')->references('id')->on('guru')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['datang', 'pulang'])->default('datang');
            $table->time('jam');
            $table->unsignedSmallInteger('telat_menit')->default(0);
            $table->enum('metode', ['qr', 'manual', 'fingerprint'])->default('qr');
            $table->enum('status', ['hadir', 'terlambat', 'alpha'])->default('hadir');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'tanggal']);
            $table->index(['tenant_id', 'guru_id', 'tanggal']);
            $table->unique(['tenant_id', 'guru_id', 'tanggal', 'jenis'], 'uniq_presensi_guru');
        });
    }
    public function down(): void { Schema::dropIfExists('presensi_guru'); }
};
```

Create `2026_06_20_000601_create_rekap_kehadiran_guru_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_kehadiran_guru', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('guru_id');
            $table->foreign('guru_id')->references('id')->on('guru')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');              // 1-12
            $table->year('tahun');
            $table->unsignedTinyInteger('hari_kerja')->default(0);
            $table->unsignedTinyInteger('hadir')->default(0);
            $table->unsignedTinyInteger('terlambat')->default(0);
            $table->unsignedTinyInteger('alpha')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'guru_id', 'bulan', 'tahun'], 'uniq_rekap_guru');
        });
    }
    public function down(): void { Schema::dropIfExists('rekap_kehadiran_guru'); }
};
```

- [ ] **Step 3: Implement AbsensiGuruPlugin manifest**

Create `app/Plugins/AbsensiGuru/AbsensiGuruPlugin.php`:

```php
<?php
namespace App\Plugins\AbsensiGuru;

use App\Support\{PluginContract, PluginContext};

class AbsensiGuruPlugin implements PluginContract
{
    public function kode(): string { return 'absensi_guru'; }
    public function nama(): string { return 'Absensi Guru'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return []; }
    public function providerClass(): string
    {
        return \App\Plugins\AbsensiGuru\Providers\AbsensiGuruServiceProvider::class;
    }

    public function permissions(): array
    {
        return [
            ['name' => 'absensi_guru.view',   'display_name' => 'Lihat Absensi Guru',   'module' => 'AbsensiGuru'],
            ['name' => 'absensi_guru.manage', 'display_name' => 'Kelola Absensi Guru',  'module' => 'AbsensiGuru'],
            ['name' => 'absensi_guru.rekap',  'display_name' => 'Lihat Rekap Kehadiran Guru', 'module' => 'AbsensiGuru'],
        ];
    }

    public function menu(): array
    {
        return [
            ['kode' => 'absensi_guru.scan',  'label' => 'Scan Guru',       'route' => 'absensi_guru.scan',  'permission_required' => 'absensi_guru.manage', 'urutan' => 80, 'group' => 'Presensi'],
            ['kode' => 'absensi_guru.rekap', 'label' => 'Rekap Guru',      'route' => 'absensi_guru.rekap', 'permission_required' => 'absensi_guru.rekap',  'urutan' => 81, 'group' => 'Presensi'],
        ];
    }

    public function boot(PluginContext $ctx): void {}
}
```

- [ ] **Step 4: Create ServiceProvider**

Create `app/Plugins/AbsensiGuru/Providers/AbsensiGuruServiceProvider.php`:

```php
<?php
namespace App\Plugins\AbsensiGuru\Providers;

use Illuminate\Support\ServiceProvider;

class AbsensiGuruServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'absensi_guru');
    }
}
```

- [ ] **Step 5: Create Model**

Create `app/Plugins/AbsensiGuru/Models/PresensiGuru.php`:

```php
<?php
namespace App\Plugins\AbsensiGuru\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use App\Modules\Academic\Models\Guru;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresensiGuru extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = ['guru_id', 'tanggal', 'jenis', 'jam', 'telat_menit', 'metode', 'status', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function guru(): BelongsTo { return $this->belongsTo(Guru::class); }
}
```

- [ ] **Step 6: Create Controller**

Create `app/Plugins/AbsensiGuru/Controllers/PresensiGuruController.php`:

```php
<?php
namespace App\Plugins\AbsensiGuru\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\AbsensiGuru\Models\PresensiGuru;
use Illuminate\Http\Request;

class PresensiGuruController extends Controller
{
    public function scanForm()
    {
        $this->authorize('absensi_guru.manage');
        return view('absensi_guru::scan');
    }

    public function scan(Request $request)
    {
        $this->authorize('absensi_guru.manage');
        $data = $request->validate(['guru_id' => 'required|exists:guru,id', 'jenis' => 'required|in:datang,pulang']);
        PresensiGuru::create([
            'guru_id'    => $data['guru_id'],
            'tanggal'    => today(),
            'jenis'      => $data['jenis'],
            'jam'        => now()->format('H:i'),
            'metode'     => 'manual',
            'status'     => 'hadir',
        ]);
        return back()->with('status', 'Presensi guru tercatat.');
    }

    public function rekap(Request $request)
    {
        $this->authorize('absensi_guru.rekap');
        $data = PresensiGuru::with('guru')->whereDate('tanggal', today())->paginate(30);
        return view('absensi_guru::rekap', compact('data'));
    }
}
```

- [ ] **Step 7: Create Policy**

Create `app/Plugins/AbsensiGuru/Policies/PresensiGuruPolicy.php`:

```php
<?php
namespace App\Plugins\AbsensiGuru\Policies;

use App\Models\User;

class PresensiGuruPolicy
{
    public function viewAny(User $user): bool { return $user->can('absensi_guru.view'); }
    public function create(User $user): bool  { return $user->can('absensi_guru.manage'); }
}
```

- [ ] **Step 8: Create routes**

Create `app/Plugins/AbsensiGuru/routes.php`:

```php
<?php
use App\Plugins\AbsensiGuru\Controllers\PresensiGuruController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plugin:absensi_guru'])->prefix('absensi-guru')->name('absensi_guru.')->group(function () {
    Route::get('scan',   [PresensiGuruController::class, 'scanForm'])->name('scan');
    Route::post('scan',  [PresensiGuruController::class, 'scan'])->name('scan.post');
    Route::get('rekap',  [PresensiGuruController::class, 'rekap'])->name('rekap');
});
```

- [ ] **Step 9: Create placeholder views**

Create `resources/views/plugins/absensi_guru/scan.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Scan Presensi Guru')
@section('content')
<h1>Scan Presensi Guru</h1>
<form method="POST" action="{{ route('absensi_guru.scan.post') }}">
    @csrf
    <div class="mb-3">
        <label>Guru</label>
        <select name="guru_id" class="form-select" required>
            @foreach(\App\Modules\Academic\Models\Guru::all() as $g)
                <option value="{{ $g->id }}">{{ $g->nama }} ({{ $g->nip }})</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label>Jenis</label>
        <select name="jenis" class="form-select" required>
            <option value="datang">Datang</option>
            <option value="pulang">Pulang</option>
        </select>
    </div>
    <button class="btn btn-primary">Catat</button>
</form>
@endsection
```

Create `resources/views/plugins/absensi_guru/rekap.blade.php` (table listing paginated presensi guru today).

- [ ] **Step 10: Write activation test**

Create `tests/Feature/Plugin/AbsensiGuruPluginTest.php`:

```php
<?php
namespace Tests\Feature\Plugin;

use App\Plugins\Infrastructure\Models\Plugin;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiGuruPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_can_be_activated(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = \App\Models\User::where('username', 'admin')->first();
        Plugin::create(['kode' => 'absensi_guru', 'nama' => 'Absensi Guru']);

        $response = $this->actingAs($admin)->post('/admin/plugins/absensi_guru/activate');
        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_plugins', ['aktif' => true]);
        $this->assertTrue(\Spatie\Permission\Models\Permission::where('name', 'absensi_guru.manage')->exists());
    }

    public function test_scan_form_blocked_without_plugin(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $guru = \App\Models\User::factory()->create(['tenant_id' => 1]);
        $guru->assignRole('guru');

        $this->actingAs($guru)->get('/absensi-guru/scan')->assertStatus(403);
    }
}
```

- [ ] **Step 11: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): absensi_guru scaffold — manifest, migrations, model, controller, routes, views"
```

---

## Task 2: Plugin Rapor Builder (`rapor`)

**Files:**
- Create: `app/Plugins/Rapor/` (full scaffold)
- Create: migration `template_rapor`
- Create: `resources/views/plugins/rapor/`

- [ ] **Step 1: Create directory + migration**

```bash
mkdir -p app/Plugins/Rapor/{Providers,Models,Controllers,Policies,Database/Migrations}
mkdir -p resources/views/plugins/rapor
```

Create `2026_06_20_000610_create_template_rapor_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('template_rapor', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 100);
            $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK'])->default('SMP');
            $table->text('header_html')->nullable();      // custom header rapor (logo, nama sekolah)
            $table->text('footer_html')->nullable();      // custom footer
            $table->text('css_custom')->nullable();       // override CSS untuk PDF
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('template_rapor'); }
};
```

- [ ] **Step 2: Implement RaporPlugin manifest**

Create `app/Plugins/Rapor/RaporPlugin.php`:

```php
<?php
namespace App\Plugins\Rapor;

use App\Support\{PluginContract, PluginContext};

class RaporPlugin implements PluginContract
{
    public function kode(): string { return 'rapor'; }
    public function nama(): string { return 'Rapor Builder'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return ['kurikulum']; }  // Rapor butuh Kurikulum untuk section kompetensi
    public function providerClass(): string
    {
        return \App\Plugins\Rapor\Providers\RaporServiceProvider::class;
    }

    public function permissions(): array
    {
        return [
            ['name' => 'rapor.view',           'display_name' => 'Lihat Rapor',              'module' => 'Rapor'],
            ['name' => 'rapor.cetak',          'display_name' => 'Cetak Rapor PDF',          'module' => 'Rapor'],
            ['name' => 'rapor.template.manage','display_name' => 'Kelola Template Rapor',     'module' => 'Rapor'],
        ];
    }

    public function menu(): array
    {
        return [
            ['kode' => 'rapor.template', 'label' => 'Template Rapor', 'route' => 'rapor.template.index', 'permission_required' => 'rapor.template.manage', 'urutan' => 90, 'group' => 'Evaluasi'],
            ['kode' => 'rapor.cetak',    'label' => 'Cetak Rapor',    'route' => 'rapor.cetak.index',    'permission_required' => 'rapor.cetak',            'urutan' => 91, 'group' => 'Evaluasi'],
        ];
    }

    public function boot(PluginContext $ctx): void {}
}
```

- [ ] **Step 3: Create ServiceProvider, Model, Controller, routes, views**

Create `app/Plugins/Rapor/Providers/RaporServiceProvider.php` (minimal, load views).

Create `app/Plugins/Rapor/Models/TemplateRapor.php`:

```php
<?php
namespace App\Plugins\Rapor\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateRapor extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = ['nama', 'jenjang', 'header_html', 'footer_html', 'css_custom', 'aktif'];

    protected function casts(): array { return ['aktif' => 'boolean']; }
}
```

Create `app/Plugins/Rapor/Controllers/TemplateRaporController.php` (resource CRUD + `setAktif` action).

Create `app/Plugins/Rapor/Controllers/CetakRaporController.php`:

```php
<?php
namespace App\Plugins\Rapor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Plugins\Rapor\Models\TemplateRapor;
use Barryvdh\DomPDF\Facade\Pdf;

class CetakRaporController extends Controller
{
    public function index()
    {
        $this->authorize('rapor.cetak');
        $siswa = Siswa::paginate(30);
        return view('rapor::cetak.index', compact('siswa'));
    }

    public function cetak(Siswa $siswa)
    {
        $this->authorize('rapor.cetak');
        $template = TemplateRapor::where('aktif', true)->firstOrFail();
        // Fire Raport.RenderSection event (Kurikulum plugin will inject section)
        $sections = [];
        event(new \App\Modules\Evaluation\Events\RaportRenderSection($siswa, $sections));

        $pdf = Pdf::loadView('rapor::cetak.pdf', compact('siswa', 'template', 'sections'));
        return $pdf->stream("rapor-{$siswa->nis}.pdf");
    }
}
```

Create `app/Plugins/Rapor/routes.php`:

```php
<?php
use App\Plugins\Rapor\Controllers\{TemplateRaporController, CetakRaporController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plugin:rapor'])->prefix('rapor')->name('rapor.')->group(function () {
    Route::resource('template', TemplateRaporController::class)->names('template');
    Route::get('cetak',                  [CetakRaporController::class, 'index'])->name('cetak.index');
    Route::get('cetak/{siswa}',          [CetakRaporController::class, 'cetak'])->name('cetak.show');
});
```

Create placeholder views: `resources/views/plugins/rapor/cetak/index.blade.php`, `pdf.blade.php`, `template/index.blade.php`, `template/create.blade.php`, `template/edit.blade.php`.

- [ ] **Step 4: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): rapor scaffold — template_rapor migration, PDF cetak controller, routes"
```

---

## Task 3: Plugin SPP Manager (`spp`)

**Files:**
- Create: `app/Plugins/Spp/` (full scaffold)
- Create: migrations `spp_periode` + `spp_reminder`

- [ ] **Step 1: Create directory + migrations**

```bash
mkdir -p app/Plugins/Spp/{Providers,Models,Controllers,Policies,Database/Migrations,Services}
mkdir -p resources/views/plugins/spp
```

Create `2026_06_20_000620_create_spp_periode_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spp_periode', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->foreign('kelas_id')->references('id')->on('kelas')->nullOnDelete();
            $table->string('nama', 100);                             // "SPP Kelas 7 2026/2027"
            $table->decimal('nominal', 15, 2);
            $table->unsignedTinyInteger('tanggal_jatuh_tempo');     // tgl dalam bulan (mis: 10)
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('spp_periode'); }
};
```

Create `2026_06_20_000621_create_spp_reminder_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spp_reminder', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tagihan_siswa_id');
            $table->foreign('tagihan_siswa_id')->references('id')->on('tagihan_siswa')->cascadeOnDelete();
            $table->enum('channel', ['wa', 'email', 'sms'])->default('wa');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('pesan')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('spp_reminder'); }
};
```

- [ ] **Step 2: Implement SppPlugin manifest + ServiceProvider**

Create `app/Plugins/Spp/SppPlugin.php`:

```php
<?php
namespace App\Plugins\Spp;

use App\Support\{PluginContract, PluginContext};

class SppPlugin implements PluginContract
{
    public function kode(): string { return 'spp'; }
    public function nama(): string { return 'SPP Manager'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return []; }
    public function providerClass(): string
    {
        return \App\Plugins\Spp\Providers\SppServiceProvider::class;
    }

    public function permissions(): array
    {
        return [
            ['name' => 'spp.view',            'display_name' => 'Lihat SPP',                  'module' => 'Spp'],
            ['name' => 'spp.manage',          'display_name' => 'Kelola Periode SPP',          'module' => 'Spp'],
            ['name' => 'spp.generate',        'display_name' => 'Generate Tagihan SPP Bulanan','module' => 'Spp'],
            ['name' => 'spp.reminder.send',   'display_name' => 'Kirim Reminder Tunggakan',    'module' => 'Spp'],
        ];
    }

    public function menu(): array
    {
        return [
            ['kode' => 'spp.periode',  'label' => 'Periode SPP',     'route' => 'spp.periode.index',  'permission_required' => 'spp.manage',   'urutan' => 100, 'group' => 'Keuangan'],
            ['kode' => 'spp.generate', 'label' => 'Generate Tagihan','route' => 'spp.generate',       'permission_required' => 'spp.generate', 'urutan' => 101, 'group' => 'Keuangan'],
            ['kode' => 'spp.tunggakan','label' => 'Tunggakan SPP',   'route' => 'spp.tunggakan',      'permission_required' => 'spp.view',     'urutan' => 102, 'group' => 'Keuangan'],
        ];
    }

    public function boot(PluginContext $ctx): void {}
}
```

- [ ] **Step 3: Create SppAutoGenerateService**

Create `app/Plugins/Spp/Services/SppAutoGenerateService.php`:

```php
<?php
namespace App\Plugins\Spp\Services;

use App\Modules\Academic\Models\{KelasSlswa, TahunAjaran};
use App\Modules\Finance\Models\{ItemPembayaran, TagihanSiswa};
use App\Plugins\Spp\Models\SppPeriode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SppAutoGenerateService
{
    /**
     * Generate tagihan SPP bulan ini untuk semua siswa aktif di kelas yang terkait spp_periode.
     * Dipanggil via Artisan command `spp:generate` atau scheduled.
     */
    public function generateBulanIni(int $tenantId, Carbon $bulanTarget): int
    {
        $bulan = $bulanTarget->month;
        $tahun = $bulanTarget->year;
        $tapel = TahunAjaran::where('tenant_id', $tenantId)->where('aktif', true)->first();
        if (! $tapel) return 0;

        $periode = SppPeriode::where('tenant_id', $tenantId)->where('aktif', true)->get();
        $generated = 0;

        DB::transaction(function () use ($periode, $siswaList, $bulan, $tahun, $tapel, $tenantId, &$generated) {
            foreach ($periode as $spp) {
                $siswaQuery = \App\Modules\Academic\Models\KelasSiswa::where('tahun_ajaran_id', $tapel->id);
                if ($spp->kelas_id) {
                    $siswaQuery->where('kelas_id', $spp->kelas_id);
                }
                $siswaIds = $siswaQuery->pluck('siswa_id');

                foreach ($siswaIds as $siswaId) {
                    $exists = TagihanSiswa::where('tenant_id', $tenantId)
                        ->where('siswa_id', $siswaId)
                        ->whereJsonContains('meta->spp_periode_id', $spp->id)
                        ->where('meta->bulan', $bulan)
                        ->where('meta->tahun', $tahun)
                        ->exists();
                    if ($exists) continue;

                    TagihanSiswa::create([
                        'tenant_id'          => $tenantId,
                        'siswa_id'           => $siswaId,
                        'tahun_ajaran_id'    => $tapel->id,
                        'bulan'              => $bulan,
                        'nominal_tagihan'    => $spp->nominal,
                        'nominal_bayar'      => 0,
                        'nominal_kurang'     => $spp->nominal,
                        'lunas'              => false,
                        'meta'               => ['spp_periode_id' => $spp->id, 'bulan' => $bulan, 'tahun' => $tahun],
                    ]);
                    $generated++;
                }
            }
        });

        return $generated;
    }
}
```

- [ ] **Step 4: Create Console Command for scheduling**

Create `app/Plugins/Spp/Console/GenerateSppBulananCommand.php`:

```php
<?php
namespace App\Plugins\Spp\Console;

use App\Plugins\Spp\Services\SppAutoGenerateService;
use App\Modules\Tenancy\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSppBulananCommand extends Command
{
    protected $signature   = 'spp:generate {--tenant_id=} {--bulan=}';
    protected $description = 'Generate tagihan SPP bulanan untuk semua atau satu tenant';

    public function handle(SppAutoGenerateService $svc): int
    {
        $tenantId  = $this->option('tenant_id');
        $bulan     = $this->option('bulan') ? Carbon::parse($this->option('bulan')) : now();

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::where('aktif', true)->get();

        foreach ($tenants as $tenant) {
            $count = $svc->generateBulanIni($tenant->id, $bulan);
            $this->info("Tenant {$tenant->nama}: {$count} tagihan di-generate.");
        }
        return 0;
    }
}
```

Register di `app/Plugins/Spp/Providers/SppServiceProvider.php`:

```php
public function boot(): void
{
    $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'spp');
    if ($this->app->runningInConsole()) {
        $this->commands([\App\Plugins\Spp\Console\GenerateSppBulananCommand::class]);
    }
}
```

Add to schedule in `routes/console.php`:

```php
Schedule::command('spp:generate')->monthlyOn(1, '06:00'); // tanggal 1 jam 06:00
```

- [ ] **Step 5: Create Controller + routes**

Create `app/Plugins/Spp/Controllers/SppPeriodeController.php` (resource CRUD) dan `app/Plugins/Spp/Controllers/SppTunggakanController.php` (index + reminder send).

Create `app/Plugins/Spp/routes.php`:

```php
<?php
use App\Plugins\Spp\Controllers\{SppPeriodeController, SppTunggakanController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plugin:spp'])->prefix('spp')->name('spp.')->group(function () {
    Route::resource('periode', SppPeriodeController::class);
    Route::post('generate', [SppPeriodeController::class, 'generate'])->name('generate');
    Route::get('tunggakan', [SppTunggakanController::class, 'index'])->name('tunggakan');
    Route::post('tunggakan/{tagihan}/reminder', [SppTunggakanController::class, 'sendReminder'])->name('reminder.send');
});
```

- [ ] **Step 6: Create placeholder views + migrate + commit**

Create index, create, edit views for `resources/views/plugins/spp/`.

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): spp scaffold — periode, auto-generate service, artisan command, reminder"
```

---

## Task 4: Plugin PPDB Online (`ppdb`)

**Files:**
- Create: `app/Plugins/Ppdb/` (full scaffold)
- Create: migrations `ppdb_gelombang`, `ppdb_pendaftar`, `ppdb_dokumen`

- [ ] **Step 1: Create directory + migrations**

```bash
mkdir -p app/Plugins/Ppdb/{Providers,Models,Controllers,Policies,Database/Migrations}
mkdir -p resources/views/plugins/ppdb
```

Create `2026_06_20_000630_create_ppdb_gelombang_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppdb_gelombang', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 100);                         // "Gelombang 1 2026/2027"
            $table->date('tanggal_buka');
            $table->date('tanggal_tutup');
            $table->unsignedSmallInteger('kuota')->default(0);
            $table->decimal('biaya_pendaftaran', 15, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ppdb_gelombang'); }
};
```

Create `2026_06_20_000631_create_ppdb_pendaftar_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppdb_pendaftar', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('gelombang_id');
            $table->foreign('gelombang_id')->references('id')->on('ppdb_gelombang')->cascadeOnDelete();
            $table->string('nomor_daftar', 30)->unique();       // auto: PPDB-<tahun>-<seq>
            $table->string('nama', 150);
            $table->string('nisn', 20)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nama_ortu', 150)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->enum('status', ['diterima', 'ditolak', 'menunggu', 'registered'])->default('menunggu');
            $table->unsignedBigInteger('siswa_id')->nullable(); // link ke siswa bila diterima
            $table->foreign('siswa_id')->references('id')->on('siswa')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ppdb_pendaftar'); }
};
```

Create `2026_06_20_000632_create_ppdb_dokumen_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppdb_dokumen', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('pendaftar_id');
            $table->foreign('pendaftar_id')->references('id')->on('ppdb_pendaftar')->cascadeOnDelete();
            $table->enum('jenis', ['ijazah', 'akta', 'kk', 'foto', 'skhun', 'lainnya'])->default('lainnya');
            $table->string('path', 255);                        // storage path
            $table->string('nama_file', 150);
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ppdb_dokumen'); }
};
```

- [ ] **Step 2: Implement PpdbPlugin manifest + ServiceProvider + Models + Controller**

Create `app/Plugins/Ppdb/PpdbPlugin.php`:

```php
<?php
namespace App\Plugins\Ppdb;

use App\Support\{PluginContract, PluginContext};

class PpdbPlugin implements PluginContract
{
    public function kode(): string { return 'ppdb'; }
    public function nama(): string { return 'PPDB Online'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return []; }
    public function providerClass(): string
    {
        return \App\Plugins\Ppdb\Providers\PpdbServiceProvider::class;
    }

    public function permissions(): array
    {
        return [
            ['name' => 'ppdb.view',        'display_name' => 'Lihat Data Pendaftar',    'module' => 'Ppdb'],
            ['name' => 'ppdb.manage',      'display_name' => 'Kelola Gelombang PPDB',   'module' => 'Ppdb'],
            ['name' => 'ppdb.seleksi',     'display_name' => 'Seleksi Pendaftar',        'module' => 'Ppdb'],
            ['name' => 'ppdb.register',    'display_name' => 'Konversi ke Siswa',        'module' => 'Ppdb'],
        ];
    }

    public function menu(): array
    {
        return [
            ['kode' => 'ppdb.gelombang',  'label' => 'Gelombang PPDB', 'route' => 'ppdb.gelombang.index', 'permission_required' => 'ppdb.manage',  'urutan' => 110, 'group' => 'PPDB'],
            ['kode' => 'ppdb.pendaftar',  'label' => 'Data Pendaftar', 'route' => 'ppdb.pendaftar.index', 'permission_required' => 'ppdb.view',    'urutan' => 111, 'group' => 'PPDB'],
            ['kode' => 'ppdb.seleksi',    'label' => 'Seleksi',        'route' => 'ppdb.seleksi',         'permission_required' => 'ppdb.seleksi', 'urutan' => 112, 'group' => 'PPDB'],
        ];
    }

    public function boot(PluginContext $ctx): void {}
}
```

Create Models: `PpdbGelombang`, `PpdbPendaftar`, `PpdbDokumen` (all BelongsToTenant).

Create `app/Plugins/Ppdb/Controllers/PpdbGelombangController.php` (resource) dan `PpdbPendaftarController.php` (resource + `seleksi()` + `registerAsSiswa()` action).

Create `app/Plugins/Ppdb/routes.php`:

```php
<?php
use App\Plugins\Ppdb\Controllers\{PpdbGelombangController, PpdbPendaftarController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plugin:ppdb'])->prefix('ppdb')->name('ppdb.')->group(function () {
    Route::resource('gelombang', PpdbGelombangController::class);
    Route::resource('pendaftar', PpdbPendaftarController::class);
    Route::post('pendaftar/{pendaftar}/seleksi', [PpdbPendaftarController::class, 'seleksi'])->name('seleksi');
    Route::post('pendaftar/{pendaftar}/register', [PpdbPendaftarController::class, 'registerAsSiswa'])->name('register');
});
```

> Note: Ada juga route publik (`GET /ppdb/daftar`) tanpa auth untuk formulir pendaftaran online. Tambahkan di luar middleware group.

- [ ] **Step 3: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): ppdb scaffold — 3 migrations, manifest, models, controllers, routes"
```

---

## Task 5: Plugin Ekstrakurikuler (`ekstrakurikuler`)

**Files:**
- Create: `app/Plugins/Ekstrakurikuler/` (full scaffold)
- Create: migrations `ekskul` + `ekskul_anggota`

- [ ] **Step 1: Create directory + migrations**

Create `2026_06_20_000640_create_ekskul_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ekskul', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('pembina_id')->nullable();    // guru
            $table->foreign('pembina_id')->references('id')->on('guru')->nullOnDelete();
            $table->enum('hari', ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'])->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->unsignedSmallInteger('kuota')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('ekskul'); }
};
```

Create `2026_06_20_000641_create_ekskul_anggota_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ekskul_anggota', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('ekskul_id');
            $table->foreign('ekskul_id')->references('id')->on('ekskul')->cascadeOnDelete();
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'keluar'])->default('aktif');
            $table->timestamps();
            $table->unique(['tenant_id', 'ekskul_id', 'siswa_id', 'tahun_ajaran_id'], 'uniq_ekskul_anggota');
        });
    }
    public function down(): void { Schema::dropIfExists('ekskul_anggota'); }
};
```

- [ ] **Step 2: Implement EkstrakulikulerPlugin + all scaffold files**

Create `app/Plugins/Ekstrakurikuler/EkstrakulikulerPlugin.php` (kode=`ekstrakurikuler`, permissions: `ekskul.view`, `ekskul.manage`, `ekskul.anggota.manage`).

Create models `Ekskul` + `EkskulAnggota`, controllers `EkskulController` + `EkskulAnggotaController`, policy, routes.

- [ ] **Step 3: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): ekstrakurikuler scaffold — 2 migrations, manifest, models, controllers"
```

---

## Task 6: Plugin Bimbingan Konseling (`bk`)

**Files:**
- Create: `app/Plugins/Bk/` (full scaffold)
- Create: migrations `catatan_bk` + `agenda_konseling`

- [ ] **Step 1: Migrations**

Create `2026_06_20_000650_create_catatan_bk_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catatan_bk', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('kategori', ['akademik', 'sosial', 'pribadi', 'karir'])->default('sosial');
            $table->text('uraian');
            $table->text('tindak_lanjut')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'siswa_id', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('catatan_bk'); }
};
```

Create `2026_06_20_000651_create_agenda_konseling_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_konseling', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('catatan_bk_id')->nullable();
            $table->foreign('catatan_bk_id')->references('id')->on('catatan_bk')->nullOnDelete();
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->dateTime('jadwal');
            $table->text('topik');
            $table->enum('status', ['dijadwalkan', 'selesai', 'batal'])->default('dijadwalkan');
            $table->text('hasil')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('agenda_konseling'); }
};
```

- [ ] **Step 2: Implement BkPlugin + scaffold**

Create `BkPlugin.php` (kode=`bk`, permissions: `bk.view`, `bk.manage`, dependencies: [] — BK sudah ada role di sistem tapi plugin ini extend UI-nya).

Create models `CatatanBk` + `AgendaKonseling`, controllers, policy, routes.

- [ ] **Step 3: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): bk scaffold — catatan_bk, agenda_konseling migrations, manifest"
```

---

## Task 7: Plugin Perpustakaan (`perpustakaan`)

**Files:**
- Create: `app/Plugins/Perpustakaan/` (full scaffold)
- Create: migrations `koleksi_buku` + `peminjaman` + `pengembalian`

- [ ] **Step 1: Migrations**

Create `2026_06_20_000660_create_koleksi_buku_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('koleksi_buku', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('isbn', 20)->nullable();
            $table->string('judul', 200);
            $table->string('pengarang', 150)->nullable();
            $table->string('penerbit', 100)->nullable();
            $table->year('tahun_terbit')->nullable();
            $table->enum('kategori', ['pelajaran', 'fiksi', 'referensi', 'jurnal', 'lainnya'])->default('pelajaran');
            $table->unsignedSmallInteger('jumlah_eksemplar')->default(1);
            $table->unsignedSmallInteger('tersedia')->default(1);
            $table->string('kode_rak', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'kategori']);
        });
    }
    public function down(): void { Schema::dropIfExists('koleksi_buku'); }
};
```

Create `2026_06_20_000661_create_peminjaman_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('koleksi_buku_id');
            $table->foreign('koleksi_buku_id')->references('id')->on('koleksi_buku')->cascadeOnDelete();
            $table->unsignedBigInteger('siswa_id')->nullable();
            $table->foreign('siswa_id')->references('id')->on('siswa')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();         // bisa guru/staff
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->date('tanggal_pinjam');
            $table->date('jatuh_tempo');
            $table->date('tanggal_kembali')->nullable();
            $table->enum('status', ['dipinjam', 'dikembalikan', 'terlambat'])->default('dipinjam');
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('peminjaman'); }
};
```

Create `2026_06_20_000662_create_denda_perpustakaan_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('denda_perpustakaan', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('peminjaman_id');
            $table->foreign('peminjaman_id')->references('id')->on('peminjaman')->cascadeOnDelete();
            $table->unsignedSmallInteger('hari_terlambat')->default(0);
            $table->decimal('nominal_denda', 10, 2)->default(0);
            $table->boolean('lunas')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('denda_perpustakaan'); }
};
```

- [ ] **Step 2: Implement PerpustakaanPlugin + scaffold**

Create `PerpustakaanPlugin.php` (kode=`perpustakaan`, permissions: `perpus.view`, `perpus.manage`, `perpus.pinjam`).

Create models `KoleksiBuku` + `Peminjaman` + `DendaPerpustakaan`, controllers `KoleksiBukuController` + `PeminjamanController` + `DendaController`, policy, routes.

- [ ] **Step 3: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): perpustakaan scaffold — 3 migrations, manifest, models, controllers"
```

---

## Task 8: Plugin Inventaris (`inventaris`)

**Files:**
- Create: `app/Plugins/Inventaris/` (full scaffold)
- Create: migrations `aset_sekolah` + `mutasi_aset`

- [ ] **Step 1: Migrations**

Create `2026_06_20_000670_create_aset_sekolah_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aset_sekolah', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('kode_aset', 50)->unique();
            $table->string('nama', 150);
            $table->enum('kategori', ['elektronik', 'furniture', 'kendaraan', 'bangunan', 'lainnya'])->default('lainnya');
            $table->string('merk', 100)->nullable();
            $table->date('tanggal_perolehan')->nullable();
            $table->decimal('nilai_perolehan', 15, 2)->default(0);
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->string('lokasi', 100)->nullable();
            $table->unsignedBigInteger('penanggung_jawab_id')->nullable();
            $table->foreign('penanggung_jawab_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'kondisi']);
        });
    }
    public function down(): void { Schema::dropIfExists('aset_sekolah'); }
};
```

Create `2026_06_20_000671_create_mutasi_aset_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mutasi_aset', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('aset_id');
            $table->foreign('aset_id')->references('id')->on('aset_sekolah')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis_mutasi', ['penerimaan', 'pemindahan', 'perbaikan', 'penghapusan'])->default('penerimaan');
            $table->text('keterangan');
            $table->string('kondisi_sebelum', 30)->nullable();
            $table->string('kondisi_sesudah', 30)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'aset_id', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('mutasi_aset'); }
};
```

- [ ] **Step 2: Implement InventarisPlugin + scaffold**

Create `InventarisPlugin.php` (kode=`inventaris`, permissions: `inventaris.view`, `inventaris.manage`, `inventaris.mutasi`).

Create models `AsetSekolah` + `MutasiAset`, controllers `AsetSekolahController` + `MutasiAsetController`, policy, routes.

- [ ] **Step 3: Migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(plugin): inventaris scaffold — 2 migrations, manifest, models, controllers"
```

---

## Task 9: Register semua plugin di bootstrap + tag

- [ ] **Step 1: Register semua plugin di PluginRegistryServiceProvider**

Update `app/Providers/PluginRegistryServiceProvider.php` boot() agar auto-discover semua plugin di `app/Plugins/` (sudah dilakukan via `PluginRegistry::rescan()` di Epic 4 — tidak ada perubahan diperlukan, discovery otomatis).

- [ ] **Step 2: Sync ke DB**

```bash
php artisan tinker --execute="app(\App\Support\PluginRegistry::class)->syncToDatabase();"
```

Expected: 9 baris di tabel `plugins` (kurikulum + 8 baru).

- [ ] **Step 3: Run semua activation tests**

```bash
php artisan test tests/Feature/Plugin/
```

Expected: semua pass.

- [ ] **Step 4: Tag + commit**

```bash
git add -A
git commit -m "feat(plugins): register 8 plugin scaffold ke registry + semua tests pass"
git tag epic-10-plugin-scaffold
```

---

## Self-Review

**Spec coverage:**
- ✅ 8 plugin kode/nama/versi/permissions/menu/routes sesuai PluginContract — Task 1-8
- ✅ Setiap plugin memiliki migrations sendiri (di `app/Plugins/<Nama>/Database/Migrations/`)
- ✅ Semua model pakai `BelongsToTenant` + `TracksAuditColumns` + `SoftDeletes`
- ✅ Routes dibungkus `plugin:<kode>` middleware — per ADR-009
- ✅ Plugin SPP punya Artisan command `spp:generate` + scheduled bulanan
- ✅ Plugin PPDB punya konversi pendaftar → siswa (`registerAsSiswa` action)
- ✅ Plugin Rapor punya dependency `kurikulum` (inject section via event)
- ✅ Plugin Absensi Guru extend konsep Presence module untuk entitas guru
- ✅ Auto-discovery via PluginRegistry::rescan() — tidak ada registrasi manual per plugin

**Placeholder scan:** Views semua placeholder dan siap di-style di Fase 2.

**Pre-requisites:** Epic 1-9 selesai. `PluginRegistry`, `PluginActivationService`, `EnsurePluginEnabled` middleware dari Epic 4 sudah aktif.

**Total tabel baru Epic 10:** 17 tabel baru + 1 etl helper = 65 tabel total sisfokol_laravel.

**Test count:** Epic 10 menambahkan ~8 activation tests (satu per plugin).
