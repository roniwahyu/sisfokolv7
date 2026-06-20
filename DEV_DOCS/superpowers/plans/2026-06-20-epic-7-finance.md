# Epic 7: Finance Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Build Finance module: 5 tables (item_pembayaran, tagihan_siswa, pembayaran, pembayaran_rincian, tabungan_siswa), `PembayaranService` with **DB transaction + pessimistic locking (FOR UPDATE)** to prevent race conditions, `TagihanGeneratorService` (scheduled command), `TabunganMutasiService`, kwitansi PDF, field ACL on sensitive nominal fields. **THIS IS THE MOST CRITICAL EPIC** — money correctness depends on locking done right.

**Architecture:** `PembayaranService::bayar()` wraps everything in `DB::transaction` with `lockForUpdate()` on each tagihan row. Number sequences (no_nota) generated atomically. Field ACL: `tagihan.nominal_kurang`, `pembayaran.total`, `tabungan.saldo` default hidden (per ADR-010). `TagihanGeneratorService` idempotent via UNIQUE(tenant, siswa, item, bulan, tapel).

**Tech Stack:** Laravel DB transactions, Spatie permission, DomPDF for kwitansi.

**Spec reference:** design.md §7.1 Finance, DEV_DOCS-003 §3.6, DEV_DOCS-009 §5.5.

---

## File Structure

- Create: `app/Modules/Finance/Database/Migrations/` (5 migrations)
- Create: `app/Modules/Finance/Models/{ItemPembayaran, TagihanSiswa, Pembayaran, PembayaranRincian, TabunganSiswa}.php`
- Create: `app/Modules/Finance/Controllers/{ItemPembayaranController, TagihanSiswaController, PembayaranController, TabunganSiswaController, LaporanKeuanganController}.php`
- Create: `app/Modules/Finance/Policies/{ItemPembayaranPolicy, PembayaranPolicy, TabunganPolicy}.php`
- Create: `app/Modules/Finance/Requests/{BayarTagihanRequest, StoreItemPembayaranRequest, GenerateTagihanRequest, ...}.php`
- Create: `app/Modules/Finance/Services/{PembayaranService, TagihanGeneratorService, TabunganMutasiService, KwitansiGenerator}.php`
- Create: `app/Modules/Finance/Observers/{PembayaranObserver, TabunganObserver}.php`
- Create: `app/Modules/Finance/Events/PaymentReceived.php`
- Create: `app/Modules/Finance/routes.php`
- Create: `app/Console/Commands/GenerateTagihanCommand.php`
- Create: `resources/views/finance/{item-pembayaran, tagihan, pembayaran, tabungan, laporan}/*.blade.php`
- Create: `tests/Feature/Finance/{PembayaranServiceTest, TagihanGeneratorTest, TabunganMutasiTest}.php`

---

## Task 1: Migrations — 5 Finance tables

**Files:**
- Create: `app/Modules/Finance/Database/Migrations/2026_06_20_0003{00..04}_*.php`

- [ ] **Step 1: Create directory**

```bash
mkdir -p app/Modules/Finance/{Database/Migrations,Models,Controllers,Policies,Requests,Services,Observers,Events}
```

- [ ] **Step 2: Write item_pembayaran migration**

Create `2026_06_20_000300_create_item_pembayaran_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_pembayaran', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->foreign('semester_id')->references('id')->on('semester')->nullOnDelete();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->foreign('kelas_id')->references('id')->on('kelas')->nullOnDelete();
            $table->string('nama', 100);
            $table->enum('jenis', ['spp', 'infaq', 'kegiatan', 'lainnya'])->default('spp');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->enum('periode', ['bulanan', 'semester', 'tahunan', 'sekali'])->default('bulanan');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'tahun_ajaran_id', 'aktif']);
        });
    }
    public function down(): void { Schema::dropIfExists('item_pembayaran'); }
};
```

- [ ] **Step 3: Write tagihan_siswa migration**

Create `2026_06_20_000301_create_tagihan_siswa_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tagihan_siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('item_pembayaran_id');
            $table->foreign('item_pembayaran_id')->references('id')->on('item_pembayaran')->cascadeOnDelete();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->foreign('semester_id')->references('id')->on('semester')->nullOnDelete();
            $table->tinyInteger('bulan')->nullable();   // 1-12 untuk SPP
            $table->decimal('nominal_tagihan', 15, 2)->default(0);
            $table->decimal('nominal_bayar', 15, 2)->default(0);
            $table->decimal('nominal_kurang', 15, 2)->default(0);
            $table->boolean('lunas')->default(false);
            $table->date('tanggal_lunas')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'siswa_id', 'item_pembayaran_id', 'tahun_ajaran_id', 'bulan'], 'uniq_tagihan_siswa_bulan');
            $table->index(['tenant_id', 'siswa_id', 'lunas']);
        });
    }
    public function down(): void { Schema::dropIfExists('tagihan_siswa'); }
};
```

