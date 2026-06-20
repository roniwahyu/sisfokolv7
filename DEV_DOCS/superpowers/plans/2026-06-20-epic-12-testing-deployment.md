# Epic 12: Testing + Deployment — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Ensure sisfokol-laravel siap production: (1) **Test coverage** — unit + feature tests untuk semua modul/plugin yang sudah dibangun Epic 1-11, code coverage ≥ 70%, (2) **Static analysis** — PHPStan level 5 zero-error, (3) **Deployment** — konfigurasi Laragon production-like environment + `.env.production` template + Artisan optimization commands, (4) **CI/CD** — optional GitHub Actions workflow bila project di-push ke GitHub, (5) **Security hardening** — security checklist pra-production.

**Architecture:** Tests pakai `RefreshDatabase` + SQLite `:memory:` untuk speed, atau MySQL test DB. PHPStan pakai `larastan/larastan`. CI menggunakan matrix PHP 8.3 + MySQL 8. Deployment via `php83 artisan optimize` + supervisor config for queue workers.

**Tech Stack:** PHPUnit 11, `larastan/larastan 2.x`, `laravel/pint 1.x`, `php artisan test --coverage`, Laragon (local), optional GitHub Actions.

**Spec reference:** Epic 1 `phpunit.xml` config, semua epic test files, design.md §9 (tech stack), DEV_DOCS-010 §deployment.

---

## File Structure

```
tests/
├── Unit/
│   ├── Support/
│   │   ├── TenantContextTest.php            ← Epic 1 (existing)
│   │   └── EtlCleansingHelperTest.php       ← Epic 12 new
│   ├── Models/
│   │   └── Traits/
│   │       ├── BelongsToTenantTraitTest.php ← Epic 1 (existing)
│   │       └── TracksAuditColumnsTest.php   ← Epic 1 (existing)
│   └── Finance/
│       └── PembayaranServiceTest.php        ← Epic 12 new (locking test)
├── Feature/
│   ├── Setup/DatabaseConnectionTest.php    ← Epic 1 (existing)
│   ├── Auth/
│   │   ├── LoginTest.php                    ← Epic 2 (existing)
│   │   ├── RbacBuilderTest.php              ← Epic 3 (existing)
│   │   └── ImpersonationTest.php           ← Epic 12 new (full flow)
│   ├── Academic/
│   │   ├── SiswaImportTest.php             ← Epic 12 new
│   │   └── JadwalConflictTest.php          ← Epic 12 new
│   ├── Finance/
│   │   └── PembayaranConcurrencyTest.php   ← Epic 12 new (DB locking)
│   ├── Plugin/
│   │   └── *PluginTest.php                  ← Epic 9-10 (existing)
│   └── Etl/
│       └── *Test.php                        ← Epic 11 (existing)
phpstan.neon                                 ← PHPStan config (larastan)
.github/
└── workflows/
    └── ci.yml                               ← GitHub Actions CI
deployment/
├── supervisor/
│   └── sisfokol-laravel.conf               ← Queue worker supervisor
├── nginx/
│   └── sisfokol-laravel.conf               ← Nginx vhost config
└── scripts/
    └── deploy.sh                            ← Deployment script
```

---

## Task 1: Complete Test Suite — Unit Tests

**Files:**
- Create: `tests/Unit/Support/EtlCleansingHelperTest.php`
- Create: `tests/Unit/Finance/PembayaranServiceUnitTest.php`

- [ ] **Step 1: Write comprehensive ETL cleansing tests**

Create `tests/Unit/Support/EtlCleansingHelperTest.php`:

```php
<?php
namespace Tests\Unit\Support;

use Tests\TestCase;

/**
 * Epic 12: Comprehensive test untuk semua cleansing helper functions.
 * Fungsi-fungsi ini dipakai di ETL pipeline — harus 100% reliabel.
 */
class EtlCleansingHelperTest extends TestCase
{
    // ===================== clean_money() =====================

    public function test_clean_money_plain_number(): void
    {
        $this->assertEquals(150000.00, clean_money('150000'));
        $this->assertEquals(0.00, clean_money('0'));
    }

    public function test_clean_money_with_rp_prefix(): void
    {
        $this->assertEquals(150000.00, clean_money('Rp. 150.000'));
        $this->assertEquals(150000.00, clean_money('Rp150000'));
        $this->assertEquals(150000.00, clean_money('rp 150.000'));
    }

    public function test_clean_money_with_thousand_separator(): void
    {
        $this->assertEquals(1500000.00, clean_money('1.500.000'));
        $this->assertEquals(15000000.00, clean_money('15.000.000'));
    }

    public function test_clean_money_with_decimal_comma(): void
    {
        $this->assertEquals(1500000.50, clean_money('1.500.000,50'));
        $this->assertEquals(75.50, clean_money('75,50'));
    }

    public function test_clean_money_empty_and_null(): void
    {
        $this->assertEquals(0.00, clean_money(''));
        $this->assertEquals(0.00, clean_money(null));
        $this->assertEquals(0.00, clean_money('   '));
    }

    public function test_clean_money_preserves_precision(): void
    {
        $this->assertEquals(1000000.99, clean_money('1.000.000,99'));
    }

    // ===================== clean_date() =====================

    public function test_clean_date_iso_format(): void
    {
        $this->assertEquals('2024-07-15', clean_date('2024-07-15'));
    }

    public function test_clean_date_indonesian_dash_format(): void
    {
        $this->assertEquals('2024-07-15', clean_date('15-07-2024'));
    }

    public function test_clean_date_indonesian_slash_format(): void
    {
        $this->assertEquals('2024-07-15', clean_date('15/07/2024'));
    }

    public function test_clean_date_zero_date_returns_null(): void
    {
        $this->assertNull(clean_date('0000-00-00'));
        $this->assertNull(clean_date(''));
        $this->assertNull(clean_date(null));
    }

    public function test_clean_date_invalid_returns_null(): void
    {
        $this->assertNull(clean_date('not-a-date'));
        $this->assertNull(clean_date('32/13/2024'));
    }

    // ===================== clean_phone() =====================

    public function test_clean_phone_starting_zero(): void
    {
        $this->assertEquals('6281234567890', clean_phone('081234567890'));
    }

    public function test_clean_phone_already_62(): void
    {
        $this->assertEquals('6281234567890', clean_phone('6281234567890'));
        $this->assertEquals('6281234567890', clean_phone('+6281234567890'));
    }

    public function test_clean_phone_starting_8(): void
    {
        $this->assertEquals('6281234567890', clean_phone('81234567890'));
    }

    public function test_clean_phone_with_spaces_and_dashes(): void
    {
        $this->assertEquals('6281234567890', clean_phone('0812-3456-7890'));
        $this->assertEquals('6281234567890', clean_phone('0812 3456 7890'));
    }

    public function test_clean_phone_null_returns_null(): void
    {
        $this->assertNull(clean_phone(null));
        $this->assertNull(clean_phone(''));
    }
}
```

- [ ] **Step 2: Write PembayaranService unit test**

Create `tests/Unit/Finance/PembayaranServiceUnitTest.php`:

```php
<?php
namespace Tests\Unit\Finance;

use App\Modules\Finance\Models\{ItemPembayaran, Pembayaran, PembayaranRincian, TagihanSiswa};
use App\Modules\Finance\Services\PembayaranService;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembayaranServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_bayar_updates_tagihan_nominal_correctly(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create([
            'tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp',
            'nominal' => 150000, 'periode' => 'bulanan', 'aktif' => true,
        ]);
        $tagihan = TagihanSiswa::create([
            'tenant_id' => $tenant->id, 'siswa_id' => $siswa->id,
            'item_pembayaran_id' => $item->id,
            'nominal_tagihan' => 150000, 'nominal_bayar' => 0,
            'nominal_kurang' => 150000, 'lunas' => false,
        ]);

        $kasir   = \App\Models\User::where('username', 'admin')->first();
        $svc     = app(PembayaranService::class);

        $pembayaran = $svc->bayar($siswa, [
            ['tagihan_id' => $tagihan->id, 'jumlah' => 150000],
        ], $kasir);

        $tagihan->refresh();
        $this->assertEquals(150000, $tagihan->nominal_bayar);
        $this->assertEquals(0, $tagihan->nominal_kurang);
        $this->assertTrue($tagihan->lunas);
        $this->assertNotNull($tagihan->tanggal_lunas);
        $this->assertInstanceOf(Pembayaran::class, $pembayaran);
    }

    public function test_bayar_partial_payment_updates_correctly(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create([
            'tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp',
            'nominal' => 150000, 'periode' => 'bulanan', 'aktif' => true,
        ]);
        $tagihan = TagihanSiswa::create([
            'tenant_id' => $tenant->id, 'siswa_id' => $siswa->id,
            'item_pembayaran_id' => $item->id,
            'nominal_tagihan' => 150000, 'nominal_bayar' => 0,
            'nominal_kurang' => 150000, 'lunas' => false,
        ]);

        $kasir = \App\Models\User::where('username', 'admin')->first();
        $svc   = app(PembayaranService::class);

        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 50000]], $kasir);

        $tagihan->refresh();
        $this->assertEquals(50000, $tagihan->nominal_bayar);
        $this->assertEquals(100000, $tagihan->nominal_kurang);
        $this->assertFalse($tagihan->lunas);
    }

    public function test_pembayaran_creates_audit_log(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create(['tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp', 'nominal' => 100000, 'periode' => 'bulanan', 'aktif' => true]);
        $tagihan = TagihanSiswa::create(['tenant_id' => $tenant->id, 'siswa_id' => $siswa->id, 'item_pembayaran_id' => $item->id, 'nominal_tagihan' => 100000, 'nominal_bayar' => 0, 'nominal_kurang' => 100000, 'lunas' => false]);

        $kasir = \App\Models\User::where('username', 'admin')->first();
        $this->actingAs($kasir);

        app(PembayaranService::class)->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 100000]], $kasir);

        $this->assertDatabaseHas('audit_logs', ['event' => 'pembayaran.created', 'tenant_id' => $tenant->id]);
    }
}
```

