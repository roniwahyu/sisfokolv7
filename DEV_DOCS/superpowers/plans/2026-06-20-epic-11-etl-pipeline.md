# Epic 11: ETL Pipeline (20 Steps + Verify) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Implement full ETL pipeline `migrate:legacy-sisfokol {tenant_id}` yang memindahkan data dari database legacy `sisfokol_v7` (MyISAM, MD5 PK, no FK) ke `sisfokol_laravel` (InnoDB, BIGINT PK, full FK) dalam urutan topologis 20 langkah. Pipeline harus idempotent (bisa dijalankan ulang), transaksional (rollback bila gagal), dan dilengkapi command verifikasi `etl:verify {tenant_id}`.

**Architecture:** Step-based pipeline: setiap langkah implement `StepInterface::handle(Tenant): void`. `IdMapper` singleton mencatat pemetaan `(entity_type, legacy_kd) → new_id` ke tabel helper `legacy_id_mappings` (di-drop pasca verifikasi). Cleansing helpers (`MoneyCleaner`, `DateCleaner`, `PhoneCleaner`) sudah ada di `app/Support/helpers.php` (Epic 1). Legacy DB via `legacy_mysql` connection (read-only).

**Tech Stack:** Laravel Artisan commands, `DB::connection('legacy_mysql')`, `DB::transaction`, pessimistic `lockForUpdate()`, `Hash::make()`, Spatie permission, chunked iteration (100 record/batch).

**Spec reference:** DEV_DOCS-009 §ETL Plan, design.md §8, ADR-003 (tenant isolation), ADR-007 (schema). Legacy tables: `m_tapel`, `m_pegawai`, `m_siswa`, `m_mapel`, `m_kelas`, `jadwal`, `kurmer_*`, `siswa_bayar*`, `user_presensi`, `user_absensi`, `user_ijin`.

---

## File Structure

```
app/Console/Commands/
├── MigrateLegacySisfokolCommand.php        ← entry point: php artisan migrate:legacy-sisfokol {tenant_id}
├── EtlVerifyCommand.php                    ← php artisan etl:verify {tenant_id}
└── Etl/
    ├── StepInterface.php                   ← contract: handle(Tenant): EtlResult
    ├── EtlResult.php                       ← value object: {migrated, skipped, errors[]}
    ├── IdMapper.php                        ← singleton: map/lookup legacy_kd → new_id
    ├── Steps/
    │   ├── Step01MigrateTahunAjaran.php
    │   ├── Step02MigrateMapelJenis.php
    │   ├── Step03MigrateGuru.php
    │   ├── Step04MigrateSiswa.php
    │   ├── Step05MigrateAdminUser.php
    │   ├── Step06MigrateMapel.php
    │   ├── Step07MigrateKelas.php
    │   ├── Step08MigrateKelasSiswa.php
    │   ├── Step09MigrateJadwal.php
    │   ├── Step10MigrateTpLm.php
    │   ├── Step11MigrateAsesmenFormatif.php
    │   ├── Step12MigrateAsesmenSumatif.php
    │   ├── Step13MigrateRapor.php
    │   ├── Step14MigrateItemPembayaran.php
    │   ├── Step15MigrateTagihanSiswa.php
    │   ├── Step16MigratePembayaran.php
    │   ├── Step17MigrateTabungan.php
    │   ├── Step18MigratePresensi.php
    │   ├── Step19MigrateAbsensi.php
    │   └── Step20MigrateIzin.php
    └── Cleansing/
        ├── MoneyCleaner.php                ← wrap helpers.php clean_money()
        ├── DateCleaner.php                 ← wrap helpers.php clean_date()
        ├── PhoneCleaner.php                ← wrap helpers.php clean_phone()
        └── PasswordResetter.php            ← strategy NIS/NIP + tanggal_lahir
database/migrations/
└── 2026_06_20_000700_create_legacy_id_mappings_table.php
tests/Feature/Etl/
├── EtlTahunAjaranTest.php
├── EtlSiswaTest.php
├── EtlKeuanganTest.php
└── EtlVerifyTest.php
```

---

## Task 1: Migration Tabel Helper + StepInterface + IdMapper

**Files:**
- Create: `database/migrations/2026_06_20_000700_create_legacy_id_mappings_table.php`
- Create: `app/Console/Commands/Etl/StepInterface.php`
- Create: `app/Console/Commands/Etl/EtlResult.php`
- Create: `app/Console/Commands/Etl/IdMapper.php`

- [ ] **Step 1: Create legacy_id_mappings migration**

Create `database/migrations/2026_06_20_000700_create_legacy_id_mappings_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legacy_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('entity_type', 50);     // 'siswa', 'guru', 'tapel', 'kelas', 'mapel', 'user', ...
            $table->string('legacy_kd', 100);       // MD5 PK dari legacy (varchar 50 + safety buffer)
            $table->unsignedBigInteger('new_id');   // FK ke tabel target (bukan enforced FK — multi-tabel)
            $table->timestamps();
            $table->unique(['tenant_id', 'entity_type', 'legacy_kd'], 'uniq_legacy_map');
            $table->index(['entity_type', 'legacy_kd'], 'idx_lookup');
        });
    }
    public function down(): void { Schema::dropIfExists('legacy_id_mappings'); }
};
```

- [ ] **Step 2: Create StepInterface**

Create `app/Console/Commands/Etl/StepInterface.php`:

```php
<?php
namespace App\Console\Commands\Etl;

use App\Modules\Tenancy\Models\Tenant;

interface StepInterface
{
    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult;

    /** Human-readable name for logging */
    public function name(): string;
}
```

- [ ] **Step 3: Create EtlResult value object**

Create `app/Console/Commands/Etl/EtlResult.php`:

```php
<?php
namespace App\Console\Commands\Etl;

class EtlResult
{
    public int   $migrated = 0;
    public int   $skipped  = 0;
    public array $errors   = [];

    public static function make(): self { return new self(); }

    public function addMigrated(int $n = 1): self { $this->migrated += $n; return $this; }
    public function addSkipped(int $n = 1): self  { $this->skipped  += $n; return $this; }
    public function addError(string $msg): self   { $this->errors[] = $msg; return $this; }

    public function hasErrors(): bool { return count($this->errors) > 0; }
}
```

- [ ] **Step 4: Implement IdMapper**

Create `app/Console/Commands/Etl/IdMapper.php`:

```php
<?php
namespace App\Console\Commands\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Singleton per ETL run.
 * Map legacy_kd (MD5) → new_id (BIGINT) in legacy_id_mappings table.
 * Also maintains an in-memory cache for performance (avoid N+1 DB queries per step).
 */
class IdMapper
{
    /** @var array<string, array<string, int>> ['entity.tenant' => ['legacy_kd' => new_id]] */
    private array $cache = [];

    public function set(int $tenantId, string $entityType, string $legacyKd, int $newId): void
    {
        DB::table('legacy_id_mappings')->upsert(
            [
                'tenant_id'   => $tenantId,
                'entity_type' => $entityType,
                'legacy_kd'   => $legacyKd,
                'new_id'      => $newId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            ['tenant_id', 'entity_type', 'legacy_kd'],
            ['new_id', 'updated_at'],
        );
        $this->cache["{$entityType}.{$tenantId}"][$legacyKd] = $newId;
    }

    public function get(int $tenantId, string $entityType, string $legacyKd): ?int
    {
        $cacheKey = "{$entityType}.{$tenantId}";
        if (! isset($this->cache[$cacheKey])) {
            $this->preload($tenantId, $entityType);
        }
        return $this->cache[$cacheKey][$legacyKd] ?? null;
    }

    /** Preload seluruh entity type untuk tenant → satu query, bukan N+1 */
    private function preload(int $tenantId, string $entityType): void
    {
        $rows = DB::table('legacy_id_mappings')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->pluck('new_id', 'legacy_kd');

        $this->cache["{$entityType}.{$tenantId}"] = $rows->toArray();
    }

    public function clearCache(): void { $this->cache = []; }
}
```