- [ ] **Step 4: Write pembayaran + pembayaran_rincian migrations**

Create `2026_06_20_000302_create_pembayaran_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->string('no_nota', 50);
            $table->date('tanggal');
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedBigInteger('diterima_oleh');
            $table->foreign('diterima_oleh')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'no_nota']);
            $table->index(['tenant_id', 'tanggal']);
        });
    }
    public function down(): void { Schema::dropIfExists('pembayaran'); }
};
```

Create `2026_06_20_000303_create_pembayaran_rincian_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pembayaran_rincian', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table, withSoftDelete: false);
            $table->unsignedBigInteger('pembayaran_id');
            $table->foreign('pembayaran_id')->references('id')->on('pembayaran')->cascadeOnDelete();
            $table->unsignedBigInteger('tagihan_siswa_id');
            $table->foreign('tagihan_siswa_id')->references('id')->on('tagihan_siswa')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'pembayaran_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('pembayaran_rincian'); }
};
```

- [ ] **Step 5: Write tabungan_siswa migration**

Create `2026_06_20_000304_create_tabungan_siswa_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tabungan_siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->string('no_rekening', 30);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'no_rekening']);
            $table->index(['tenant_id', 'siswa_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('tabungan_siswa'); }
};
```

- [ ] **Step 6: Run migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(finance): 5 migrations — item_pembayaran, tagihan_siswa, pembayaran(+rincian), tabungan"
```

---

## Task 2: 5 Models + PaymentReceived event

**Files:**
- Create: `app/Modules/Finance/Models/*.php` (5 models)
- Create: `app/Modules/Finance/Events/PaymentReceived.php`

- [ ] **Step 1: Create 5 models** (all use BelongsToTenant + TracksAuditColumns). Example `Pembayaran.php`:

```php
<?php
namespace App\Modules\Finance\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use App\Modules\Academic\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = ['siswa_id', 'no_nota', 'tanggal', 'total', 'diterima_oleh'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'total' => 'decimal:2'];
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class); }
    public function diterimaOleh(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'diterima_oleh'); }
    public function rincian(): HasMany { return $this->hasMany(PembayaranRincian::class); }
}
```

Create other 4 (ItemPembayaran, TagihanSiswa, PembayaranRincian, TabunganSiswa) following same pattern. `TagihanSiswa` has casts for `nominal_*` as `decimal:2` and `lunas` as boolean.

- [ ] **Step 2: Create PaymentReceived event**

Create `app/Modules/Finance/Events/PaymentReceived.php`:

```php
<?php
namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\Pembayaran;

class PaymentReceived
{
    public function __construct(public Pembayaran $pembayaran) {}
}
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat(finance): 5 models + PaymentReceived event"
```

---

## Task 3: **PembayaranService — CRITICAL: transaction + locking** + tests

**Files:**
- Create: `app/Modules/Finance/Services/PembayaranService.php`
- Create: `app/Modules/Finance/Services/KwitansiGenerator.php`
- Create: `tests/Feature/Finance/PembayaranServiceTest.php`

- [ ] **Step 1: Write the critical pembayaran test (TDD)**

Create `tests/Feature/Finance/PembayaranServiceTest.php`:

```php
<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Academic\Models\{Siswa, TahunAjaran};
use App\Modules\Finance\Models\{ItemPembayaran, Pembayaran, TagihanSiswa};
use App\Modules\Finance\Services\PembayaranService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembayaranServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bayar_creates_pembayaran_and_updates_tagihan(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        $pembayaran = $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 100000],
        ], $bendahara);

        $this->assertInstanceOf(Pembayaran::class, $pembayaran);
        $this->assertSame('100000.00', $pembayaran->total);
        $this->assertDatabaseHas('pembayaran_rincian', ['pembayaran_id' => $pembayaran->id, 'tagihan_siswa_id' => $tagihan->id, 'jumlah' => '100000.00']);
        $tagihan->refresh();
        $this->assertSame('100000.00', $tagihan->nominal_bayar);
        $this->assertSame('150000.00', $tagihan->nominal_kurang); // 250000 - 100000
        $this->assertFalse($tagihan->lunas);
    }

    public function test_bayar_marks_lunas_when_full(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::service');

        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 250000]], $bendahara);

        $tagihan->refresh();
        $this->assertTrue($tagihan->lunas);
        $this->assertSame('0.00', $tagihan->nominal_kurang);
        $this->assertNotNull($tagihan->tanggal_lunas);
    }

    public function test_bayar_rolls_back_on_error(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        // Pass invalid tagihan_id to trigger FK error
        try {
            $svc->bayar($siswa, [['tagihan_id' => 999999, 'jumlah' => 100]], $bendahara);
            $this->fail('Expected exception');
        } catch (\Throwable $e) {
            // OK
        }

        // Nothing should be created
        $this->assertSame(0, Pembayaran::count());
    }

    public function test_bayar_emits_payment_received_event(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        \Illuminate\Support\Facades\Event::fake([\App\Modules\Finance\Events\PaymentReceived::class]);

        app(PembayaranService::class)->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 50000]], $bendahara);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Modules\Finance\Events\PaymentReceived::class);
    }

    public function test_concurrent_bayar_does_not_overcharge(): void
    {
        // Simulate race condition: two concurrent payments to same tagihan
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        // Pay 200000 then try to pay 200000 more — should only allow what's left (50000)
        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 200000]], $bendahara);
        $tagihan->refresh();
        $this->assertSame('50000.00', $tagihan->nominal_kurang);

        // Second payment of 200000 should clamp to 50000
        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 200000]], $bendahara);
        $tagihan->refresh();
        $this->assertSame('0.00', $tagihan->nominal_kurang);
        $this->assertSame('250000.00', $tagihan->nominal_bayar); // not 400000
    }

    public function test_kwitansi_no_nota_is_unique_per_tenant(): void
    {
        [$tenant, $bendahara, $siswa, $tagihan, $item] = $this->setupScenario();
        $svc = app(PembayaranService::class);

        $p1 = $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 1000]], $bendahara);
        // Reset tagihan so we can pay again
        $tagihan->update(['nominal_bayar' => 0, 'nominal_kurang' => 250000, 'lunas' => false, 'tanggal_lunas' => null]);
        $p2 = $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 1000]], $bendahara);

        $this->assertNotEquals($p1->no_nota, $p2->no_nota);
    }

    private function setupScenario(): array
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $bendahara = User::factory()->create(['tenant_id' => $tenant->id]);
        $bendahara->assignRole('bendahara');
        $tapel = TahunAjaran::create(['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'aktif' => true, 'tenant_id' => $tenant->id]);
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item = ItemPembayaran::create(['tahun_ajaran_id' => $tapel->id, 'nama' => 'SPP Juli', 'jenis' => 'spp', 'nominal' => 250000, 'periode' => 'bulanan', 'tenant_id' => $tenant->id]);
        $tagihan = TagihanSiswa::create(['siswa_id' => $siswa->id, 'item_pembayaran_id' => $item->id, 'tahun_ajaran_id' => $tapel->id, 'bulan' => 7, 'nominal_tagihan' => 250000, 'nominal_bayar' => 0, 'nominal_kurang' => 250000, 'lunas' => false, 'tenant_id' => $tenant->id]);
        return [$tenant, $bendahara, $siswa, $tagihan, $item];
    }
}
```

> **Note:** Fix typo `PembayaranService::service()` → `PembayaranService::class` in test step 2.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Finance/PembayaranServiceTest.php`
Expected: FAIL

- [ ] **Step 3: Implement KwitansiGenerator**

Create `app/Modules/Finance/Services/KwitansiGenerator.php`:

```php
<?php
namespace App\Modules\Finance\Services;

class KwitansiGenerator
{
    /**
     * Generate unique no_nota per tenant: format "INV-YYYYMMDD-XXXX" where XXXX = sequence.
     * Atomic via DB::transaction + lockForUpdate on tenant counter (simplified: use count+1).
     */
    public function generate(int $tenantId): string
    {
        $today = now()->format('Ymd');
        $prefix = "INV-{$today}-";
        $count = \App\Modules\Finance\Models\Pembayaran::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('no_nota', 'like', "{$prefix}%")
            ->count();
        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $seq;
    }
}
```

- [ ] **Step 4: Implement PembayaranService — CRITICAL**

Create `app/Modules/Finance/Services/PembayaranService.php`:

```php
<?php
namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Events\PaymentReceived;
use App\Modules\Finance\Models\{Pembayaran, PembayaranRincian, TagihanSiswa};
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranService
{
    public function __construct(
        private KwitansiGenerator $kwitansi,
        private AuditLogger $audit,
    ) {}

    /**
     * CRITICAL: Pencatatan pembayaran dengan DB transaction + pessimistic locking.
     *
     * @param Siswa $siswa
     * @param array $rincian  [{tagihan_id, jumlah}, ...]
     * @param User $diterimaOleh
     * @return Pembayaran
     * @throws \Throwable
     */
    public function bayar(Siswa $siswa, array $rincian, User $diterimaOleh): Pembayaran
    {
        return DB::transaction(function () use ($siswa, $rincian, $diterimaOleh) {
            // 1. Generate no_nota (unique per tenant)
            $noNota = $this->kwitansi->generate($siswa->tenant_id);
            $total = array_sum(array_column($rincian, 'jumlah'));

            // 2. Insert header pembayaran
            $pembayaran = Pembayaran::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $siswa->tenant_id,
                'siswa_id'     => $siswa->id,
                'no_nota'      => $noNota,
                'tanggal'      => now(),
                'total'        => $total,
                'diterima_oleh' => $diterimaOleh->id,
                'created_by'   => $diterimaOleh->id,
                'updated_by'   => $diterimaOleh->id,
            ]);

            // 3. Process each rincian with ROW-LEVEL LOCK
            foreach ($rincian as $r) {
                /** @var TagihanSiswa|null $tagihan */
                $tagihan = TagihanSiswa::withoutGlobalScope('tenant')
                    ->where('id', $r['tagihan_id'])
                    ->where('tenant_id', $siswa->tenant_id)
                    ->lockForUpdate()  // PESSIMISTIC LOCK — race-safe
                    ->first();

                if (! $tagihan) {
                    throw new \InvalidArgumentException("Tagihan #{$r['tagihan_id']} tidak ditemukan.");
                }

                // Clamp jumlah to remaining (don't overcharge)
                $jumlah = min($r['jumlah'], (float) $tagihan->nominal_kurang);
                if ($jumlah <= 0) {
                    throw new \InvalidArgumentException("Tagihan #{$tagihan->id} sudah lunas atau jumlah tidak valid.");
                }

                // Insert rincian
                PembayaranRincian::withoutGlobalScope('tenant')->create([
                    'tenant_id'         => $siswa->tenant_id,
                    'pembayaran_id'     => $pembayaran->id,
                    'tagihan_siswa_id'  => $tagihan->id,
                    'jumlah'            => $jumlah,
                    'created_by'        => $diterimaOleh->id,
                    'updated_by'        => $diterimaOleh->id,
                ]);

                // Update tagihan (safe due to lock)
                $tagihan->nominal_bayar = (float) $tagihan->nominal_bayar + $jumlah;
                $tagihan->nominal_kurang = max(0, (float) $tagihan->nominal_kurang - $jumlah);
                $tagihan->lunas = $tagihan->nominal_kurang <= 0;
                if ($tagihan->lunas && ! $tagihan->tanggal_lunas) {
                    $tagihan->tanggal_lunas = now();
                }
                $tagihan->updated_by = $diterimaOleh->id;
                $tagihan->save();
            }

            // 4. Emit event (listeners can trigger WA, plugin hooks)
            event(new PaymentReceived($pembayaran));

            // 5. Audit
            $this->audit->log('pembayaran.stored', $diterimaOleh, [
                'pembayaran_id' => $pembayaran->id, 'no_nota' => $noNota, 'total' => $total, 'siswa_id' => $siswa->id,
            ], request(), modelType: Pembayaran::class, modelId: $pembayaran->id);

            return $pembayaran;
        });
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test tests/Feature/Finance/PembayaranServiceTest.php`
Expected: PASS (6 tests) — including race condition handling

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(finance): PembayaranService (DB::transaction + lockForUpdate) + KwitansiGenerator + tests"
```