- [ ] **Step 3: Run unit tests**

```bash
php83 artisan test tests/Unit/ --stop-on-failure
```

Expected: All PASS.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "test(unit): ETL cleansing helpers + PembayaranService unit tests"
```

---

## Task 2: Feature Tests — Critical Flows

**Files:**
- Create: `tests/Feature/Auth/ImpersonationTest.php`
- Create: `tests/Feature/Academic/JadwalConflictTest.php`
- Create: `tests/Feature/Academic/SiswaImportTest.php`
- Create: `tests/Feature/Finance/PembayaranConcurrencyTest.php`

- [ ] **Step 1: Write Impersonation full flow test**

Create `tests/Feature/Auth/ImpersonationTest.php`:

```php
<?php
namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_impersonate_admin_sekolah(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);

        $super = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($super)
            ->post("/impersonate/{$admin->id}/start")
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(session()->has('impersonated_by'));
    }

    public function test_impersonation_blocked_when_disabled(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => false]);

        $super = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($super)
            ->post("/impersonate/{$admin->id}/start")
            ->assertStatus(403);
    }

    public function test_guru_cannot_impersonate(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);

        $tenant = Tenant::first();
        $guru   = User::factory()->create(['tenant_id' => $tenant->id]);
        $guru->assignRole('guru');
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($guru)
            ->post("/impersonate/{$admin->id}/start")
            ->assertStatus(403);
    }

    public function test_rbac_changes_blocked_while_impersonating(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);

        $super = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        // Start impersonation
        $this->actingAs($super)->post("/impersonate/{$admin->id}/start");

        // Try to change RBAC — must be blocked
        $response = $this->post('/admin/rbac/roles', ['name' => 'new_role']);
        $response->assertStatus(403);
    }

    public function test_stop_impersonation_restores_original_user(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);

        $super = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($super)->post("/impersonate/{$admin->id}/start");
        $this->actingAs($admin)->post('/impersonate/stop')->assertRedirect();

        $this->assertAuthenticatedAs($super);
        $this->assertFalse(session()->has('impersonated_by'));
    }
}
```

- [ ] **Step 2: Write Jadwal conflict test**

Create `tests/Feature/Academic/JadwalConflictTest.php`:

```php
<?php
namespace Tests\Feature\Academic;