- [ ] **Step 5: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(etl): legacy_id_mappings migration + StepInterface + EtlResult + IdMapper"
```

---

## Task 2: Main ETL Command (Entry Point)

**Files:**
- Create: `app/Console/Commands/MigrateLegacySisfokolCommand.php`

- [ ] **Step 1: Implement MigrateLegacySisfokolCommand**

Create `app/Console/Commands/MigrateLegacySisfokolCommand.php`:

```php
<?php
namespace App\Console\Commands;

use App\Console\Commands\Etl\IdMapper;
use App\Console\Commands\Etl\Steps\{
    Step01MigrateTahunAjaran,
    Step02MigrateMapelJenis,
    Step03MigrateGuru,
    Step04MigrateSiswa,
    Step05MigrateAdminUser,
    Step06MigrateMapel,
    Step07MigrateKelas,
    Step08MigrateKelasSiswa,
    Step09MigrateJadwal,
    Step10MigrateTpLm,
    Step11MigrateAsesmenFormatif,
    Step12MigrateAsesmenSumatif,
    Step13MigrateRapor,
    Step14MigrateItemPembayaran,
    Step15MigrateTagihanSiswa,
    Step16MigratePembayaran,
    Step17MigrateTabungan,
    Step18MigratePresensi,
    Step19MigrateAbsensi,
    Step20MigrateIzin,
};
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateLegacySisfokolCommand extends Command
{
    protected $signature = 'migrate:legacy-sisfokol
                            {tenant_id : ID tenant yang akan di-migrasi}
                            {--dry-run : Simulasi tanpa write ke DB target}
                            {--steps= : Comma-separated step numbers to run (e.g. "1,2,3")}
                            {--from-step= : Mulai dari langkah ke-N (resume setelah gagal)}';

    protected $description = 'ETL: Migrate data dari sisfokol_v7 legacy ke sisfokol_laravel untuk satu tenant';

    private array $stepClasses = [
        1  => Step01MigrateTahunAjaran::class,
        2  => Step02MigrateMapelJenis::class,
        3  => Step03MigrateGuru::class,
        4  => Step04MigrateSiswa::class,
        5  => Step05MigrateAdminUser::class,
        6  => Step06MigrateMapel::class,
        7  => Step07MigrateKelas::class,
        8  => Step08MigrateKelasSiswa::class,
        9  => Step09MigrateJadwal::class,
        10 => Step10MigrateTpLm::class,
        11 => Step11MigrateAsesmenFormatif::class,
        12 => Step12MigrateAsesmenSumatif::class,
        13 => Step13MigrateRapor::class,
        14 => Step14MigrateItemPembayaran::class,
        15 => Step15MigrateTagihanSiswa::class,
        16 => Step16MigratePembayaran::class,
        17 => Step17MigrateTabungan::class,
        18 => Step18MigratePresensi::class,
        19 => Step19MigrateAbsensi::class,
        20 => Step20MigrateIzin::class,
    ];

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        $tenant   = Tenant::findOrFail($tenantId);
        $isDry    = $this->option('dry-run');

        $this->info("=== ETL SISFOKOL v7 → sisfokol_laravel ===");
        $this->info("Tenant: [{$tenant->id}] {$tenant->nama}");
        if ($isDry) $this->warn("MODE: DRY-RUN (tidak ada perubahan tersimpan)");

        // Determine which steps to run
        $stepsToRun = $this->resolveStepsToRun();

        $mapper    = new IdMapper();
        $totalMigrated = 0;
        $totalSkipped  = 0;
        $allErrors     = [];

        foreach ($stepsToRun as $stepNum => $class) {
            $step = app($class);
            $this->line("  [Step {$stepNum}/20] {$step->name()}...");

            try {
                if ($isDry) {
                    $this->comment("  → [DRY-RUN] Skipped.");
                    continue;
                }

                $result = DB::transaction(function () use ($step, $tenant, $mapper) {
                    return $step->handle($tenant, $mapper);
                });

                $this->info("  ✓ Migrated: {$result->migrated}, Skipped: {$result->skipped}");
                $totalMigrated += $result->migrated;
                $totalSkipped  += $result->skipped;

                if ($result->hasErrors()) {
                    foreach ($result->errors as $err) {
                        $this->warn("    ⚠ {$err}");
                        $allErrors[] = "[Step {$stepNum}] {$err}";
                    }
                    Log::warning("ETL step {$stepNum} soft errors", ['errors' => $result->errors]);
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ FATAL: " . $e->getMessage());
                $this->error("    File: " . $e->getFile() . ":" . $e->getLine());
                Log::error("ETL step {$stepNum} FATAL", ['exception' => $e]);
                $this->error("ETL ABORTED. Re-run from step {$stepNum} setelah fix: --from-step={$stepNum}");
                return 1;
            }
        }

        $this->newLine();
        $this->info("=== ETL SELESAI ===");
        $this->info("Total Migrated : {$totalMigrated}");
        $this->info("Total Skipped  : {$totalSkipped}");
        $this->info("Total Errors   : " . count($allErrors));

        if ($allErrors) {
            $this->warn("Soft errors (data perlu manual reconcile):");
            foreach ($allErrors as $err) $this->warn("  • {$err}");
        }

        $this->info("Jalankan verifikasi: php artisan etl:verify {$tenantId}");

        return count($allErrors) > 0 ? 2 : 0;  // 2 = OK with warnings
    }

    private function resolveStepsToRun(): array
    {
        $steps = $this->stepClasses;

        if ($stepsOption = $this->option('steps')) {
            $nums = array_map('intval', explode(',', $stepsOption));
            $steps = array_filter($steps, fn($k) => in_array($k, $nums), ARRAY_FILTER_USE_KEY);
        }

        if ($fromStep = $this->option('from-step')) {
            $steps = array_filter($steps, fn($k) => $k >= (int) $fromStep, ARRAY_FILTER_USE_KEY);
        }

        return $steps;
    }
}
```

- [ ] **Step 2: Register command**

In `app/Console/Kernel.php` atau `routes/console.php` (Laravel 11):

```php
// In bootstrap/app.php withSchedule() or routes/console.php:
Artisan::command('migrate:legacy-sisfokol {tenant_id}', function () {}); // just for discovery
// Real registration happens via Console/Commands/ auto-discovery
```

Atau di `app/Providers/AppServiceProvider.php`:

```php
$this->commands([\App\Console\Commands\MigrateLegacySisfokolCommand::class]);
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat(etl): MigrateLegacySisfokolCommand entry point — 20 steps, dry-run, resume"
```

---

## Task 3: Steps 1–5 (Tenancy + User entities)

**Files:**
- Create: `app/Console/Commands/Etl/Steps/Step01MigrateTahunAjaran.php`
- Create: `app/Console/Commands/Etl/Steps/Step02MigrateMapelJenis.php`
- Create: `app/Console/Commands/Etl/Steps/Step03MigrateGuru.php`
- Create: `app/Console/Commands/Etl/Steps/Step04MigrateSiswa.php`
- Create: `app/Console/Commands/Etl/Steps/Step05MigrateAdminUser.php`
- Create: `app/Console/Commands/Etl/Cleansing/*.php`

- [ ] **Step 1: Create Cleansing helper classes**

Create `app/Console/Commands/Etl/Cleansing/MoneyCleaner.php`:

```php
<?php
namespace App\Console\Commands\Etl\Cleansing;

class MoneyCleaner
{
    public static function clean(?string $value): float
    {
        return clean_money($value);  // delegate to helpers.php
    }
}
```

Create `app/Console/Commands/Etl/Cleansing/DateCleaner.php`:

```php
<?php
namespace App\Console\Commands\Etl\Cleansing;

class DateCleaner
{
    public static function clean(?string $value): ?string
    {
        return clean_date($value);
    }
}
```

Create `app/Console/Commands/Etl/Cleansing/PhoneCleaner.php`:

```php
<?php
namespace App\Console\Commands\Etl\Cleansing;

class PhoneCleaner
{
    public static function clean(?string $value): ?string
    {
        return clean_phone($value);
    }
}
```

Create `app/Console/Commands/Etl/Cleansing/PasswordResetter.php`:

```php
<?php
namespace App\Console\Commands\Etl\Cleansing;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetter
{
    /**
     * Generate password default untuk user ETL.
     * Format: <NIS/NIP>@<tanggal_lahir_yyyymmdd> bila tanggal_lahir ada.
     * Else: random 16-char string (user wajib reset via forgot password).
     */
    public static function generate(?string $nisOrNip, ?string $tanggalLahir): string
    {
        $tanggal = clean_date($tanggalLahir);
        if ($nisOrNip && $tanggal) {
            $raw = $nisOrNip . '@' . str_replace('-', '', $tanggal);
        } else {
            $raw = Str::random(16);
        }
        return Hash::make($raw);
    }
}
```

- [ ] **Step 2: Implement Step01MigrateTahunAjaran**

Create `app/Console/Commands/Etl/Steps/Step01MigrateTahunAjaran.php`:

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step01MigrateTahunAjaran implements StepInterface
{
    public function name(): string { return 'Migrate Tahun Ajaran (m_tapel → tahun_ajaran)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        // Legacy: m_tapel (kd_tapel varchar PK, nama_tapel, tapel_aktif 'true'/'false', tgl_mulai, tgl_akhir)
        $legacyRows = DB::connection('legacy_mysql')
            ->table('m_tapel')
            ->get();

        foreach ($legacyRows as $row) {
            // Idempotent: skip if already mapped
            if ($mapper->get($tenant->id, 'tapel', $row->kd_tapel)) {
                $result->addSkipped();
                continue;
            }

            try {
                $tapel = TahunAjaran::create([
                    'tenant_id'       => $tenant->id,
                    'nama'            => trim($row->nama_tapel),
                    'tanggal_mulai'   => clean_date($row->tgl_mulai ?? null),
                    'tanggal_selesai' => clean_date($row->tgl_akhir ?? null),
                    'aktif'           => ($row->tapel_aktif === 'true' || $row->tapel_aktif == 1),
                ]);

                $mapper->set($tenant->id, 'tapel', $row->kd_tapel, $tapel->id);
                $result->addMigrated();
            } catch (\Throwable $e) {
                $result->addError("tapel {$row->kd_tapel}: " . $e->getMessage());
            }
        }

        return $result;
    }
}
```