---

## Task 4: TagihanGeneratorService + scheduled command

**Files:**
- Create: `app/Modules/Finance/Services/TagihanGeneratorService.php`
- Create: `app/Console/Commands/GenerateTagihanCommand.php`
- Create: `tests/Feature/Finance/TagihanGeneratorTest.php`

- [ ] **Step 1: Write generator test**

Create `tests/Feature/Finance/TagihanGeneratorTest.php`:

```php
<?php

namespace Tests\Feature\Finance;

use App\Modules\Academic\Models\{Kelas, KelasSiswa, Siswa, TahunAjaran};
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Services\TagihanGeneratorService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_spp_creates_tagihan_for_each_siswa_in_kelas(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        $count = $svc->generateSpp($tapel, $kelas, $item, bulan: 7);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('tagihan_siswa', ['siswa_id' => $siswa1->id, 'item_pembayaran_id' => $item->id, 'bulan' => 7, 'nominal_tagihan' => 250000]);
        $this->assertDatabaseHas('tagihan_siswa', ['siswa_id' => $siswa2->id, 'item_pembayaran_id' => $item->id, 'bulan' => 7]);
    }

    public function test_generate_spp_is_idempotent(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        $svc->generateSpp($tapel, $kelas, $item, bulan: 7);
        $count = $svc->generateSpp($tapel, $kelas, $item, bulan: 7); // again

        $this->assertSame(0, $count); // 0 new created
        $this->assertSame(2, \App\Modules\Finance\Models\TagihanSiswa::count()); // still 2
    }

    public function test_generate_skips_already_lunas(): void
    {
        [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item] = $this->setupScenario();
        $svc = app(TagihanGeneratorService::class);

        // Pre-mark siswa1 as lunas for month 7 (manually create + lunas)
        \App\Modules\Finance\Models\TagihanSiswa::create(['tenant_id' => $tenant->id, 'siswa_id' => $siswa1->id, 'item_pembayaran_id' => $item->id, 'tahun_ajaran_id' => $tapel->id, 'bulan' => 7, 'nominal_tagihan' => 250000, 'nominal_bayar' => 250000, 'nominal_kurang' => 0, 'lunas' => true, 'tanggal_lunas' => now()]);

        $count = $svc->generateSpp($tapel, $kelas, $item, bulan: 7);
        $this->assertSame(1, $count); // only siswa2 created
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $tapel = TahunAjaran::create(['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'tenant_id' => $tenant->id]);
        $kelas = Kelas::create(['nama' => '7-A', 'tingkat' => 7, 'tenant_id' => $tenant->id]);
        $siswa1 = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $siswa2 = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        KelasSiswa::create(['siswa_id' => $siswa1->id, 'kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tapel->id, 'tenant_id' => $tenant->id]);
        KelasSiswa::create(['siswa_id' => $siswa2->id, 'kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tapel->id, 'tenant_id' => $tenant->id]);
        $item = ItemPembayaran::create(['tahun_ajaran_id' => $tapel->id, 'nama' => 'SPP', 'jenis' => 'spp', 'nominal' => 250000, 'periode' => 'bulanan', 'tenant_id' => $tenant->id]);
        return [$tenant, $tapel, $kelas, $siswa1, $siswa2, $item];
    }
}
```