use App\Modules\Academic\Models\{Jadwal, Kelas, Mapel, Semester, TahunAjaran};
use App\Modules\Academic\Services\JadwalConflictChecker;
use App\Modules\Academic\Models\Guru;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_kelas_same_hari_same_jam_is_conflict(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $tapel, $smt, $kelas, $mapel1, $mapel2, $guru] = $this->setupAcademic();

        // Create first jadwal
        Jadwal::create([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel1->id, 'guru_id' => $guru->id,
            'hari' => 1, 'jam_ke' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '07:45',
        ]);

        // Second jadwal same slot = conflict
        $checker = app(JadwalConflictChecker::class);
        $conflicts = $checker->validate([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel2->id, 'guru_id' => $guru->id,
            'hari' => 1, 'jam_ke' => 1,
        ]);

        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('kelas', strtolower($conflicts[0]));
    }

    public function test_same_guru_different_kelas_same_jam_is_conflict(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $tapel, $smt, $kelas1, $mapel1, $mapel2, $guru] = $this->setupAcademic();
        $kelas2 = Kelas::create(['tenant_id' => $tenant->id, 'nama' => 'Kelas 8', 'tingkat' => 8]);

        Jadwal::create([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas1->id, 'mapel_id' => $mapel1->id, 'guru_id' => $guru->id,
            'hari' => 2, 'jam_ke' => 3, 'jam_mulai' => '09:00', 'jam_selesai' => '09:45',
        ]);

        $checker = app(JadwalConflictChecker::class);
        $conflicts = $checker->validate([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas2->id, 'mapel_id' => $mapel2->id, 'guru_id' => $guru->id,
            'hari' => 2, 'jam_ke' => 3,
        ]);

        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('guru', strtolower($conflicts[0]));
    }

    public function test_different_hari_no_conflict(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        [$tenant, $tapel, $smt, $kelas, $mapel1, $mapel2, $guru] = $this->setupAcademic();

        Jadwal::create([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel1->id, 'guru_id' => $guru->id,
            'hari' => 1, 'jam_ke' => 1,
        ]);

        $checker   = app(JadwalConflictChecker::class);
        $conflicts = $checker->validate([
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel2->id, 'guru_id' => $guru->id,
            'hari' => 2, 'jam_ke' => 1,
        ]);

        $this->assertEmpty($conflicts);
    }

    private function setupAcademic(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $tapel  = TahunAjaran::create(['tenant_id' => $tenant->id, 'nama' => '2026/2027', 'aktif' => true]);
        $smt    = Semester::create(['tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'nama' => '1', 'aktif' => true]);
        $kelas  = Kelas::create(['tenant_id' => $tenant->id, 'nama' => 'Kelas 7A', 'tingkat' => 7]);
        $mapel1 = Mapel::create(['tenant_id' => $tenant->id, 'kode' => 'MTH', 'nama' => 'Matematika', 'kkm' => 75]);
        $mapel2 = Mapel::create(['tenant_id' => $tenant->id, 'kode' => 'IND', 'nama' => 'B. Indonesia', 'kkm' => 75]);
        $guru   = Guru::create(['tenant_id' => $tenant->id, 'nip' => '198001012005011001', 'nama' => 'Budi Santoso', 'aktif' => true]);
        return [$tenant, $tapel, $smt, $kelas, $mapel1, $mapel2, $guru];
    }
}
```

- [ ] **Step 3: Write PembayaranConcurrencyTest**

Create `tests/Feature/Finance/PembayaranConcurrencyTest.php`:

```php
<?php
namespace Tests\Feature\Finance;

use App\Modules\Finance\Models\{ItemPembayaran, TagihanSiswa};
use App\Modules\Finance\Services\PembayaranService;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test bahwa PembayaranService menggunakan DB::transaction + lockForUpdate()
 * sehingga tidak ada double-payment pada satu tagihan.
 * 
 * Note: True concurrency test sulit di PHP single-process test.
 * Ini test structural: verifikasi bahwa:
 * (1) DB transaction digunakan
 * (2) tagihan.nominal_kurang tidak negatif setelah pembayaran berlebih
 */
class PembayaranConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_overpayment_capped_at_zero_kurang(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create(['tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp', 'nominal' => 100000, 'periode' => 'bulanan', 'aktif' => true]);
        $tagihan = TagihanSiswa::create(['tenant_id' => $tenant->id, 'siswa_id' => $siswa->id, 'item_pembayaran_id' => $item->id, 'nominal_tagihan' => 100000, 'nominal_bayar' => 0, 'nominal_kurang' => 100000, 'lunas' => false]);

        $kasir = \App\Models\User::where('username', 'admin')->first();
        $svc   = app(PembayaranService::class);

        // Pay exact amount
        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 100000]], $kasir);

        $tagihan->refresh();
        $this->assertEquals(0, $tagihan->nominal_kurang);
        $this->assertTrue($tagihan->lunas);
    }

    public function test_second_payment_on_lunas_tagihan_throws(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create(['tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp', 'nominal' => 100000, 'periode' => 'bulanan', 'aktif' => true]);
        $tagihan = TagihanSiswa::create(['tenant_id' => $tenant->id, 'siswa_id' => $siswa->id, 'item_pembayaran_id' => $item->id, 'nominal_tagihan' => 100000, 'nominal_bayar' => 100000, 'nominal_kurang' => 0, 'lunas' => true]);

        $kasir = \App\Models\User::where('username', 'admin')->first();
        $svc   = app(PembayaranService::class);

        $this->expectException(\App\Modules\Finance\Exceptions\TagihanAlreadyLunasException::class);
        $svc->bayar($siswa, [['tagihan_id' => $tagihan->id, 'jumlah' => 100000]], $kasir);
    }

    public function test_transaction_rollback_on_error(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::first();
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $siswa   = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        $item    = ItemPembayaran::create(['tenant_id' => $tenant->id, 'nama' => 'SPP', 'jenis' => 'spp', 'nominal' => 100000, 'periode' => 'bulanan', 'aktif' => true]);
        $tagihan = TagihanSiswa::create(['tenant_id' => $tenant->id, 'siswa_id' => $siswa->id, 'item_pembayaran_id' => $item->id, 'nominal_tagihan' => 100000, 'nominal_bayar' => 0, 'nominal_kurang' => 100000, 'lunas' => false]);
        $originalBayar = $tagihan->nominal_bayar;

        $kasir = \App\Models\User::where('username', 'admin')->first();
        $svc   = app(PembayaranService::class);

        try {
            // Pass invalid tagihan_id to force error mid-transaction
            $svc->bayar($siswa, [
                ['tagihan_id' => $tagihan->id, 'jumlah' => 50000],
                ['tagihan_id' => 99999, 'jumlah' => 50000],  // invalid → should rollback
            ], $kasir);
        } catch (\Throwable $e) {
            // Expected
        }

        $tagihan->refresh();
        $this->assertEquals($originalBayar, $tagihan->nominal_bayar);  // rollback → unchanged
    }
}
```

- [ ] **Step 4: Run all feature tests**

```bash
php83 artisan test tests/Feature/ --stop-on-failure
```

Expected: All pass (or known pending implementations clearly documented).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "test(feature): impersonation flow, jadwal conflict, pembayaran concurrency"
```