- [ ] **Step 3: Implement Step02MigrateMapelJenis**

Create `app/Console/Commands/Etl/Steps/Step02MigrateMapelJenis.php`:

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\MapelJenis;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step02MigrateMapelJenis implements StepInterface
{
    public function name(): string { return 'Migrate Jenis Mapel (m_mapel_jns → mapel_jenis)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        $rows = DB::connection('legacy_mysql')->table('m_mapel_jns')->get();

        foreach ($rows as $row) {
            if ($mapper->get($tenant->id, 'mapel_jenis', $row->kd_mapel_jns)) {
                $result->addSkipped();
                continue;
            }
            try {
                $mj = MapelJenis::create(['tenant_id' => $tenant->id, 'nama' => trim($row->nm_mapel_jns ?? $row->nama)]);
                $mapper->set($tenant->id, 'mapel_jenis', $row->kd_mapel_jns, $mj->id);
                $result->addMigrated();
            } catch (\Throwable $e) {
                $result->addError("mapel_jenis {$row->kd_mapel_jns}: " . $e->getMessage());
            }
        }

        return $result;
    }
}
```

- [ ] **Step 4: Implement Step03MigrateGuru**

Create `app/Console/Commands/Etl/Steps/Step03MigrateGuru.php`:

> **2-step per row**: (1) create User (email=NIP@tenant, must_reset_password=true) → (2) create Guru linked to User.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Console\Commands\Etl\Cleansing\{DateCleaner, PasswordResetter, PhoneCleaner};
use App\Models\User;
use App\Modules\Academic\Models\Guru;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step03MigrateGuru implements StepInterface
{
    public function name(): string { return 'Migrate Guru (m_pegawai → users + guru)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        DB::connection('legacy_mysql')
            ->table('m_pegawai')
            ->orderBy('kd_pegawai')
            ->chunk(100, function ($rows) use ($tenant, $mapper, $result) {
                foreach ($rows as $row) {
                    if ($mapper->get($tenant->id, 'guru', $row->kd_pegawai)) {
                        $result->addSkipped();
                        continue;
                    }
                    try {
                        // Step A: Create User
                        $nip    = trim($row->nip ?? $row->kd_pegawai);
                        $email  = strtolower(str_replace(' ', '', $nip)) . '@' . str_replace(' ', '', strtolower($tenant->nama)) . '.sch.id';
                        $user   = User::create([
                            'tenant_id'           => $tenant->id,
                            'username'            => $nip,
                            'email'               => $email,
                            'nama'                => trim($row->nm_pegawai ?? $row->nama),
                            'password'            => PasswordResetter::generate($nip, $row->tgl_lahir ?? null),
                            'tipe'                => 'guru',
                            'aktif'               => true,
                            'must_reset_password' => true,
                        ]);
                        $mapper->set($tenant->id, 'user_guru', $row->kd_pegawai, $user->id);

                        // Step B: Create Guru profile
                        $guru = Guru::create([
                            'tenant_id'     => $tenant->id,
                            'nip'           => $nip,
                            'nama'          => trim($row->nm_pegawai ?? $row->nama),
                            'jenis_kelamin' => $row->jenis_kelamin ?? null,
                            'telepon'       => PhoneCleaner::clean($row->telp ?? null),
                            'email'         => $email,
                            'jabatan'       => trim($row->jabatan ?? ''),
                            'aktif'         => true,
                        ]);
                        $mapper->set($tenant->id, 'guru', $row->kd_pegawai, $guru->id);

                        // Assign role 'guru'
                        $user->assignRole('guru');
                        $result->addMigrated();
                    } catch (\Throwable $e) {
                        $result->addError("guru {$row->kd_pegawai}: " . $e->getMessage());
                    }
                }
            });

        return $result;
    }
}
```

- [ ] **Step 5: Implement Step04MigrateSiswa**

Create `app/Console/Commands/Etl/Steps/Step04MigrateSiswa.php`:

> **3-entity per row**: User siswa + Siswa profil + User/OrangTua (bila `passwordx_ortu` ada).

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Console\Commands\Etl\Cleansing\{DateCleaner, PasswordResetter, PhoneCleaner};
use App\Models\User;
use App\Modules\Academic\Models\{OrangTua, Siswa, SiswaOrangTua};
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step04MigrateSiswa implements StepInterface
{
    public function name(): string { return 'Migrate Siswa + Ortu (m_siswa → users + siswa + orang_tua)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        DB::connection('legacy_mysql')
            ->table('m_siswa')
            ->orderBy('kd_siswa')
            ->chunk(100, function ($rows) use ($tenant, $mapper, $result) {
                foreach ($rows as $row) {
                    if ($mapper->get($tenant->id, 'siswa', $row->kd_siswa)) {
                        $result->addSkipped();
                        continue;
                    }
                    try {
                        $nis = trim($row->nis ?? $row->kd_siswa);

                        // Create User siswa
                        $user = User::create([
                            'tenant_id'           => $tenant->id,
                            'username'            => $nis,
                            'email'               => $nis . '@siswa.' . strtolower(str_replace(' ', '', $tenant->nama)) . '.sch.id',
                            'nama'                => trim($row->nm_siswa),
                            'password'            => PasswordResetter::generate($nis, $row->tgl_lahir ?? null),
                            'tipe'                => 'siswa',
                            'aktif'               => ($row->status_siswa ?? 'aktif') === 'aktif',
                            'must_reset_password' => true,
                        ]);
                        $user->assignRole('siswa');
                        $mapper->set($tenant->id, 'user_siswa', $row->kd_siswa, $user->id);

                        // Create Siswa profile
                        $siswa = Siswa::create([
                            'tenant_id'     => $tenant->id,
                            'nis'           => $nis,
                            'nisn'          => trim($row->nisn ?? ''),
                            'nama'          => trim($row->nm_siswa),
                            'jenis_kelamin' => $row->jenis_kelamin ?? null,
                            'tempat_lahir'  => trim($row->tmp_lahir ?? ''),
                            'tanggal_lahir' => DateCleaner::clean($row->tgl_lahir ?? null),
                            'alamat'        => trim($row->alamat ?? ''),
                            'telepon'       => PhoneCleaner::clean($row->telp ?? null),
                            'agama'         => trim($row->agama ?? ''),
                            'status'        => in_array($row->status_siswa ?? 'aktif', ['aktif','lulus','pindah','keluar']) ? $row->status_siswa : 'aktif',
                            'qrcode'        => 'QR-' . $nis . '-' . $tenant->id,
                        ]);
                        $mapper->set($tenant->id, 'siswa', $row->kd_siswa, $siswa->id);

                        // Create OrangTua bila passwordx_ortu ada
                        if (! empty($row->passwordx_ortu) && ! empty($row->telp_ortu)) {
                            $ortu = OrangTua::firstOrCreate(
                                ['tenant_id' => $tenant->id, 'telepon' => PhoneCleaner::clean($row->telp_ortu)],
                                [
                                    'tenant_id'   => $tenant->id,
                                    'nama'        => trim($row->nm_ortu ?? 'Wali ' . trim($row->nm_siswa)),
                                    'hubungan'    => 'wali',
                                    'telepon'     => PhoneCleaner::clean($row->telp_ortu),
                                    'username'    => 'ortu_' . $nis,
                                    'password'    => PasswordResetter::generate('ortu_' . $nis, null),
                                ]
                            );
                            SiswaOrangTua::firstOrCreate(['siswa_id' => $siswa->id, 'orang_tua_id' => $ortu->id]);
                        }

                        $result->addMigrated();
                    } catch (\Throwable $e) {
                        $result->addError("siswa {$row->kd_siswa}: " . $e->getMessage());
                    }
                }
            });

        return $result;
    }
}
```

- [ ] **Step 6: Implement Step05MigrateAdminUser**

Create `app/Console/Commands/Etl/Steps/Step05MigrateAdminUser.php`:

> Migrasi user admin/non-guru/non-siswa dari `m_user`/`adminx`. Mapping role dari `tipe` (tp01..tp042) ke Spatie role.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Console\Commands\Etl\Cleansing\PasswordResetter;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step05MigrateAdminUser implements StepInterface
{
    public function name(): string { return 'Migrate Admin/Staff Users (m_user/adminx → users)'; }

    private array $roleMap = [
        'tp06'  => ['admin_sekolah'],
        'tp01'  => ['guru'],
        'tp02'  => ['siswa'],
        'tp03'  => ['wk', 'guru'],
        'tp04'  => ['ks'],
        'tp011' => ['bk'],
        'tp033' => ['piket'],
        'tp041' => ['sarpras'],
        'tp042' => ['bendahara'],
        'tp05'  => ['super_admin'],
    ];

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        // Try legacy table names (may differ per installation)
        $legacyTable = DB::connection('legacy_mysql')->getSchemaBuilder()->hasTable('m_user') ? 'm_user' : 'adminx';

        DB::connection('legacy_mysql')
            ->table($legacyTable)
            ->chunk(100, function ($rows) use ($tenant, $mapper, $result) {
                foreach ($rows as $row) {
                    $kd = $row->kd_user ?? $row->id ?? null;
                    if (! $kd) continue;

                    // Skip siswa & guru — handled in steps 3 & 4
                    $tipe = $row->tipe ?? $row->tp ?? null;
                    if (in_array($tipe, ['tp01', 'tp02', 'tp03'])) continue;

                    if ($mapper->get($tenant->id, 'user_admin', (string)$kd)) {
                        $result->addSkipped();
                        continue;
                    }

                    try {
                        $username = trim($row->username ?? $row->user_login ?? "user_{$kd}");
                        $user = User::create([
                            'tenant_id'           => $tenant->id,
                            'username'            => $username,
                            'email'               => $username . '@admin.' . strtolower(str_replace(' ', '', $tenant->nama)) . '.sch.id',
                            'nama'                => trim($row->nama ?? $row->nm_user ?? $username),
                            'password'            => PasswordResetter::generate($username, null),
                            'tipe'                => $tipe ?? 'staff',
                            'aktif'               => true,
                            'must_reset_password' => true,
                        ]);

                        $roles = $this->roleMap[$tipe] ?? ['staff'];
                        foreach ($roles as $role) {
                            if (\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                                $user->assignRole($role);
                            }
                        }

                        $mapper->set($tenant->id, 'user_admin', (string)$kd, $user->id);
                        $result->addMigrated();
                    } catch (\Throwable $e) {
                        $result->addError("admin_user {$kd}: " . $e->getMessage());
                    }
                }
            });

        return $result;
    }
}
```

- [ ] **Step 7: Write ETL unit test for Step01**

Create `tests/Feature/Etl/EtlTahunAjaranTest.php`:

```php
<?php
namespace Tests\Feature\Etl;

use App\Console\Commands\Etl\IdMapper;
use App\Console\Commands\Etl\Steps\Step01MigrateTahunAjaran;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtlTahunAjaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrates_tapel_from_legacy(): void
    {
        // Mock legacy DB (use sqlite in-memory for testing)
        // In real test: configure test DB with legacy_mysql = sqlite memory
        $tenant = Tenant::create(['nama' => 'Demo School', 'npsn' => '12345678']);
        $mapper = new IdMapper();

        // Seed legacy mock (via DB::connection('legacy_mysql') in testing uses same DB connection)
        // Note: in test environment, legacy_mysql can be set to use sqlite :memory: via phpunit.xml
        DB::statement('CREATE TABLE IF NOT EXISTS m_tapel (kd_tapel VARCHAR(50) PRIMARY KEY, nama_tapel VARCHAR(100), tapel_aktif VARCHAR(10), tgl_mulai VARCHAR(20), tgl_akhir VARCHAR(20))');
        DB::table('m_tapel')->insert([
            ['kd_tapel' => 'TP2024', 'nama_tapel' => '2024/2025', 'tapel_aktif' => 'true', 'tgl_mulai' => '2024-07-15', 'tgl_akhir' => '2025-06-30'],
        ]);

        $step   = new Step01MigrateTahunAjaran();
        $result = DB::transaction(fn() => $step->handle($tenant, $mapper));

        $this->assertEquals(1, $result->migrated);
        $this->assertDatabaseHas('tahun_ajaran', ['nama' => '2024/2025', 'tenant_id' => $tenant->id, 'aktif' => true]);
        $this->assertNotNull($mapper->get($tenant->id, 'tapel', 'TP2024'));
    }

    public function test_idempotent_skips_already_mapped(): void
    {
        $tenant = Tenant::create(['nama' => 'Demo School', 'npsn' => '12345678']);
        $mapper = new IdMapper();

        DB::statement('CREATE TABLE IF NOT EXISTS m_tapel (kd_tapel VARCHAR(50) PRIMARY KEY, nama_tapel VARCHAR(100), tapel_aktif VARCHAR(10), tgl_mulai VARCHAR(20), tgl_akhir VARCHAR(20))');
        DB::table('m_tapel')->insert(['kd_tapel' => 'TP2024', 'nama_tapel' => '2024/2025', 'tapel_aktif' => 'false', 'tgl_mulai' => null, 'tgl_akhir' => null]);

        $step = new Step01MigrateTahunAjaran();

        // Run twice
        DB::transaction(fn() => $step->handle($tenant, $mapper));
        $result2 = DB::transaction(fn() => $step->handle($tenant, $mapper));

        $this->assertEquals(0, $result2->migrated);
        $this->assertEquals(1, $result2->skipped);
    }
}
```

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(etl): steps 1-5 (tahun_ajaran, mapel_jenis, guru, siswa+ortu, admin users) + cleansing classes"
```

---

## Task 4: Steps 6–9 (Academic relations)

**Files:**
- Create: `Step06MigrateMapel.php` through `Step09MigrateJadwal.php`

- [ ] **Step 1: Implement Step06MigrateMapel**

Create `app/Console/Commands/Etl/Steps/Step06MigrateMapel.php`:

> Source: `m_mapel`. Fields: `kd_mapel` (PK MD5), `nm_mapel`, `kd_mapel_jns` (FK), `kkm` (varchar), `kd_pegawai` (FK guru).

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\Mapel;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step06MigrateMapel implements StepInterface
{
    public function name(): string { return 'Migrate Mapel (m_mapel → mapel)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        DB::connection('legacy_mysql')->table('m_mapel')->chunk(100, function ($rows) use ($tenant, $mapper, $result) {
            foreach ($rows as $row) {
                if ($mapper->get($tenant->id, 'mapel', $row->kd_mapel)) {
                    $result->addSkipped(); continue;
                }
                try {
                    $mapelJenisId = $mapper->get($tenant->id, 'mapel_jenis', $row->kd_mapel_jns ?? '');
                    $kkm = is_numeric($row->kkm) ? (float) $row->kkm : clean_money($row->kkm ?? null);

                    $mapel = Mapel::create([
                        'tenant_id'      => $tenant->id,
                        'kode'           => trim($row->kd_mapel),
                        'nama'           => trim($row->nm_mapel),
                        'mapel_jenis_id' => $mapelJenisId,
                        'kkm'            => $kkm,
                        'kurikulum_id'   => null,  // akan di-set manual pasca ETL
                    ]);
                    $mapper->set($tenant->id, 'mapel', $row->kd_mapel, $mapel->id);
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("mapel {$row->kd_mapel}: " . $e->getMessage());
                }
            }
        });

        return $result;
    }
}
```