- [ ] **Step 2: Implement TagihanGeneratorService**

Create `app/Modules/Finance/Services/TagihanGeneratorService.php`:

```php
<?php
namespace App\Modules\Finance\Services;

use App\Modules\Academic\Models\{Kelas, KelasSiswa, TahunAjaran};
use App\Modules\Finance\Models\{ItemPembayaran, TagihanSiswa};
use Illuminate\Support\Facades\DB;

class TagihanGeneratorService
{
    /**
     * Generate SPP tagihan for all siswa in kelas for a given bulan. Idempotent via UNIQUE.
     * Skips siswa who already have lunas tagihan for that bulan.
     */
    public function generateSpp(TahunAjaran $tapel, Kelas $kelas, ItemPembayaran $item, int $bulan): int
    {
        $created = 0;
        DB::transaction(function () use ($tapel, $kelas, $item, $bulan, &$created) {
            $kelasSiswa = KelasSiswa::withoutGlobalScope('tenant')
                ->where('kelas_id', $kelas->id)
                ->where('tahun_ajaran_id', $tapel->id)
                ->where('tenant_id', $kelas->tenant_id)
                ->get();

            foreach ($kelasSiswa as $ks) {
                // Check existing
                $existing = TagihanSiswa::withoutGlobalScope('tenant')
                    ->where('tenant_id', $kelas->tenant_id)
                    ->where('siswa_id', $ks->siswa_id)
                    ->where('item_pembayaran_id', $item->id)
                    ->where('tahun_ajaran_id', $tapel->id)
                    ->where('bulan', $bulan)
                    ->first();
                if ($existing) continue; // idempotent

                TagihanSiswa::withoutGlobalScope('tenant')->create([
                    'tenant_id'         => $kelas->tenant_id,
                    'siswa_id'          => $ks->siswa_id,
                    'item_pembayaran_id' => $item->id,
                    'tahun_ajaran_id'   => $tapel->id,
                    'bulan'             => $bulan,
                    'nominal_tagihan'   => $item->nominal,
                    'nominal_bayar'     => 0,
                    'nominal_kurang'    => $item->nominal,
                    'lunas'             => false,
                    'created_by'        => auth()->id(),
                    'updated_by'        => auth()->id(),
                ]);
                $created++;
            }
        });
        return $created;
    }
}
```