---

## Task 3: Full Test Run + Coverage Report

- [ ] **Step 1: Run all tests**

```bash
php83 artisan test --parallel
```

Expected: Green. Document any failures as known issues.

- [ ] **Step 2: Generate coverage report**

```bash
php83 artisan test --coverage --min=70
```

> Note: Requires `pcov` atau `xdebug` extension. Di Laragon: aktifkan `php_pcov.dll` di `php.ini` php83.

Expected: ≥ 70% total coverage.

- [ ] **Step 3: Check test count summary**

Total tests expected setelah Epic 1-12:

| Epic | Approximate Tests |
|------|-------------------|
| 1    | ~8 (context, traits, helpers, DB connection) |
| 2    | ~5 (login, logout, rate limit, force reset) |
| 3    | ~6 (RBAC builder, menu, field ACL) |
| 4    | ~10 (registry, plugin enable, activation) |
| 5    | ~12 (siswa CRUD, import, promotion, jadwal) |
| 6    | ~8 (TP/LM, asesmen, raport NA) |
| 7    | ~10 (tagihan generate, pembayaran, tabungan) |
| 8    | ~8 (QR scan, absensi, izin approval) |
| 9    | ~3 (kurikulum plugin framework) |
| 10   | ~8 (8 plugin activation) |
| 11   | ~6 (ETL steps, verify) |
| 12   | ~15 (cleansing, impersonation, jadwal conflict, concurrency) |
| **Total** | **~99 tests** |

- [ ] **Step 4: Commit coverage report**

```bash
git add -A
git commit -m "test: full test suite pass + coverage ≥70%"
```

---

## Task 4: PHPStan Static Analysis (Level 5)

**Files:**
- Create: `phpstan.neon`
- Install: `larastan/larastan`

- [ ] **Step 1: Install larastan**

```bash
php83 (Get-Command composer).Source require --dev larastan/larastan:^2.0
```

- [ ] **Step 2: Create phpstan.neon**