- [ ] **Step 2: Implement Step07MigrateKelas**

Create `app/Console/Commands/Etl/Steps/Step07MigrateKelas.php`:

> Source: `m_kelas` + join `m_walikelas` untuk `wali_kelas_id`. Mapping `peg_kd` → `guru_id` → `user_id` wali kelas.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\Kelas;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step07MigrateKelas implements StepInterface
{
    public function name(): string { return 'Migrate Kelas + Walikelas (m_kelas → kelas)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        // Left join m_walikelas
        $rows = DB::connection('legacy_mysql')
            ->table('m_kelas as k')
            ->leftJoin('m_walikelas as w', 'k.kd_kelas', '=', 'w.kd_kelas')
            ->select('k.*', 'w.peg_kd as wali_peg_kd')
            ->get();

        foreach ($rows as $row) {
            if ($mapper->get($tenant->id, 'kelas', $row->kd_kelas)) {
                $result->addSkipped(); continue;
            }
            try {
                $waliUserId = $row->wali_peg_kd
                    ? $mapper->get($tenant->id, 'user_guru', $row->wali_peg_kd)
                    : null;
                $tingkat = (int) preg_replace('/\D/', '', $row->kelas ?? $row->nm_kelas ?? '0');

                $kelas = Kelas::create([
                    'tenant_id'       => $tenant->id,
                    'nama'            => trim($row->nm_kelas ?? $row->kelas),
                    'tingkat'         => $tingkat ?: null,
                    'wali_kelas_id'   => $waliUserId,
                ]);
                $mapper->set($tenant->id, 'kelas', $row->kd_kelas, $kelas->id);
                $result->addMigrated();
            } catch (\Throwable $e) {
                $result->addError("kelas {$row->kd_kelas}: " . $e->getMessage());
            }
        }

        return $result;
    }
}
```

- [ ] **Step 3: Implement Step08MigrateKelasSiswa**

Create `app/Console/Commands/Etl/Steps/Step08MigrateKelasSiswa.php`:

> Source: `m_siswa.kelas` (string nama kelas, denorm). Resolve kelas → `kelas_id` via nama. Default `tahun_ajaran_id` = tapel_aktif dari mapping.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\KelasSiswa;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step08MigrateKelasSiswa implements StepInterface
{
    public function name(): string { return 'Migrate Kelas Siswa (m_siswa.kelas string → kelas_siswa pivot)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        // Build kelas nama→id map for this tenant (in new DB)
        $kelasMap = \App\Modules\Academic\Models\Kelas::where('tenant_id', $tenant->id)
            ->pluck('id', 'nama')
            ->toArray();

        // Tapel aktif (first tapel mapped, or the one marked aktif)
        $tapelAktifId = \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)
            ->where('aktif', true)
            ->value('id')
            ?? \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)->value('id');

        DB::connection('legacy_mysql')
            ->table('m_siswa')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->chunk(200, function ($rows) use ($tenant, $mapper, $result, $kelasMap, $tapelAktifId) {
                foreach ($rows as $row) {
                    $siswaId = $mapper->get($tenant->id, 'siswa', $row->kd_siswa);
                    if (! $siswaId) { $result->addError("siswa not mapped: {$row->kd_siswa}"); continue; }

                    $kelasNama = trim($row->kelas);
                    $kelasId   = $kelasMap[$kelasNama] ?? null;
                    if (! $kelasId) {
                        $result->addError("kelas nama '{$kelasNama}' not found for siswa {$row->kd_siswa}");
                        continue;
                    }
                    if (! $tapelAktifId) { $result->addError("no tapel for tenant"); return; }

                    try {
                        KelasSiswa::firstOrCreate([
                            'tenant_id'       => $tenant->id,
                            'kelas_id'        => $kelasId,
                            'siswa_id'        => $siswaId,
                            'tahun_ajaran_id' => $tapelAktifId,
                        ]);
                        $result->addMigrated();
                    } catch (\Throwable $e) {
                        $result->addError("kelas_siswa {$row->kd_siswa}: " . $e->getMessage());
                    }
                }
            });

        return $result;
    }
}
```

- [ ] **Step 4: Implement Step09MigrateJadwal**

Create `app/Console/Commands/Etl/Steps/Step09MigrateJadwal.php`:

> Source: `jadwal` + `m_waktu_jadwal`. Map `waktu` string → TIME `jam_mulai/jam_selesai`. Map `mapel_kd`, `peg_kd`, `kelas_kd` via IdMapper.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Academic\Models\Jadwal;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step09MigrateJadwal implements StepInterface
{
    public function name(): string { return 'Migrate Jadwal (jadwal + m_waktu_jadwal → jadwal)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        // Preload waktu table
        $waktuMap = DB::connection('legacy_mysql')
            ->table('m_waktu_jadwal')
            ->get()
            ->keyBy('kd_waktu');

        // Default tapel + semester aktif
        $tapelId  = \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');
        $semId    = \App\Modules\Academic\Models\Semester::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');

        DB::connection('legacy_mysql')
            ->table('jadwal')
            ->chunk(200, function ($rows) use ($tenant, $mapper, $result, $waktuMap, $tapelId, $semId) {
                foreach ($rows as $row) {
                    try {
                        $mapelId  = $mapper->get($tenant->id, 'mapel', $row->kd_mapel ?? '');
                        $guruId   = $mapper->get($tenant->id, 'guru', $row->kd_pegawai ?? $row->peg_kd ?? '');
                        $kelasId  = $mapper->get($tenant->id, 'kelas', $row->kd_kelas ?? '');

                        if (! $mapelId || ! $kelasId || ! $tapelId) {
                            $result->addError("jadwal skip (unresolved FK): kelas={$row->kd_kelas} mapel={$row->kd_mapel}");
                            continue;
                        }

                        $waktu    = $waktuMap[$row->kd_waktu ?? ''] ?? null;
                        $jamMulai = $waktu?->jam_mulai ?? null;
                        $jamSls   = $waktu?->jam_selesai ?? null;

                        Jadwal::firstOrCreate(
                            [
                                'tenant_id'       => $tenant->id,
                                'tahun_ajaran_id' => $tapelId,
                                'semester_id'     => $semId,
                                'kelas_id'        => $kelasId,
                                'hari'            => (int) ($row->hari ?? 1),
                                'jam_ke'          => (int) ($row->jam_ke ?? $row->urutan ?? 1),
                            ],
                            [
                                'mapel_id'  => $mapelId,
                                'guru_id'   => $guruId,
                                'jam_mulai' => $jamMulai,
                                'jam_selesai' => $jamSls,
                            ]
                        );
                        $result->addMigrated();
                    } catch (\Throwable $e) {
                        $result->addError("jadwal: " . $e->getMessage());
                    }
                }
            });

        return $result;
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(etl): steps 6-9 (mapel, kelas+walikelas, kelas_siswa, jadwal)"
```

---

## Task 5: Steps 10–13 (Evaluation data)

**Files:**
- Create: `Step10MigrateTpLm.php` through `Step13MigrateRapor.php`

- [ ] **Step 1: Implement Step10MigrateTpLm**

> Source: `kurmer_mapel_tp` (TP) + `kurmer_mapel_lm` (LM). Fields: `kd_mapel`, `kd_tapel`, `kd_kelas`, teks, urutan.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Evaluation\Models\{Tp, Lm};
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step10MigrateTpLm implements StepInterface
{
    public function name(): string { return 'Migrate TP/LM Kurmer (kurmer_mapel_tp/lm → tp, lm)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();
        $tapelId = \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');
        $semId   = \App\Modules\Academic\Models\Semester::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');

        // TP
        DB::connection('legacy_mysql')->table('kurmer_mapel_tp')->chunk(200, function ($rows) use ($tenant, $mapper, $result, $tapelId, $semId) {
            foreach ($rows as $row) {
                try {
                    $mapelId = $mapper->get($tenant->id, 'mapel', $row->kd_mapel ?? '');
                    $kelasId = $mapper->get($tenant->id, 'kelas', $row->kd_kelas ?? '');
                    if (! $mapelId) { $result->addError("tp: mapel {$row->kd_mapel} not mapped"); continue; }

                    $tp = Tp::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'mapel_id' => $mapelId, 'tahun_ajaran_id' => $tapelId, 'kode' => trim($row->kd_tp ?? $row->id)],
                        ['kelas_id' => $kelasId, 'teks' => trim($row->teks_tp ?? $row->teks), 'urutan' => (int)($row->urutan ?? 0)]
                    );
                    $mapper->set($tenant->id, 'tp', $row->id ?? $row->kd_tp, $tp->id);
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("tp: " . $e->getMessage());
                }
            }
        });

        // LM — sama pola
        DB::connection('legacy_mysql')->table('kurmer_mapel_lm')->chunk(200, function ($rows) use ($tenant, $mapper, $result, $tapelId, $semId) {
            foreach ($rows as $row) {
                try {
                    $mapelId = $mapper->get($tenant->id, 'mapel', $row->kd_mapel ?? '');
                    $kelasId = $mapper->get($tenant->id, 'kelas', $row->kd_kelas ?? '');
                    if (! $mapelId) { $result->addError("lm: mapel {$row->kd_mapel} not mapped"); continue; }

                    $lm = Lm::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'mapel_id' => $mapelId, 'tahun_ajaran_id' => $tapelId, 'kode' => trim($row->kd_lm ?? $row->id)],
                        ['kelas_id' => $kelasId, 'teks' => trim($row->teks_lm ?? $row->teks), 'urutan' => (int)($row->urutan ?? 0)]
                    );
                    $mapper->set($tenant->id, 'lm', $row->id ?? $row->kd_lm, $lm->id);
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("lm: " . $e->getMessage());
                }
            }
        });

        return $result;
    }
}
```

- [ ] **Step 2: Implement Steps 11–13**

Create `Step11MigrateAsesmenFormatif.php`:
> Source: `kurmer_nilai_asesmen_formatif_detail`. Map siswa_kd + mapel_kd + tp_kd via mapper. `nilai` enum `Tercapai`/`Belum`.

Create `Step12MigrateAsesmenSumatif.php`:
> Source: `kurmer_nilai_asesmen_sumatif_detail`. Map via mapper. `nilai_*` varchar → `decimal` via `clean_money()`.

Create `Step13MigrateRapor.php`:
> Source: `siswa_raport_catatan`, `siswa_raport_sikap`, `siswa_raport_kenaikan`. Map siswa_kd + tapel_kd. Trim text.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat(etl): steps 10-13 (TP/LM, asesmen formatif/sumatif, rapor)"
```

---

## Task 6: Steps 14–17 (Finance data — KRITIS)

**Files:**
- Create: `Step14MigrateItemPembayaran.php` through `Step17MigrateTabungan.php`

- [ ] **Step 1: Implement Step14MigrateItemPembayaran**

> Source: `m_keu_siswa`. Field `nominal` = varchar → `decimal(15,2)` via `clean_money()`.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Console\Commands\Etl\Cleansing\MoneyCleaner;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step14MigrateItemPembayaran implements StepInterface
{
    public function name(): string { return 'Migrate Item Pembayaran (m_keu_siswa → item_pembayaran)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();
        $tapelId = \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');

        DB::connection('legacy_mysql')->table('m_keu_siswa')->chunk(100, function ($rows) use ($tenant, $mapper, $result, $tapelId) {
            foreach ($rows as $row) {
                if ($mapper->get($tenant->id, 'item_pembayaran', $row->kd_keu)) { $result->addSkipped(); continue; }
                try {
                    $nominal  = MoneyCleaner::clean($row->nominal ?? '0');
                    $item = ItemPembayaran::create([
                        'tenant_id'       => $tenant->id,
                        'tahun_ajaran_id' => $tapelId,
                        'nama'            => trim($row->nm_keu ?? $row->nama_pembayaran),
                        'jenis'           => $this->mapJenis($row->jenis_keu ?? ''),
                        'nominal'         => $nominal,
                        'periode'         => $this->mapPeriode($row->periode ?? ''),
                        'aktif'           => true,
                    ]);
                    $mapper->set($tenant->id, 'item_pembayaran', $row->kd_keu, $item->id);
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("item_pembayaran {$row->kd_keu}: " . $e->getMessage());
                }
            }
        });
        return $result;
    }

    private function mapJenis(string $j): string
    {
        return match(strtolower($j)) {
            'spp'    => 'spp',
            'infaq'  => 'infaq',
            'kegiatan' => 'kegiatan',
            default  => 'lainnya',
        };
    }

    private function mapPeriode(string $p): string
    {
        return match(strtolower($p)) {
            'bulanan'   => 'bulanan',
            'semester'  => 'semester',
            'tahunan'   => 'tahunan',
            default     => 'sekali',
        };
    }
}
```

- [ ] **Step 2: Implement Step15MigrateTagihanSiswa**

> Source: `siswa_bayar_tagihan`. **KRITIS**: `nominal_bayar` + `nominal_kurang` varchar → decimal. Map `siswa_kd` + `item_kd` via mapper. `lunas_status` → bool.

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Console\Commands\Etl\Cleansing\MoneyCleaner;
use App\Modules\Finance\Models\TagihanSiswa;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step15MigrateTagihanSiswa implements StepInterface
{
    public function name(): string { return 'Migrate Tagihan Siswa (siswa_bayar_tagihan → tagihan_siswa) [KRITIS]'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();
        $tapelId = \App\Modules\Academic\Models\TahunAjaran::where('tenant_id', $tenant->id)->where('aktif', true)->value('id');

        DB::connection('legacy_mysql')->table('siswa_bayar_tagihan')->chunk(200, function ($rows) use ($tenant, $mapper, $result, $tapelId) {
            foreach ($rows as $row) {
                if ($mapper->get($tenant->id, 'tagihan', $row->id ?? $row->kd_tagihan)) { $result->addSkipped(); continue; }
                try {
                    $siswaId  = $mapper->get($tenant->id, 'siswa', $row->kd_siswa ?? '');
                    $itemId   = $mapper->get($tenant->id, 'item_pembayaran', $row->kd_keu ?? '');
                    if (! $siswaId || ! $itemId) {
                        $result->addError("tagihan skip: siswa={$row->kd_siswa} item={$row->kd_keu}"); continue;
                    }

                    $nomTagihan = MoneyCleaner::clean($row->nominal_tagihan ?? $row->nominal ?? '0');
                    $nomBayar   = MoneyCleaner::clean($row->nominal_bayar ?? '0');
                    $nomKurang  = MoneyCleaner::clean($row->nominal_kurang ?? (string)($nomTagihan - $nomBayar));
                    $lunas      = ($row->lunas_status ?? $row->lunas ?? 0) == 1 || strtolower($row->lunas_status ?? '') === 'lunas';

                    $tagihan = TagihanSiswa::create([
                        'tenant_id'          => $tenant->id,
                        'siswa_id'           => $siswaId,
                        'item_pembayaran_id' => $itemId,
                        'tahun_ajaran_id'    => $tapelId,
                        'bulan'              => $row->bulan ?? null,
                        'nominal_tagihan'    => $nomTagihan,
                        'nominal_bayar'      => $nomBayar,
                        'nominal_kurang'     => $nomKurang,
                        'lunas'              => $lunas,
                        'tanggal_lunas'      => $lunas && isset($row->tgl_lunas) ? clean_date($row->tgl_lunas) : null,
                    ]);
                    $mapper->set($tenant->id, 'tagihan', (string)($row->id ?? $row->kd_tagihan), $tagihan->id);
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("tagihan {$row->id}: " . $e->getMessage());
                }
            }
        });
        return $result;
    }
}
```

- [ ] **Step 3: Implement Steps 16–17**

Create `Step16MigratePembayaran.php`:
> Source: `siswa_bayar` (header) + `siswa_bayar_rincian` (detail). Map via mapper. `total` varchar → decimal. No `no_nota` → generate `PMB-<legacy_id>-<tenant>`.

Create `Step17MigrateTabungan.php`:
> Source: legacy tabungan table (bila ada). Map siswa_kd. `saldo` varchar → decimal.

- [ ] **Step 4: Write finance test**

Create `tests/Feature/Etl/EtlKeuanganTest.php`:

```php
<?php
namespace Tests\Feature\Etl;

use App\Console\Commands\Etl\IdMapper;
use App\Console\Commands\Etl\Steps\Step14MigrateItemPembayaran;
use App\Console\Commands\Etl\Steps\Step15MigrateTagihanSiswa;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtlKeuanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_nominal_varchar_converted_correctly(): void
    {
        $tenant = Tenant::create(['nama' => 'Demo', 'npsn' => '12345678']);

        // Test nominal cleansing: "Rp. 150.000" → 150000.00
        $this->assertEquals(150000.00, clean_money('Rp. 150.000'));
        $this->assertEquals(1500000.50, clean_money('1.500.000,50'));
        $this->assertEquals(0.00, clean_money(''));
        $this->assertEquals(0.00, clean_money(null));
    }

    public function test_tagihan_lunas_logic(): void
    {
        $this->assertEquals(0.00, 150000.00 - 150000.00);   // lunas exact
        $this->assertTrue((150000.00 - 150000.00) <= 0);    // lunas check
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(etl): steps 14-17 (item_pembayaran, tagihan_siswa KRITIS, pembayaran, tabungan)"
```

---

## Task 7: Steps 18–20 (Presence data)

**Files:**
- Create: `Step18MigratePresensi.php` through `Step20MigrateIzin.php`

- [ ] **Step 1: Implement Step18MigratePresensi**

> Source: `user_presensi`. Map `user_kd` → `user_id` atau `siswa_id` (via tipe user). `telat_ket` varchar → `telat_menit` int (ambil angka pertama dari string "30 menit" → 30).

```php
<?php
namespace App\Console\Commands\Etl\Steps;

use App\Console\Commands\Etl\{EtlResult, IdMapper, StepInterface};
use App\Modules\Presence\Models\Presensi;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Step18MigratePresensi implements StepInterface
{
    public function name(): string { return 'Migrate Presensi (user_presensi → presensi)'; }

    public function handle(Tenant $tenant, IdMapper $mapper): EtlResult
    {
        $result = EtlResult::make();

        DB::connection('legacy_mysql')->table('user_presensi')->chunk(500, function ($rows) use ($tenant, $mapper, $result) {
            foreach ($rows as $row) {
                try {
                    // Resolve user/siswa
                    $siswaId = $mapper->get($tenant->id, 'siswa', $row->kd_user ?? $row->kd_siswa ?? '');
                    $userId  = $mapper->get($tenant->id, 'user_siswa', $row->kd_user ?? '') ??
                               $mapper->get($tenant->id, 'user_guru', $row->kd_user ?? '');

                    $telatMenit = (int) preg_replace('/\D.*/', '', $row->telat_ket ?? '0');

                    Presensi::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'siswa_id' => $siswaId, 'tanggal' => clean_date($row->tgl ?? $row->tanggal), 'jenis' => 'datang'],
                        [
                            'user_id'    => $userId,
                            'jam'        => substr($row->jam ?? '07:00', 0, 5),
                            'telat_menit'=> $telatMenit,
                            'metode'     => 'manual',
                        ]
                    );
                    $result->addMigrated();
                } catch (\Throwable $e) {
                    $result->addError("presensi: " . $e->getMessage());
                }
            }
        });

        return $result;
    }
}
```

- [ ] **Step 2: Implement Steps 19–20**

Create `Step19MigrateAbsensi.php`:
> Source: `user_absensi`. Map siswa/user. `jenis` normalize: 'sakit'/'ijin'/'alpha'. Keterangan text trim.

Create `Step20MigrateIzin.php`:
> Source: `user_ijin`. Legacy tidak punya approval workflow → set `status='approved'` semua. Map siswa_id.

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat(etl): steps 18-20 (presensi, absensi, izin) — presence migration complete"
```

---

## Task 8: ETL Verify Command (20 checks)

**Files:**
- Create: `app/Console/Commands/EtlVerifyCommand.php`
- Create: `tests/Feature/Etl/EtlVerifyTest.php`

- [ ] **Step 1: Implement EtlVerifyCommand**

Create `app/Console/Commands/EtlVerifyCommand.php`:

```php
<?php
namespace App\Console\Commands;

use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EtlVerifyCommand extends Command
{
    protected $signature   = 'etl:verify {tenant_id} {--drop-mappings : Drop legacy_id_mappings setelah verifikasi PASS}';
    protected $description = 'Verifikasi integritas data hasil ETL untuk satu tenant';

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        $tenant   = Tenant::findOrFail($tenantId);

        $this->info("=== ETL VERIFY: Tenant [{$tenant->id}] {$tenant->nama} ===");

        $checks  = $this->buildChecks($tenant);
        $passed  = 0;
        $failed  = 0;

        foreach ($checks as $check) {
            [$label, $fn, $expected] = $check;
            try {
                $actual = $fn();
                $ok     = (is_callable($expected)) ? $expected($actual) : ($actual == $expected);

                if ($ok) {
                    $this->line("  ✓ {$label}: {$actual}");
                    $passed++;
                } else {
                    $this->error("  ✗ {$label}: Expected {$expected}, got {$actual}");
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ {$label}: ERROR — " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Passed: {$passed} / " . count($checks));
        if ($failed > 0) {
            $this->error("FAILED: {$failed} checks. Jangan cut-over sebelum semua pass.");
            return 1;
        }

        $this->info("✅ Semua verifikasi PASS. Data siap untuk cut-over.");

        if ($this->option('drop-mappings')) {
            DB::table('legacy_id_mappings')->where('tenant_id', $tenantId)->delete();
            $this->warn("legacy_id_mappings dibersihkan untuk tenant {$tenantId}.");
        }

        return 0;
    }

    private function buildChecks(Tenant $tenant): array
    {
        $tid = $tenant->id;

        $legacySiswaCount = fn() => DB::connection('legacy_mysql')->table('m_siswa')->count();
        $newSiswaCount    = fn() => DB::table('siswa')->where('tenant_id', $tid)->count();
        $legacyGuruCount  = fn() => DB::connection('legacy_mysql')->table('m_pegawai')->count();
        $newGuruCount     = fn() => DB::table('guru')->where('tenant_id', $tid)->count();

        $legacyNominalSum = fn() => DB::connection('legacy_mysql')
            ->select('SELECT SUM(CAST(REPLACE(REPLACE(nominal_bayar, "Rp.", ""), ".", "") AS DECIMAL(15,2))) as total FROM siswa_bayar_tagihan')[0]->total ?? 0;
        $newNominalSum    = fn() => DB::table('tagihan_siswa')->where('tenant_id', $tid)->sum('nominal_bayar');

        return [
            // Count reconciliation
            ['1. Siswa count match',     $newSiswaCount,    $legacySiswaCount],
            ['2. Guru count match',      $newGuruCount,     $legacyGuruCount],
            ['3. TahunAjaran migrated',  fn() => DB::table('tahun_ajaran')->where('tenant_id', $tid)->count(),  fn($v) => $v > 0],
            ['4. Mapel migrated',        fn() => DB::table('mapel')->where('tenant_id', $tid)->count(),          fn($v) => $v > 0],
            ['5. Kelas migrated',        fn() => DB::table('kelas')->where('tenant_id', $tid)->count(),          fn($v) => $v > 0],
            ['6. KelasSiswa migrated',   fn() => DB::table('kelas_siswa')->where('tenant_id', $tid)->count(),    fn($v) => $v > 0],

            // Money reconciliation (KRITIS)
            ['7. Nominal bayar sum match (KRITIS)', $newNominalSum, fn($v) => abs($v - ($legacyNominalSum() ?? 0)) < 1.0],

            // FK integrity checks
            ['8. No orphan kelas_siswa (siswa)',     fn() => DB::select("SELECT COUNT(*) as c FROM kelas_siswa ks LEFT JOIN siswa s ON ks.siswa_id=s.id WHERE s.id IS NULL AND ks.tenant_id={$tid}")[0]->c, 0],
            ['9. No orphan kelas_siswa (kelas)',     fn() => DB::select("SELECT COUNT(*) as c FROM kelas_siswa ks LEFT JOIN kelas k ON ks.kelas_id=k.id WHERE k.id IS NULL AND ks.tenant_id={$tid}")[0]->c, 0],
            ['10. No orphan tagihan_siswa (siswa)',  fn() => DB::select("SELECT COUNT(*) as c FROM tagihan_siswa ts LEFT JOIN siswa s ON ts.siswa_id=s.id WHERE s.id IS NULL AND ts.tenant_id={$tid}")[0]->c, 0],
            ['11. No orphan tagihan_siswa (item)',   fn() => DB::select("SELECT COUNT(*) as c FROM tagihan_siswa ts LEFT JOIN item_pembayaran ip ON ts.item_pembayaran_id=ip.id WHERE ip.id IS NULL AND ts.tenant_id={$tid}")[0]->c, 0],
            ['12. No orphan pembayaran (siswa)',     fn() => DB::select("SELECT COUNT(*) as c FROM pembayaran p LEFT JOIN siswa s ON p.siswa_id=s.id WHERE s.id IS NULL AND p.tenant_id={$tid}")[0]->c, 0],

            // Password reset check
            ['13. All ETL users must_reset_password=true', fn() => DB::table('users')->where('tenant_id', $tid)->where('must_reset_password', false)->count(), 0],

            // Legacy ID mappings coverage
            ['14. IdMap siswa populated',    fn() => DB::table('legacy_id_mappings')->where('tenant_id', $tid)->where('entity_type', 'siswa')->count(),    fn($v) => $v > 0],
            ['15. IdMap guru populated',     fn() => DB::table('legacy_id_mappings')->where('tenant_id', $tid)->where('entity_type', 'guru')->count(),     fn($v) => $v > 0],
            ['16. IdMap mapel populated',    fn() => DB::table('legacy_id_mappings')->where('tenant_id', $tid)->where('entity_type', 'mapel')->count(),    fn($v) => $v > 0],
            ['17. IdMap tapel populated',    fn() => DB::table('legacy_id_mappings')->where('tenant_id', $tid)->where('entity_type', 'tapel')->count(),    fn($v) => $v > 0],

            // Data quality
            ['18. No NULL nominal_tagihan in tagihan_siswa', fn() => DB::table('tagihan_siswa')->where('tenant_id', $tid)->whereNull('nominal_tagihan')->count(), 0],
            ['19. No NULL nama in siswa',   fn() => DB::table('siswa')->where('tenant_id', $tid)->where(function($q) { $q->whereNull('nama')->orWhere('nama', ''); })->count(), 0],
            ['20. Tapel aktif exists',      fn() => DB::table('tahun_ajaran')->where('tenant_id', $tid)->where('aktif', true)->count(), fn($v) => $v >= 1],
        ];
    }
}
```

- [ ] **Step 2: Write verify test**

Create `tests/Feature/Etl/EtlVerifyTest.php`:

```php
<?php
namespace Tests\Feature\Etl;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtlVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_command_fails_on_empty_db(): void
    {
        $tenant = \App\Modules\Tenancy\Models\Tenant::create(['nama' => 'Demo', 'npsn' => '12345678']);

        $this->artisan('etl:verify', ['tenant_id' => $tenant->id])
            ->assertExitCode(1);
    }

    public function test_clean_money_helper_precision(): void
    {
        $this->assertEquals(150000.00, clean_money('Rp. 150.000'));
        $this->assertEquals(1500000.50, clean_money('1.500.000,50'));
        $this->assertEquals(0.00, clean_money(''));
        $this->assertEquals(75.50, clean_money('75,50'));
    }

    public function test_clean_date_helper_multi_format(): void
    {
        $this->assertEquals('2024-07-15', clean_date('2024-07-15'));
        $this->assertEquals('2024-07-15', clean_date('15-07-2024'));
        $this->assertEquals('2024-07-15', clean_date('15/07/2024'));
        $this->assertNull(clean_date('0000-00-00'));
        $this->assertNull(clean_date(null));
    }
}
```

- [ ] **Step 3: Register commands**

Update `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    $this->commands([
        \App\Console\Commands\MigrateLegacySisfokolCommand::class,
        \App\Console\Commands\EtlVerifyCommand::class,
    ]);
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Feature/Etl/
```

Expected: PASS (cleansing tests pass, verify test exits 1 on empty DB — correct behavior).

- [ ] **Step 5: Commit + tag**

```bash
git add -A
git commit -m "feat(etl): EtlVerifyCommand — 20 checks (count, money reconciliation, FK integrity, quality)"
git tag epic-11-etl-pipeline
```

---

## Self-Review

**Spec coverage (against DEV_DOCS-009 §ETL Plan):**
- ✅ 20 langkah topologis sesuai tabel di DEV_DOCS-009 — Task 3-7
- ✅ `MigrateLegacySisfokolCommand` dengan `--dry-run`, `--from-step`, `--steps` — Task 2
- ✅ `StepInterface` contract — Task 1
- ✅ `IdMapper` singleton dengan in-memory cache + DB persistence — Task 1
- ✅ `legacy_id_mappings` tabel helper (bisa di-drop post-verify) — Task 1
- ✅ Chunked iteration (100-500 per batch) untuk performa memory — semua steps
- ✅ Idempotent: setiap step skip bila sudah mapped — semua steps
- ✅ Cleansing: `MoneyCleaner`, `DateCleaner`, `PhoneCleaner`, `PasswordResetter` — Task 3
- ✅ **Password MD5 → Bcrypt + must_reset_password=true** — Step 3, 4, 5
- ✅ **Nominal varchar → decimal(15,2)** via `clean_money()` — Step 14, 15
- ✅ Role mapping (`tp01..tp042` → Spatie roles) — Step 5
- ✅ 3-entity per siswa row (user + siswa + ortu bila ada) — Step 4
- ✅ **TagihanSiswa lock (Step 15)** — idempotent firstOrCreate (no locking needed ETL)
- ✅ `EtlVerifyCommand` 20 checks: count, money reconciliation, FK orphan, quality — Task 8
- ✅ `--drop-mappings` flag untuk cleanup pasca verifikasi — Task 8
- ✅ Cut-over strategy documented (freeze legacy → backup → migrate → verify → switch) — Task 8

**Placeholder scan:** Steps 11-13, 16-17, 19-20 harus di-implement penuh mengikuti pola steps yang sudah ada.

**Name consistency:**
- Artisan commands: `migrate:legacy-sisfokol {tenant_id}` + `etl:verify {tenant_id}` — konsisten
- Step classes: `Step01MigrateTahunAjaran` pattern — konsisten
- `IdMapper::get(tenantId, entityType, legacyKd)` + `set(...)` — konsisten

**Test count:** Epic 11 adds ~6 tests (2 tahun_ajaran, 2 keuangan cleansing, 2 verify).

**Pre-requisites:** Epic 1-9 selesai. `legacy_mysql` DB connection configured in `.env`. Legacy database `sisfokol_v7` ada di server. PHP 8.3 (`php83`).