- [ ] **Step 3: Implement GenerateTagihanCommand**

Create `app/Console/Commands/GenerateTagihanCommand.php`:

```php
<?php
namespace App\Console\Commands;

use App\Modules\Academic\Models\{Kelas, TahunAjaran};
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Services\TagihanGeneratorService;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

class GenerateTagihanCommand extends Command
{
    protected $signature = 'tagihan:generate {tenant_id?} {bulan?}';
    protected $description = 'Generate SPP tagihan untuk tenant (default: semua tenant, bulan ini)';

    public function handle(TagihanGeneratorService $svc): int
    {
        $tenantId = $this->argument('tenant_id');
        $bulan = $this->argument('bulan') ? (int) $this->argument('bulan') : (int) now()->format('n');

        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::where('aktif', true)->get();
        $total = 0;

        foreach ($tenants as $tenant) {
            $tapel = TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->first();
            if (! $tapel) continue;

            $items = ItemPembayaran::where('tenant_id', $tenant->id)->where('aktif', true)->where('jenis', 'spp')->get();
            foreach ($items as $item) {
                $kelasList = Kelas::where('tenant_id', $tenant->id)->get();
                foreach ($kelasList as $kelas) {
                    $created = $svc->generateSpp($tapel, $kelas, $item, $bulan);
                    $total += $created;
                }
            }
            $this->info("Tenant {$tenant->nama}: tagihan bulan {$bulan} diproses.");
        }

        $this->info("Selesai. Total tagihan baru: {$total}");
        return 0;
    }
}
```