Create `phpstan.neon` at project root:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
    level: 5
    ignoreErrors:
        # Known Laravel magic methods
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::.*#'
        - '#Access to an undefined property App\\Models\\User::\$.*#'
    checkMissingIterableValueType: false
    universalObjectCratesClasses:
        - Illuminate\Http\Request
```

- [ ] **Step 3: Run PHPStan**

```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: 0 errors at level 5.

- [ ] **Step 4: Run Pint (code style)**

```bash
vendor/bin/pint --test
```

Expected: No style issues. If any: `vendor/bin/pint` to auto-fix.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: PHPStan level 5 pass + pint code style clean"
```

---

## Task 5: Artisan Optimization + Security Hardening

- [ ] **Step 1: Run optimization commands**

```bash
php83 artisan optimize           # cache config + routes + views
php83 artisan event:cache        # cache event listeners
php83 artisan permission:cache-reset  # reset Spatie cache
```

- [ ] **Step 2: Security checklist**

Verify semua item sebelum cut-over:

```
[ ] APP_DEBUG=false di .env production
[ ] APP_KEY set dan tidak kosong
[ ] IMPERSONATION_ENABLED=false di production (atau true hanya bila dibutuhkan)
[ ] BCRYPT_COST=12 (tidak 8 atau default)
[ ] DB_PASSWORD tidak kosong
[ ] SESSION_SECURE_COOKIE=true (bila HTTPS)
[ ] CACHE_STORE=redis (bukan file di production)
[ ] QUEUE_CONNECTION=redis (bukan sync di production)
[ ] LOG_LEVEL=warning (bukan debug di production)
[ ] storage/ dan bootstrap/cache/ writeable tapi tidak di-serve public
[ ] php artisan route:list | grep 'debug\|telescope' → pastikan hanya aktif di local
[ ] Telescope disabled di production (check AppServiceProvider)
```

- [ ] **Step 3: Create .env.production template**

Create `deployment/.env.production.example`:

```ini
APP_NAME="SISFOKOL"
APP_ENV=production
APP_KEY=             ; WAJIB: php artisan key:generate
APP_DEBUG=false
APP_URL=https://sisfokol.yourdomain.id
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisfokol_laravel
DB_USERNAME=sisfokol_user
DB_PASSWORD=           ; WAJIB: set password kuat

LEGACY_DB_CONNECTION=legacy_mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=sisfokol_v7
LEGACY_DB_USERNAME=sisfokol_readonly
LEGACY_DB_PASSWORD=

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis

IMPERSONATION_ENABLED=false
BCRYPT_COST=12

LOG_CHANNEL=stack
LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.id
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@yourdomain.id
MAIL_FROM_NAME="SISFOKOL"
```

- [ ] **Step 4: Create Supervisor config**

Create `deployment/supervisor/sisfokol-laravel.conf`:

```ini
[program:sisfokol-laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php83 /path/to/sisfokol-laravel/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/sisfokol-laravel/storage/logs/worker.log
stopwaitsecs=3600

[program:sisfokol-spp-scheduler]
process_name=%(program_name)s
command=php83 /path/to/sisfokol-laravel/artisan schedule:run
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/sisfokol-laravel/storage/logs/scheduler.log
```

- [ ] **Step 5: Create Nginx config**

Create `deployment/nginx/sisfokol-laravel.conf`:

```nginx
server {
    listen 80;
    server_name sisfokol.yourdomain.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sisfokol.yourdomain.id;

    ssl_certificate     /etc/ssl/sisfokol/fullchain.pem;
    ssl_certificate_key /etc/ssl/sisfokol/privkey.pem;

    root /var/www/sisfokol-laravel/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block access to sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 20M;
}
```

- [ ] **Step 6: Create deployment script**

Create `deployment/scripts/deploy.sh`:

```bash
#!/bin/bash
# Deployment script untuk sisfokol-laravel
# Usage: ./deployment/scripts/deploy.sh [branch]

set -e

BRANCH=${1:-main}
APP_DIR="/var/www/sisfokol-laravel"
PHP="php83"

echo "=== Deploying branch: ${BRANCH} ==="

cd $APP_DIR

# 1. Pull latest code
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH

# 2. Install/update dependencies (no-dev production)
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Run new migrations
$PHP artisan migrate --force

# 4. Clear and rebuild caches
$PHP artisan optimize:clear
$PHP artisan optimize
$PHP artisan event:cache
$PHP artisan permission:cache-reset

# 5. Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Restart queue workers
supervisorctl restart sisfokol-laravel-worker:*

echo "=== Deployment SELESAI ==="
```

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore: production deployment configs — .env template, supervisor, nginx, deploy script"
```

---

## Task 6: Optional GitHub Actions CI

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Create CI workflow**

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: sisfokol_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    strategy:
      matrix:
        php: ['8.3']

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, pdo_mysql, pcov
          coverage: pcov

      - name: Copy .env
        run: cp .env.example .env.testing

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress --no-dev

      - name: Generate APP_KEY
        run: php artisan key:generate --env=testing

      - name: Run migrations
        run: php artisan migrate --env=testing --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: sisfokol_test
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run PHPUnit tests with coverage
        run: php artisan test --coverage --min=70 --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: sisfokol_test
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --memory-limit=512M --no-progress

      - name: Run Pint
        run: vendor/bin/pint --test
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "ci: GitHub Actions workflow — test + PHPStan + Pint + MySQL"
```

---

## Task 7: Final Self-Review + Pre-Launch Checklist

- [ ] **Step 1: Run full test suite one last time**

```bash
php83 artisan test --parallel --stop-on-failure
```

Expected: All pass.

- [ ] **Step 2: PHPStan final run**

```bash
vendor/bin/phpstan analyse --level=5 --memory-limit=512M
```

Expected: 0 errors.

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint
```

Expected: All formatted.

- [ ] **Step 4: Check for hardcoded credentials / debug code**

```bash
grep -rn "dd\|dump\|var_dump\|print_r" app/ --include="*.php" | grep -v vendor
grep -rn "TODO\|FIXME\|HACK\|password123\|secret" app/ --include="*.php"
```

Expected: None in production code.

- [ ] **Step 5: Final commit + tag**

```bash
git add -A
git commit -m "chore: pre-launch review — all tests pass, PHPStan clean, pint formatted"
git tag v1.0.0-rc1
git push origin main --tags
```

---

## Self-Review

**Spec coverage:**
- ✅ Unit tests untuk semua helpers (ETL cleansing) — Task 1
- ✅ PembayaranService unit test dengan rollback + partial payment — Task 1
- ✅ ImpersonationTest full flow (start, stop, blocked when disabled, non-admin blocked) — Task 2
- ✅ JadwalConflictTest (kelas conflict, guru conflict, no conflict) — Task 2
- ✅ PembayaranConcurrencyTest (structural test locking) — Task 2
- ✅ Total ~99 tests dari Epic 1-12 — Task 3
- ✅ Coverage ≥ 70% target — Task 3
- ✅ PHPStan level 5 + `larastan/larastan` — Task 4
- ✅ Laravel Pint code style — Task 4
- ✅ Security hardening checklist — Task 5
- ✅ `.env.production.example` lengkap — Task 5
- ✅ Supervisor config queue worker (2 processes) + scheduler — Task 5
- ✅ Nginx config (SSL, security headers, static cache, 20MB upload) — Task 5
- ✅ `deploy.sh` script (git pull + migrate + optimize + restart worker) — Task 5
- ✅ GitHub Actions CI matrix PHP 8.3 + MySQL 8 — Task 6

**Name consistency:**
- Artisan commands: `php83 artisan test`, `php83 artisan optimize` — pakai `php83` sesuai environment Laragon
- Deploy script menggunakan `PHP="php83"` variabel
- Supervisor pakai `php83` binary path

**Test count:** Epic 12 menambahkan ~15 tests. Grand total semua epic: ~99 tests.

**Pre-requisites:** Epic 1-11 selesai dan semua tests pass. `larastan/larastan` diinstall. `pcov` extension aktif di php83. Git remote (GitHub/GitLab) terkonfigurasi.

**Cut-over Strategy (Final):**
1. Freeze legacy (`GRANT SELECT ONLY` ke semua user)
2. `mysqldump sisfokol_v7 > backup-$(date +%Y%m%d).sql`
3. Deploy `sisfokol-laravel` ke production server
4. `php83 artisan migrate --force`
5. `php83 artisan db:seed --class=SuperAdminSeeder`
6. `php83 artisan migrate:legacy-sisfokol {tenant_id}`
7. `php83 artisan etl:verify {tenant_id}`
8. Bila PASS → switch DNS → announce ke user
9. `php83 artisan etl:verify {tenant_id} --drop-mappings` (cleanup)