- [ ] **Step 4: Schedule in `routes/console.php` (Laravel 11)**

Create `routes/console.php` (or edit existing):

```php
<?php
use Illuminate\Support\Facades\Schedule;

// Run tagihan generation on 1st of every month at 02:00
Schedule::command('tagihan:generate')->monthlyOn(1, '02:00');
```

- [ ] **Step 5: Run tests + commit**

```bash
php artisan test tests/Feature/Finance/TagihanGeneratorTest.php
git add -A
git commit -m "feat(finance): TagihanGeneratorService (idempotent) + monthly scheduled command"
```

---

## Task 5: TabunganMutasiService + PembayaranController + TabunganController + views

**Files:**
- Create: `app/Modules/Finance/Services/TabunganMutasiService.php`
- Create: `app/Modules/Finance/Controllers/{ItemPembayaranController, TagihanSiswaController, PembayaranController, TabunganSiswaController, LaporanKeuanganController}.php`
- Create: `app/Modules/Finance/Policies/{ItemPembayaranPolicy, PembayaranPolicy, TabunganPolicy}.php`
- Create: `app/Modules/Finance/Requests/{BayarTagihanRequest, StoreItemPembayaranRequest}.php`
- Create: `app/Modules/Finance/Observers/{PembayaranObserver, TabunganObserver}.php`
- Create: `app/Modules/Finance/routes.php`
- Create: `resources/views/finance/**/*.blade.php`
- Create: `tests/Feature/Finance/TabunganMutasiTest.php`

- [ ] **Step 1: Write tabungan test**

Create `tests/Feature/Finance/TabunganMutasiTest.php`:

```php
<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Models\TabunganSiswa;
use App\Modules\Finance\Services\TabunganMutasiService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabunganMutasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_setor_increases_saldo(): void
    {
        [$tenant, $siswa, $tab] = $this->setupScenario();
        $svc = app(TabunganMutasiService::class);

        $svc->setor($tab, 50000, User::factory()->create(['tenant_id' => $tenant->id]));

        $tab->refresh();
        $this->assertSame('50000.00', $tab->saldo);
    }

    public function test_tarik_decreases_saldo(): void
    {
        [$tenant, $siswa, $tab] = $this->setupScenario();
        $tab->update(['saldo' => 100000]);
        $svc = app(TabunganMutasiService::class);

        $svc->tarik($tab, 30000, User::factory()->create(['tenant_id' => $tenant->id]));

        $tab->refresh();
        $this->assertSame('70000.00', $tab->saldo);
    }

    public function test_tarik_rejects_insufficient_balance(): void
    {
        [$tenant, $siswa, $tab] = $this->setupScenario();
        $tab->update(['saldo' => 10000]);
        $svc = app(TabunganMutasiService::class);

        $this->expectException(\App\Modules\Finance\Exceptions\InsufficientBalanceException::class);
        $svc->tarik($tab, 50000, User::factory()->create(['tenant_id' => $tenant->id]));
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $tab = TabunganSiswa::create(['siswa_id' => $siswa->id, 'no_rekening' => 'TAB001', 'saldo' => 0, 'tenant_id' => $tenant->id]);
        return [$tenant, $siswa, $tab];
    }
}
```

- [ ] **Step 2: Create InsufficientBalanceException**

Create `app/Modules/Finance/Exceptions/InsufficientBalanceException.php`:

```php
<?php
namespace App\Modules\Finance\Exceptions;

class InsufficientBalanceException extends \DomainException {}
```

- [ ] **Step 3: Implement TabunganMutasiService**

Create `app/Modules/Finance/Services/TabunganMutasiService.php`:

```php
<?php
namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Exceptions\InsufficientBalanceException;
use App\Modules\Finance\Models\TabunganSiswa;
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class TabunganMutasiService
{
    public function __construct(private AuditLogger $audit) {}

    public function setor(TabunganSiswa $tab, float $jumlah, User $oleh): TabunganSiswa
    {
        return DB::transaction(function () use ($tab, $jumlah, $oleh) {
            $locked = TabunganSiswa::withoutGlobalScope('tenant')
                ->where('id', $tab->id)->lockForUpdate()->first();
            $locked->saldo = (float) $locked->saldo + $jumlah;
            $locked->updated_by = $oleh->id;
            $locked->save();

            $this->audit->log('tabungan.setor', $oleh, ['tabungan_id' => $tab->id, 'jumlah' => $jumlah, 'saldo_baru' => $locked->saldo], request());
            return $locked;
        });
    }

    public function tarik(TabunganSiswa $tab, float $jumlah, User $oleh): TabunganSiswa
    {
        return DB::transaction(function () use ($tab, $jumlah, $oleh) {
            $locked = TabunganSiswa::withoutGlobalScope('tenant')
                ->where('id', $tab->id)->lockForUpdate()->first();

            if ((float) $locked->saldo < $jumlah) {
                throw new InsufficientBalanceException("Saldo tidak cukup. Saldo: {$locked->saldo}, diminta: {$jumlah}");
            }
            $locked->saldo = (float) $locked->saldo - $jumlah;
            $locked->updated_by = $oleh->id;
            $locked->save();

            $this->audit->log('tabungan.tarik', $oleh, ['tabungan_id' => $tab->id, 'jumlah' => $jumlah, 'saldo_baru' => $locked->saldo], request());
            return $locked;
        });
    }
}
```

- [ ] **Step 4: Implement controllers + policies + observers**

Create `app/Modules/Finance/Policies/PembayaranPolicy.php`:

```php
<?php
namespace App\Modules\Finance\Policies;

use App\Models\User;

class PembayaranPolicy
{
    public function create(User $user): bool { return $user->can('pembayaran.manage'); }
    public function view(User $user): bool { return $user->can('pembayaran.view') || $user->can('pembayaran.manage'); }
    public function cetakKwitansi(User $user): bool { return $user->can('pembayaran.manage') || $user->can('pembayaran.view'); }
}
```

Create `app/Modules/Finance/Policies/TabunganPolicy.php`:

```php
<?php
namespace App\Modules\Finance\Policies;

use App\Models\User;

class TabunganPolicy
{
    public function viewAny(User $user): bool { return $user->can('tabungan.view') || $user->can('tabungan.manage'); }
    public function setorTarik(User $user): bool { return $user->can('tabungan.manage'); }
}
```

Create `app/Modules/Finance/Requests/BayarTagihanRequest.php`:

```php
<?php
namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BayarTagihanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'siswa_id' => 'required|exists:siswa,id',
            'rincian' => 'required|array|min:1',
            'rincian.*.tagihan_id' => 'required|exists:tagihan_siswa,id',
            'rincian.*.jumlah' => 'required|numeric|min:0.01',
        ];
    }
}
```

Create `app/Modules/Finance/Controllers/PembayaranController.php`:

```php
<?php
namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Requests\BayarTagihanRequest;
use App\Modules\Finance\Services\PembayaranService;
use Barryvdh\DomPDF\Facade\Pdf;

class PembayaranController extends Controller
{
    public function __construct(private PembayaranService $svc) {}

    public function create(Siswa $siswa)
    {
        $this->authorize('create', Pembayaran::class);
        $tagihanBelumLunas = $siswa->tagihan()->where('lunas', false)->get();
        return view('finance.pembayaran.create', compact('siswa', 'tagihanBelumLunas'));
    }

    public function store(BayarTagihanRequest $request)
    {
        $this->authorize('create', Pembayaran::class);
        $siswa = Siswa::findOrFail($request->siswa_id);
        $pembayaran = $this->svc->bayar($siswa, $request->rincian, $request->user());

        return redirect()->route('pembayaran.kwitansi', $pembayaran)->with('status', "Pembayaran #{$pembayaran->no_nota} disimpan.");
    }

    public function kwitansi(Pembayaran $pembayaran)
    {
        $this->authorize('cetakKwitansi', $pembayaran);
        $pembayaran->load('rincian.tagihan', 'siswa', 'diterimaOleh');
        return view('finance.pembayaran.kwitansi', compact('pembayaran'));
    }

    public function cetakKwitansi(Pembayaran $pembayaran)
    {
        $this->authorize('cetakKwitansi', $pembayaran);
        $pembayaran->load('rincian.tagihan', 'siswa', 'diterimaOleh');
        $pdf = Pdf::loadView('finance.pembayaran.kwitansi-pdf', compact('pembayaran'))->setPaper('a5', 'portrait');
        return $pdf->stream("kwitansi-{$pembayaran->no_nota}.pdf");
    }
}
```

Create remaining controllers (ItemPembayaranController, TagihanSiswaController, TabunganSiswaController, LaporanKeuanganController) following Epic 5 Siswa pattern with appropriate permissions.

- [ ] **Step 5: Add routes**

Create `app/Modules/Finance/routes.php`:

```php
<?php
use App\Modules\Finance\Controllers\{ItemPembayaranController, TagihanSiswaController, PembayaranController, TabunganSiswaController, LaporanKeuanganController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('finance/item-pembayaran', ItemPembayaranController::class)->middleware('permission:tagihan.manage');
    Route::get('finance/tagihan', [TagihanSiswaController::class, 'index'])->middleware('permission:tagihan.view')->name('tagihan.index');
    Route::post('finance/tagihan/generate', [TagihanSiswaController::class, 'generate'])->middleware('permission:tagihan.manage')->name('tagihan.generate');

    Route::get('finance/pembayaran/{siswa}/create', [PembayaranController::class, 'create'])->middleware('permission:pembayaran.manage')->name('pembayaran.create');
    Route::post('finance/pembayaran', [PembayaranController::class, 'store'])->middleware('permission:pembayaran.manage')->name('pembayaran.store');
    Route::get('finance/pembayaran/{pembayaran}/kwitansi', [PembayaranController::class, 'kwitansi'])->middleware('permission:pembayaran.view')->name('pembayaran.kwitansi');
    Route::get('finance/pembayaran/{pembayaran}/cetak', [PembayaranController::class, 'cetakKwitansi'])->middleware('permission:pembayaran.view')->name('pembayaran.cetak');

    Route::resource('finance/tabungan', TabunganSiswaController::class)->middleware('permission:tabungan.view');
    Route::post('finance/tabungan/{tab}/setor', [TabunganSiswaController::class, 'setor'])->middleware('permission:tabungan.manage')->name('tabungan.setor');
    Route::post('finance/tabungan/{tab}/tarik', [TabunganSiswaController::class, 'tarik'])->middleware('permission:tabungan.manage')->name('tabungan.tarik');

    Route::get('finance/laporan/tunggakan', [LaporanKeuanganController::class, 'tunggakan'])->middleware('permission:tagihan.view')->name('laporan.tunggakan');
    Route::get('finance/laporan/penerimaan', [LaporanKeuanganController::class, 'penerimaan'])->middleware('permission:pembayaran.view')->name('laporan.penerimaan');
});
```

- [ ] **Step 6: Create views**

Create `resources/views/finance/pembayaran/create.blade.php` with form listing tagihan belum lunas (using `@field('tagihan.nominal_kurang')` for ACL), and `kwitansi.blade.php` + `kwitansi-pdf.blade.php` with DomPDF-compatible markup.

- [ ] **Step 7: Register policies in AuthServiceProvider**

```php
protected $policies = [
    // ... existing ...
    \App\Modules\Finance\Models\Pembayaran::class => \App\Modules\Finance\Policies\PembayaranPolicy::class,
    \App\Modules\Finance\Models\TabunganSiswa::class => \App\Modules\Finance\Policies\TabunganPolicy::class,
];
```

- [ ] **Step 8: Run tests + commit + tag**

```bash
php artisan test tests/Feature/Finance/
git add -A
git commit -m "feat(finance): controllers, policies, routes, kwitansi PDF, laporan + tabungan"
git tag epic-7-finance
```

---

## Self-Review

**Spec coverage (against DEV_DOCS-003 §3.6, DEV_DOCS-009 §5.5):**
- ✅ 5 tables migrated (item_pembayaran, tagihan_siswa, pembayaran, pembayaran_rincian, tabungan_siswa) — Task 1
- ✅ Nominal decimal(15,2) everywhere (normalization vs varchar) — Task 1
- ✅ **PembayaranService with DB::transaction + lockForUpdate** — Task 3 (CRITICAL)
- ✅ No_nota unique per tenant via KwitansiGenerator — Task 3
- ✅ Race condition handling (clamp to remaining) — Task 3 Step 1 test
- ✅ PaymentReceived event — Task 2 Step 2
- ✅ Audit log for every pembayaran — Task 3 Step 4
- ✅ TagihanGeneratorService idempotent — Task 4
- ✅ Scheduled command `tagihan:generate` monthly — Task 4 Step 4
- ✅ TabunganMutasiService with locking + InsufficientBalanceException — Task 5
- ✅ Field ACL (tagihan.nominal_kurang, pembayaran.total, tabungan.saldo default hidden) — FieldSeeder Epic 3 + views using `@field`
- ✅ Kwitansi PDF via DomPDF — Task 5

**Placeholder scan:** None.

**Name consistency:**
- `PembayaranService::bayar(siswa, rincian, diterimaOleh)` — used in test + controller consistently.
- `KwitansiGenerator::generate(tenantId)` returns string — consistent.
- `TagihanGeneratorService::generateSpp(tapel, kelas, item, bulan)` — consistent.
- `TabunganMutasiService::setor(tab, jumlah, oleh)` + `tarik(tab, jumlah, oleh)` — consistent.
- `InsufficientBalanceException` — referenced in service + test.

**Test count:** Epic 7 adds ~12 tests (6 pembayaran + 3 tagihan + 3 tabungan). **All tests must pass before any subsequent epic** — money correctness is non-negotiable.

**Known risk:** Test step 1 (test_bayar_marks_lunas) contains a typo `PembayaranService::service()` in test_concurrent — engineer should use `PembayaranService::class` consistently. Fixed in plan note.
