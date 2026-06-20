# Epic 5: Academic Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Build Academic module with 11 tables (siswa, orang_tua, siswa_orang_tua, guru, tahun_ajaran, semester, kelas, kelas_siswa, mapel, mapel_jenis, jadwal), full CRUD with tenant isolation + RBAC + field ACL, plus business logic services (`JadwalConflictChecker`, `KelasSiswaPromotionService`, `SiswaImportService`). Depends on Epic 1-4 (foundation, auth, RBAC, plugin infra).

**Architecture:** All models use `BelongsToTenant` + `TracksAuditColumns`. Controllers resource-based + authorize via Policy. KelasSiswa is a pivot per `tahun_ajaran_id` (preserves history — major fix vs SISFOKOL overwrite pattern). Jadwal has UNIQUE(tenant, tapel, smt, kelas, hari, jam_ke).

**Tech Stack:** Laravel Eloquent, Laravel Excel (import), Spatie permission.

**Spec reference:** design.md §7.1 Academic, ADR-003 (tenant), ADR-007 (skema), DEV_DOCS-003 §3.4.

---

## File Structure

- Create: `app/Modules/Academic/Database/Migrations/` (11 migrations)
- Create: `app/Modules/Academic/Models/{Siswa, OrangTua, SiswaOrangTua, Guru, TahunAjaran, Semester, Kelas, KelasSiswa, Mapel, MapelJenis, Jadwal}.php`
- Create: `app/Modules/Academic/Controllers/{SiswaController, GuruController, OrangTuaController, TahunAjaranController, SemesterController, KelasController, KelasSiswaController, MapelController, MapelJenisController, JadwalController}.php`
- Create: `app/Modules/Academic/Policies/{SiswaPolicy, GuruPolicy, KelasPolicy, JadwalPolicy}.php`
- Create: `app/Modules/Academic/Requests/{StoreSiswaRequest, UpdateSiswaRequest, StoreGuruRequest, StoreKelasRequest, StoreJadwalRequest, ...}.php`
- Create: `app/Modules/Academic/Services/{JadwalConflictChecker, KelasSiswaPromotionService, SiswaImportService}.php`
- Create: `app/Modules/Academic/Observers/{SiswaObserver, GuruObserver, ...}.php`
- Create: `app/Modules/Academic/routes.php`
- Create: `resources/views/academic/{siswa,guru,orang-tua,kelas,kelas-siswa,mapel,jadwal,tahun-ajaran,semester}/*.blade.php`
- Create: `database/factories/{SiswaFactory, GuruFactory, ...}.php`
- Create: `tests/Feature/Academic/{SiswaCrudTest, KelasSiswaPromotionTest, JadwalConflictTest, TenantIsolationTest}.php`

---

## Task 1: Migrations — 11 Academic tables

**Files:**
- Create: `app/Modules/Academic/Database/Migrations/2026_06_20_000100_*.php` (11 files)

- [ ] **Step 1: Create migration directory**

```bash
mkdir -p app/Modules/Academic/Database/Migrations
mkdir -p app/Modules/Academic/Models
mkdir -p app/Modules/Academic/{Controllers,Policies,Requests,Services,Observers}
```

- [ ] **Step 2: Write mapel_jenis migration (smallest, no FK deps)**

Create `app/Modules/Academic/Database/Migrations/2026_06_20_000100_create_mapel_jenis_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mapel_jenis', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 50);            // 'Wajib','Muatan Lokal','Peminatan'
            $table->string('kode', 30)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'nama']);
        });
    }
    public function down(): void { Schema::dropIfExists('mapel_jenis'); }
};
```

- [ ] **Step 3: Write tahun_ajaran + semester migrations**

Create `2026_06_20_000101_create_tahun_ajaran_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 20);            // '2026/2027'
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'nama']);
            $table->index(['tenant_id', 'aktif']);
        });
    }
    public function down(): void { Schema::dropIfExists('tahun_ajaran'); }
};
```

Create `2026_06_20_000102_create_semester_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('semester', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->tinyInteger('nama');             // 1 or 2
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'tahun_ajaran_id', 'nama']);
        });
    }
    public function down(): void { Schema::dropIfExists('semester'); }
};
```

- [ ] **Step 4: Write orang_tua + siswa + siswa_orang_tua migrations**

Create `2026_06_20_000103_create_orang_tua_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nama', 100);
            $table->enum('hubungan', ['ayah', 'ibu', 'wali'])->default('ayah');
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('username', 50)->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'username']);
        });
    }
    public function down(): void { Schema::dropIfExists('orang_tua'); }
};
```

Create `2026_06_20_000104_create_siswa_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nis', 30);
            $table->string('nisn', 30)->nullable();
            $table->string('nama', 100);
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('foto')->nullable();
            $table->string('agama', 20)->default('Islam');
            $table->enum('status', ['aktif', 'lulus', 'pindah', 'keluar'])->default('aktif');
            $table->string('qrcode', 100)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'nis']);
            $table->index(['tenant_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('siswa'); }
};
```

Create `2026_06_20_000105_create_siswa_orang_tua_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('siswa_orang_tua', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table, withSoftDelete: false);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('orang_tua_id');
            $table->foreign('orang_tua_id')->references('id')->on('orang_tua')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'siswa_id', 'orang_tua_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('siswa_orang_tua'); }
};
```

- [ ] **Step 5: Write guru migration**

Create `2026_06_20_000106_create_guru_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('nip', 30)->nullable();
            $table->string('nama', 100);
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'nip']);
        });
    }
    public function down(): void { Schema::dropIfExists('guru'); }
};
```

- [ ] **Step 6: Write kelas + kelas_siswa migrations**

Create `2026_06_20_000107_create_kelas_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->unsignedBigInteger('wali_kelas_id')->nullable();
            $table->foreign('wali_kelas_id')->references('id')->on('guru')->nullOnDelete();
            $table->string('nama', 30);              // '7-A'
            $table->tinyInteger('tingkat');          // 7/8/9
            $table->unsignedSmallInteger('kapasitas')->default(32);
            $table->timestamps();
            $table->index(['tenant_id', 'tingkat']);
        });
    }
    public function down(): void { Schema::dropIfExists('kelas'); }
};
```

Create `2026_06_20_000108_create_kelas_siswa_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kelas_siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table, withSoftDelete: false);
            $table->unsignedBigInteger('kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedSmallInteger('no_urut')->default(1);
            $table->timestamps();
            $table->unique(['tenant_id', 'tahun_ajaran_id', 'kelas_id', 'siswa_id']);
            $table->index(['tenant_id', 'tahun_ajaran_id', 'kelas_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('kelas_siswa'); }
};
```

- [ ] **Step 7: Write mapel migration**

Create `2026_06_20_000109_create_mapel_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mapel', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('kode', 30);
            $table->string('nama', 100);
            $table->unsignedBigInteger('mapel_jenis_id')->nullable();
            $table->foreign('mapel_jenis_id')->references('id')->on('mapel_jenis')->nullOnDelete();
            $table->decimal('kkm', 5, 2)->default(75.00);
            $table->unsignedBigInteger('kurikulum_id')->nullable();  // FK ke plugin Kurikulum (nullable — generic bila plugin nonaktif)
            $table->string('jenjang', 10)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'kode']);
        });
    }
    public function down(): void { Schema::dropIfExists('mapel'); }
};
```

> Note: `mapel.kurikulum_id` references `kurikulum.id` but that table only exists after Epic 9 (Kurikulum plugin). For this epic, leave it as a plain `unsignedBigInteger` (no FK constraint) — the FK is added in Epic 9 when the kurikulum table is created.

- [ ] **Step 8: Write jadwal migration**

Create `2026_06_20_000110_create_jadwal_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('semester_id');
            $table->foreign('semester_id')->references('id')->on('semester')->cascadeOnDelete();
            $table->unsignedBigInteger('kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
            $table->unsignedBigInteger('mapel_id');
            $table->foreign('mapel_id')->references('id')->on('mapel')->cascadeOnDelete();
            $table->unsignedBigInteger('guru_id');
            $table->foreign('guru_id')->references('id')->on('guru')->cascadeOnDelete();
            $table->tinyInteger('hari');             // 1=Senin..7=Minggu
            $table->tinyInteger('jam_ke');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruang', 30)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'tahun_ajaran_id', 'semester_id', 'kelas_id', 'hari', 'jam_ke'], 'uniq_jadwal_kelas_slot');
            $table->unique(['tenant_id', 'tahun_ajaran_id', 'semester_id', 'guru_id', 'hari', 'jam_ke'], 'uniq_jadwal_guru_slot');
            $table->index(['tenant_id', 'tahun_ajaran_id', 'semester_id', 'kelas_id', 'hari']);
        });
    }
    public function down(): void { Schema::dropIfExists('jadwal'); }
};
```

- [ ] **Step 9: Run migrate**

```bash
php artisan migrate
```
Expected: 11 Academic tables created. No FK errors.

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(academic): 11 migrations — siswa, orang_tua, guru, kelas, mapel, jadwal, etc"
```

---

## Task 2: 11 Models with BelongsToTenant + relations

**Files:**
- Create: `app/Modules/Academic/Models/{Siswa, OrangTua, SiswaOrangTua, Guru, TahunAjaran, Semester, Kelas, KelasSiswa, Mapel, MapelJenis, Jadwal}.php`
- Create: `database/factories/{SiswaFactory, GuruFactory, KelasFactory, TahunAjaranFactory, MapelFactory}.php`

- [ ] **Step 1: Create all models (show 3 key examples — others follow same pattern)**

Create `app/Modules/Academic/Models/Siswa.php`:

```php
<?php

namespace App\Modules\Academic\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = [
        'nis', 'nisn', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'telepon', 'foto', 'agama', 'status', 'qrcode',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function orangTuas(): BelongsToMany
    {
        return $this->belongsToMany(OrangTua::class, 'siswa_orang_tua');
    }

    public function kelasSiswa(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }
}
```

Create `app/Modules/Academic/Models/KelasSiswa.php` (the history-preserving pivot):

```php
<?php

namespace App\Modules\Academic\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasSiswa extends Model
{
    use BelongsToTenant, TracksAuditColumns;

    public $timestamps = true;

    protected $fillable = ['kelas_id', 'siswa_id', 'tahun_ajaran_id', 'no_urut'];

    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class); }
}
```

Create `app/Modules/Academic/Models/Jadwal.php`:

```php
<?php

namespace App\Modules\Academic\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = [
        'tahun_ajaran_id', 'semester_id', 'kelas_id', 'mapel_id', 'guru_id',
        'hari', 'jam_ke', 'jam_mulai', 'jam_selesai', 'ruang',
    ];

    protected function casts(): array
    {
        return ['jam_mulai' => 'datetime:H:i', 'jam_selesai' => 'datetime:H:i'];
    }

    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class); }
    public function mapel(): BelongsTo { return $this->belongsTo(Mapel::class); }
    public function guru(): BelongsTo { return $this->belongsTo(Guru::class); }
}
```

- [ ] **Step 2: Create remaining 8 models** (OrangTua, SiswaOrangTua, Guru, TahunAjaran, Semester, Kelas, Mapel, MapelJenis) following the same pattern with `use SoftDeletes, BelongsToTenant, TracksAuditColumns;`, fillables per DEV_DOCS-003 §3.4, and appropriate relations.

- [ ] **Step 3: Create factories for testing**

Create `database/factories/SiswaFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Modules\Academic\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiswaFactory extends Factory
{
    protected $model = Siswa::class;
    public function definition(): array
    {
        return [
            'nis' => $this->faker->unique()->numerify('##########'),
            'nisn' => $this->faker->optional()->numerify('##########'),
            'nama' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-15 years', '-10 years')->format('Y-m-d'),
            'telepon' => $this->faker->phoneNumber(),
            'agama' => 'Islam',
            'status' => 'aktif',
        ];
    }
}
```

Add `use HasFactory;` to `Siswa` model. Create `GuruFactory`, `KelasFactory`, `TahunAjaranFactory`, `MapelFactory` following same pattern.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(academic): 11 models + factories with BelongsToTenant + relations"
```

---

## Task 3: JadwalConflictChecker + Tests

**Files:**
- Create: `app/Modules/Academic/Services/JadwalConflictChecker.php`
- Create: `tests/Feature/Academic/JadwalConflictTest.php`

- [ ] **Step 1: Write conflict checker test**

Create `tests/Feature/Academic/JadwalConflictTest.php`:

```php
<?php

namespace Tests\Feature\Academic;

use App\Modules\Academic\Models\{Guru, Jadwal, Kelas, Mapel, Semester, TahunAjaran};
use App\Modules\Academic\Services\JadwalConflictChecker;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalConflictTest extends TestCase
{
    use RefreshDatabase;

    private JadwalConflictChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(JadwalConflictChecker::class);
    }

    public function test_no_conflict_for_new_jadwal(): void
    {
        $data = $this->setupScenario();
        $conflicts = $this->checker->validate($data['jadwal1_attrs']);
        $this->assertEmpty($conflicts);
    }

    public function test_conflict_same_kelas_same_slot(): void
    {
        $data = $this->setupScenario();
        // jadwal1 already exists, try to insert another jadwal at same slot for same kelas
        $newAttrs = array_merge($data['jadwal1_attrs'], ['mapel_id' => $data['mapel2']->id, 'guru_id' => $data['guru2']->id]);
        $conflicts = $this->checker->validate($newAttrs);
        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('kelas', implode('', $conflicts));
    }

    public function test_conflict_same_guru_same_slot(): void
    {
        $data = $this->setupScenario();
        // Use same guru, different kelas, same slot
        $newAttrs = array_merge($data['jadwal1_attrs'], [
            'kelas_id' => $data['kelas2']->id,
            'mapel_id' => $data['mapel2']->id,
            // guru_id stays same
        ]);
        $conflicts = $this->checker->validate($newAttrs);
        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('guru', implode('', $conflicts));
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);

        $tapel = TahunAjaran::create(['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'aktif' => true, 'tenant_id' => $tenant->id]);
        $smt = Semester::create(['tahun_ajaran_id' => $tapel->id, 'nama' => 1, 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-12-31', 'aktif' => true, 'tenant_id' => $tenant->id]);
        $kelas1 = Kelas::create(['nama' => '7-A', 'tingkat' => 7, 'kapasitas' => 32, 'tenant_id' => $tenant->id]);
        $kelas2 = Kelas::create(['nama' => '7-B', 'tingkat' => 7, 'kapasitas' => 32, 'tenant_id' => $tenant->id]);
        $mapel1 = Mapel::create(['kode' => 'MTH', 'nama' => 'Matematika', 'kkm' => 75, 'tenant_id' => $tenant->id]);
        $mapel2 = Mapel::create(['kode' => 'SCI', 'nama' => 'IPA', 'kkm' => 75, 'tenant_id' => $tenant->id]);
        $guru1 = Guru::create(['nip' => 'G1', 'nama' => 'Guru 1', 'tenant_id' => $tenant->id]);
        $guru2 = Guru::create(['nip' => 'G2', 'nama' => 'Guru 2', 'tenant_id' => $tenant->id]);

        $attrs = [
            'tenant_id' => $tenant->id, 'tahun_ajaran_id' => $tapel->id, 'semester_id' => $smt->id,
            'kelas_id' => $kelas1->id, 'mapel_id' => $mapel1->id, 'guru_id' => $guru1->id,
            'hari' => 1, 'jam_ke' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '07:40', 'ruang' => 'R1',
        ];
        Jadwal::create($attrs);

        return compact('tenant', 'tapel', 'smt', 'kelas1', 'kelas2', 'mapel1', 'mapel2', 'guru1', 'guru2', 'jadwal1_attrs');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Academic/JadwalConflictTest.php`
Expected: FAIL

- [ ] **Step 3: Implement JadwalConflictChecker**

Create `app/Modules/Academic/Services/JadwalConflictChecker.php`:

```php
<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\Jadwal;

class JadwalConflictChecker
{
    /**
     * Validate a jadwal payload. Return list of conflict messages (empty = no conflict).
     */
    public function validate(array $attrs, ?int $excludeId = null): array
    {
        $conflicts = [];

        // Kelas slot conflict
        $kelasQuery = Jadwal::withoutGlobalScope('tenant')
            ->where('tenant_id', $attrs['tenant_id'])
            ->where('tahun_ajaran_id', $attrs['tahun_ajaran_id'])
            ->where('semester_id', $attrs['semester_id'])
            ->where('kelas_id', $attrs['kelas_id'])
            ->where('hari', $attrs['hari'])
            ->where('jam_ke', $attrs['jam_ke']);
        if ($excludeId) $kelasQuery->where('id', '!=', $excludeId);
        if ($kelasQuery->exists()) {
            $conflicts[] = "Bentrok: kelas sudah ada jadwal di hari ke-{$attrs['hari']} jam ke-{$attrs['jam_ke']}.";
        }

        // Guru slot conflict
        $guruQuery = Jadwal::withoutGlobalScope('tenant')
            ->where('tenant_id', $attrs['tenant_id'])
            ->where('tahun_ajaran_id', $attrs['tahun_ajaran_id'])
            ->where('semester_id', $attrs['semester_id'])
            ->where('guru_id', $attrs['guru_id'])
            ->where('hari', $attrs['hari'])
            ->where('jam_ke', $attrs['jam_ke']);
        if ($excludeId) $guruQuery->where('id', '!=', $excludeId);
        if ($guruQuery->exists()) {
            $conflicts[] = "Bentrok: guru sudah mengajar di kelas lain pada hari ke-{$attrs['hari']} jam ke-{$attrs['jam_ke']}.";
        }

        return $conflicts;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Academic/JadwalConflictTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(academic): JadwalConflictChecker service + tests"
```

---

## Task 4: KelasSiswaPromotionService (naik kelas per tapel)

**Files:**
- Create: `app/Modules/Academic/Services/KelasSiswaPromotionService.php`
- Create: `tests/Feature/Academic/KelasSiswaPromotionTest.php`

- [ ] **Step 1: Write promotion test**

Create `tests/Feature/Academic/KelasSiswaPromotionTest.php`:

```php
<?php

namespace Tests\Feature\Academic;

use App\Modules\Academic\Models\{Kelas, KelasSiswa, Siswa, TahunAjaran};
use App\Modules\Academic\Services\KelasSiswaPromotionService;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasSiswaPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_promote_moves_siswa_to_next_kelas_in_new_tapel(): void
    {
        [$tenant, $t1, $t2, $k7A, $k8A, $siswa] = $this->setupScenario();
        $svc = app(KelasSiswaPromotionService::class);

        $svc->promote($t1->id, $t2->id, [[$k7A->id => $k8A->id]]);

        // Siswa now in kelas 8-A for tapel 2
        $this->assertDatabaseHas('kelas_siswa', [
            'siswa_id' => $siswa->id, 'kelas_id' => $k8A->id, 'tahun_ajaran_id' => $t2->id,
        ]);
        // Old kelas_siswa for t1 still intact (history preserved)
        $this->assertDatabaseHas('kelas_siswa', [
            'siswa_id' => $siswa->id, 'kelas_id' => $k7A->id, 'tahun_ajaran_id' => $t1->id,
        ]);
    }

    public function test_promote_idempotent_does_not_duplicate(): void
    {
        [$tenant, $t1, $t2, $k7A, $k8A, $siswa] = $this->setupScenario();
        $svc = app(KelasSiswaPromotionService::class);
        $svc->promote($t1->id, $t2->id, [[$k7A->id => $k8A->id]]);
        $svc->promote($t1->id, $t2->id, [[$k7A->id => $k8A->id]]); // again

        $count = KelasSiswa::where('siswa_id', $siswa->id)
            ->where('kelas_id', $k8A->id)
            ->where('tahun_ajaran_id', $t2->id)
            ->count();
        $this->assertSame(1, $count);
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $t1 = TahunAjaran::create(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30', 'tenant_id' => $tenant->id]);
        $t2 = TahunAjaran::create(['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'tenant_id' => $tenant->id]);
        $k7A = Kelas::create(['nama' => '7-A', 'tingkat' => 7, 'tenant_id' => $tenant->id]);
        $k8A = Kelas::create(['nama' => '8-A', 'tingkat' => 8, 'tenant_id' => $tenant->id]);
        $siswa = Siswa::factory()->create(['tenant_id' => $tenant->id]);
        KelasSiswa::create(['siswa_id' => $siswa->id, 'kelas_id' => $k7A->id, 'tahun_ajaran_id' => $t1->id, 'tenant_id' => $tenant->id, 'no_urut' => 1]);
        return [$tenant, $t1, $t2, $k7A, $k8A, $siswa];
    }
}
```

- [ ] **Step 2: Implement promotion service**

Create `app/Modules/Academic/Services/KelasSiswaPromotionService.php`:

```php
<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\KelasSiswa;
use Illuminate\Support\Facades\DB;

class KelasSiswaPromotionService
{
    /**
     * Promote siswa from $fromTapel to $toTapel based on kelas mapping.
     * $kelasMapping: [[oldKelasId => newKelasId], ...]
     * Idempotent — re-running on same tapel target won't duplicate.
     * ADR-003: History preserved — old kelas_siswa rows untouched.
     */
    public function promote(int $fromTapel, int $toTapel, array $kelasMapping): int
    {
        $moved = 0;
        DB::transaction(function () use ($fromTapel, $toTapel, $kelasMapping, &$moved) {
            foreach ($kelasMapping as $map) {
                foreach ($map as $oldKelasId => $newKelasId) {
                    $rows = KelasSiswa::withoutGlobalScope('tenant')
                        ->where('tahun_ajaran_id', $fromTapel)
                        ->where('kelas_id', $oldKelasId)
                        ->get();

                    foreach ($rows as $row) {
                        KelasSiswa::withoutGlobalScope('tenant')->firstOrCreate(
                            [
                                'tenant_id' => $row->tenant_id,
                                'siswa_id' => $row->siswa_id,
                                'kelas_id' => $newKelasId,
                                'tahun_ajaran_id' => $toTapel,
                            ],
                            ['no_urut' => $row->no_urut],
                        );
                        $moved++;
                    }
                }
            }
        });
        return $moved;
    }
}
```

- [ ] **Step 3: Run tests**

Run: `php artisan test tests/Feature/Academic/KelasSiswaPromotionTest.php`
Expected: PASS (2 tests)

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(academic): KelasSiswaPromotionService (naik kelas per tapel, history preserved)"
```

---

## Task 5: CRUD controllers + Policies + Views (Siswa example, others same pattern)

**Files:**
- Create: `app/Modules/Academic/Policies/SiswaPolicy.php`
- Create: `app/Modules/Academic/Requests/{StoreSiswaRequest, UpdateSiswaRequest}.php`
- Create: `app/Modules/Academic/Controllers/SiswaController.php`
- Create: `app/Modules/Academic/Observers/SiswaObserver.php`
- Create: `resources/views/academic/siswa/{index, create, edit, show}.blade.php`
- Modify: `app/Providers/AuthServiceProvider.php` (register SiswaPolicy)
- Create: `tests/Feature/Academic/SiswaCrudTest.php`

- [ ] **Step 1: Write CRUD test**

Create `tests/Feature/Academic/SiswaCrudTest.php`:

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, MenuSeeder, FieldSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, MenuSeeder::class, FieldSeeder::class, SuperAdminSeeder::class]);
    }

    public function test_admin_can_list_siswa(): void
    {
        $admin = User::where('username', 'admin')->first();
        Siswa::factory()->count(3)->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->actingAs($admin)->get('/academic/siswa');
        $response->assertStatus(200);
        $response->assertSee('Siswa');
    }

    public function test_admin_can_create_siswa(): void
    {
        $admin = User::where('username', 'admin')->first();
        $response = $this->actingAs($admin)->post('/academic/siswa', [
            'nis' => '12345', 'nama' => 'Siswa Test', 'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01', 'agama' => 'Islam', 'status' => 'aktif',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('siswa', ['nis' => '12345', 'nama' => 'Siswa Test', 'tenant_id' => $admin->tenant_id]);
    }

    public function test_guru_cannot_create_siswa(): void
    {
        $tenant = Tenant::create(['nama' => 'T2', 'npsn' => '22222222']);
        $guru = User::factory()->create(['tenant_id' => $tenant->id]);
        $guru->assignRole('guru');

        $response = $this->actingAs($guru)->post('/academic/siswa', [
            'nis' => '99', 'nama' => 'Test', 'jenis_kelamin' => 'L',
        ]);
        $response->assertStatus(403);
    }

    public function test_siswa_tenant_isolation(): void
    {
        $t1 = Tenant::create(['nama' => 'TA', 'npsn' => '11111111']);
        $t2 = Tenant::create(['nama' => 'TB', 'npsn' => '22222222']);
        $s1 = Siswa::withoutGlobalScope('tenant')->create(['nis' => 'A1', 'nama' => 'A', 'tenant_id' => $t1->id, 'jenis_kelamin' => 'L']);
        $s2 = Siswa::withoutGlobalScope('tenant')->create(['nis' => 'B1', 'nama' => 'B', 'tenant_id' => $t2->id, 'jenis_kelamin' => 'L']);

        $adminT1 = User::factory()->create(['tenant_id' => $t1->id]);
        $adminT1->assignRole('admin_sekolah');

        $response = $this->actingAs($adminT1)->get('/academic/siswa');
        $response->assertSee('A1');
        $response->assertDontSee('B1');
    }
}
```

- [ ] **Step 2: Implement SiswaPolicy**

Create `app/Modules/Academic/Policies/SiswaPolicy.php`:

```php
<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Models\Siswa;

class SiswaPolicy
{
    public function viewAny(User $user): bool { return $user->can('siswa.view'); }
    public function view(User $user, Siswa $siswa): bool
    {
        return $user->isSuperAdmin() || ($user->can('siswa.view') && $user->tenant_id === $siswa->tenant_id);
    }
    public function create(User $user): bool { return $user->can('siswa.create') || $user->can('siswa.manage'); }
    public function update(User $user, Siswa $siswa): bool
    {
        return ($user->can('siswa.update') || $user->can('siswa.manage'))
            && ($user->isSuperAdmin() || $user->tenant_id === $siswa->tenant_id);
    }
    public function delete(User $user, Siswa $siswa): bool
    {
        return ($user->can('siswa.delete') || $user->can('siswa.manage'))
            && ($user->isSuperAdmin() || $user->tenant_id === $siswa->tenant_id);
    }
}
```

- [ ] **Step 3: Implement FormRequests**

Create `app/Modules/Academic/Requests/StoreSiswaRequest.php`:

```php
<?php

namespace App\Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiswaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id ?? 'NULL';
        return [
            'nis'           => ['required', 'string', 'max:30', "unique:siswa,nis,NULL,id,tenant_id,{$tenantId}"],
            'nisn'          => ['nullable', 'string', 'max:30'],
            'nama'          => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date'],
            'telepon'       => ['nullable', 'string', 'max:30'],
            'agama'         => ['nullable', 'string', 'max:20'],
            'status'        => ['nullable', 'in:aktif,lulus,pindah,keluar'],
        ];
    }
}
```

Create `UpdateSiswaRequest.php` (same rules but `nis` excludes current record).

- [ ] **Step 4: Implement SiswaController**

Create `app/Modules/Academic/Controllers/SiswaController.php`:

```php
<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\Siswa;
use App\Modules\Academic\Requests\{StoreSiswaRequest, UpdateSiswaRequest};
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Siswa::class);
        $siswa = Siswa::query();
        if ($request->filled('q')) $siswa->where('nama', 'like', "%{$request->q}%")->orWhere('nis', 'like', "%{$request->q}%");
        $columns = \App\Support\FieldAcl::columnsForIndex('Siswa'); // Field ACL per ADR-010
        return view('academic.siswa.index', [
            'siswa' => $siswa->paginate(25),
            'columns' => $columns,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Siswa::class);
        return view('academic.siswa.create');
    }

    public function store(StoreSiswaRequest $request)
    {
        $this->authorize('create', Siswa::class);
        Siswa::create($request->validated());
        return redirect()->route('siswa.index')->with('status', 'Siswa ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        $this->authorize('update', $siswa);
        return view('academic.siswa.edit', compact('siswa'));
    }

    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        $this->authorize('update', $siswa);
        $siswa->update($request->validated());
        return redirect()->route('siswa.index')->with('status', 'Siswa diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $this->authorize('delete', $siswa);
        $siswa->delete();
        return back()->with('status', 'Siswa dihapus.');
    }
}
```

- [ ] **Step 5: Implement SiswaObserver**

Create `app/Modules/Academic/Observers/SiswaObserver.php`:

```php
<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\Siswa;
use App\Modules\Auth\Services\AuditLogger;

class SiswaObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(Siswa $siswa): void
    {
        $this->audit->log('siswa.created', auth()->user(), $siswa->only(['id', 'nis', 'nama']), request(), modelType: Siswa::class, modelId: $siswa->id);
        event(new \App\Modules\Academic\Events\SiswaRegistered($siswa));
    }

    public function updated(Siswa $siswa): void
    {
        $this->audit->log('siswa.updated', auth()->user(), $siswa->getChanges(), request(), $siswa->getOriginal(), Siswa::class, $siswa->id);
    }

    public function deleted(Siswa $siswa): void
    {
        $this->audit->log('siswa.deleted', auth()->user(), ['id' => $siswa->id], request(), modelType: Siswa::class, modelId: $siswa->id);
    }
}
```

Create event class `app/Modules/Academic/Events/SiswaRegistered.php`:

```php
<?php

namespace App\Modules\Academic\Events;

use App\Modules\Academic\Models\Siswa;

class SiswaRegistered
{
    public function __construct(public Siswa $siswa) {}
}
```

- [ ] **Step 6: Register observer + policy**

Edit `app/Providers/EventServiceProvider.php`:

```php
public function boot(): void
{
    \App\Models\User::observe(\App\Modules\Auth\Observers\UserObserver::class);
    \App\Modules\Academic\Models\Siswa::observe(\App\Modules\Academic\Observers\SiswaObserver::class);
}
```

Edit `app/Providers/AuthServiceProvider.php` `$policies`:

```php
protected $policies = [
    \App\Modules\Auth\Models\AuditLog::class => \App\Modules\Auth\Policies\AuditLogPolicy::class,
    \App\Modules\Academic\Models\Siswa::class => \App\Modules\Academic\Policies\SiswaPolicy::class,
];
```

- [ ] **Step 7: Create views (index with field ACL)**

Create `resources/views/academic/siswa/index.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Siswa')
@section('content')
<h1>Siswa</h1>
<a href="{{ route('siswa.create') }}" class="btn btn-primary mb-2">Tambah Siswa</a>
<form class="mb-2"><input name="q" class="form-control" placeholder="Cari nama/NIS..." value="{{ request('q') }}"></form>
<table class="table table-bordered table-sm">
    <thead><tr>
        <th>NIS</th><th>Nama</th>
        @if(in_array('telepon', $columns))<th>Telepon</th>@endif
        <th>Status</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    @foreach($siswa as $s)
        <tr>
            <td>{{ $s->nis }}</td>
            <td>{{ $s->nama }}</td>
            @if(in_array('telepon', $columns))<td>{{ $s->telepon }}</td>@endif
            <td><span class="badge bg-{{ $s->status === 'aktif' ? 'success' : 'secondary' }}">{{ $s->status }}</span></td>
            <td>
                @can('siswa.update', $s)<a href="{{ route('siswa.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a>@endcan
                @can('siswa.delete', $s)
                    <form method="POST" action="{{ route('siswa.destroy', $s) }}" class="d-inline">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">Hapus</button>
                    </form>
                @endcan
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $siswa->links() }}
@endsection
```

Create `resources/views/academic/siswa/create.blade.php` and `edit.blade.php` with form using `@field('siswa.telepon')` directive.

- [ ] **Step 8: Add routes**

Create `app/Modules/Academic/routes.php`:

```php
<?php

use App\Modules\Academic\Controllers\SiswaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:siswa.view'])->group(function () {
    Route::resource('academic/siswa', SiswaController::class)
        ->middleware(['permission:siswa.view']);
});
```

ModuleServiceProvider auto-loads `Modules/Academic/routes.php`.

- [ ] **Step 9: Run tests**

Run: `php artisan test tests/Feature/Academic/SiswaCrudTest.php`
Expected: PASS (4 tests)

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(academic): Siswa CRUD (controller, policy, requests, observer, views, field ACL)"
```

---

## Task 6: Remaining 9 CRUDs (Guru, OrangTua, Kelas, KelasSiswa, Mapel, MapelJenis, Jadwal, TahunAjaran, Semester)

**Files:** Same pattern as Task 5 per entity.

- [ ] **Step 1: Implement GuruController + Policy + views**

Follow Task 5 pattern. Permission: `guru.manage` / `guru.view`. Routes: `Route::resource('academic/guru', GuruController::class)`.

- [ ] **Step 2: Implement OrangTuaController** (permission `user.manage` for admin or `bk`)

- [ ] **Step 3: Implement TahunAjaranController + SemesterController** with `set-aktif` action:

```php
// TahunAjaranController@setAktif:
public function setAktif(Request $request, TahunAjaran $tapel)
{
    $this->authorize('tahun_ajaran.manage');
    DB::transaction(function () use ($tapel, $request) {
        TahunAjaran::where('tenant_id', $request->user()->tenant_id)->update(['aktif' => false]);
        $tapel->update(['aktif' => true]);
        // Update tenant_settings
        TenantSetting::updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id, 'key' => 'tapel_aktif_id'],
            ['value' => $tapel->id],
        );
    });
    return back()->with('status', "Tahun ajaran {$tapel->nama} diaktifkan.");
}
```

- [ ] **Step 4: Implement KelasController** (with wali_kelas_id dropdown from Guru)

- [ ] **Step 5: Implement KelasSiswaController** (assign siswa to kelas per tapel — bulk add via multi-select)

- [ ] **Step 6: Implement MapelController + MapelJenisController** (with mapel_jenis dropdown)

- [ ] **Step 7: Implement JadwalController** with conflict validation:

```php
public function store(StoreJadwalRequest $request)
{
    $this->authorize('create', Jadwal::class);
    $attrs = $request->validated();
    $conflicts = $this->checker->validate($attrs);
    if (!empty($conflicts)) {
        return back()->withErrors(['jadwal' => $conflicts])->withInput();
    }
    Jadwal::create($attrs);
    return redirect()->route('jadwal.index')->with('status', 'Jadwal dibuat.');
}
```

- [ ] **Step 8: Update routes.php to register all 9 controllers**

```php
Route::middleware(['auth'])->group(function () {
    Route::resource('academic/guru', GuruController::class)->middleware('permission:guru.view');
    Route::resource('academic/orang-tua', OrangTuaController::class)->middleware('permission:user.manage');
    Route::resource('academic/tahun-ajaran', TahunAjaranController::class)->middleware('permission:tahun_ajaran.manage');
    Route::post('academic/tahun-ajaran/{tapel}/set-aktif', [TahunAjaranController::class, 'setAktif'])->name('tahun-ajaran.set-aktif');
    Route::resource('academic/semester', SemesterController::class)->middleware('permission:tahun_ajaran.manage');
    Route::resource('academic/kelas', KelasController::class)->middleware('permission:kelas.view');
    Route::resource('academic/kelas/{kelas}/siswa', KelasSiswaController::class)->middleware('permission:kelas.view');
    Route::resource('academic/mapel', MapelController::class)->middleware('permission:mapel.view');
    Route::resource('academic/mapel-jenis', MapelJenisController::class)->middleware('permission:mapel.manage');
    Route::resource('academic/jadwal', JadwalController::class)->middleware('permission:jadwal.view');
});
```

- [ ] **Step 9: Commit + tag**

```bash
git add -A
git commit -m "feat(academic): remaining 9 CRUDs + tapel/semester set-aktif + jadwal conflict"
git tag epic-5-academic
```

---

## Self-Review

**Spec coverage (against DEV_DOCS-003 §3.4, DEV_DOCS-009 §5.3):**
- ✅ All 11 tables migrated (siswa, orang_tua, siswa_orang_tua, guru, tahun_ajaran, semester, kelas, kelas_siswa, mapel, mapel_jenis, jadwal) — Task 1
- ✅ BelongsToTenant + TracksAuditColumns on all models — Task 2
- ✅ NIS/NIP unique per tenant (UNIQUE constraint) — Task 1 Step 4/5
- ✅ kelas_siswa history-preserving pivot per tapel — Task 1 Step 6
- ✅ orang_tua separated from siswa (normalization) — Task 1 Step 4
- ✅ JadwalConflictChecker (kelas + guru slot) — Task 3
- ✅ KelasSiswaPromotionService (idempotent naik kelas) — Task 4
- ✅ Siswa CRUD + Policy + Observer + field ACL — Task 5
- ✅ Remaining 9 CRUDs — Task 6
- ✅ Tahun Ajaran/Semester set-aktif → tenant_settings — Task 6 Step 3
- ⏭️ SiswaImportService (Laravel Excel) — deferred to Epic 12 (testing) or ad-hoc — implementation note only

**Placeholder scan:** Task 6 steps 1-6 say "follow Task 5 pattern" — that's intentional pattern repetition, not a code gap. Each controller has the same skeleton; engineer reproduces the Siswa pattern. If the engineer wants explicit code per controller, Epic 5 can be split further.

**Name consistency:**
- `BelongsToTenant` trait used in all models + via `tenant_and_audit_columns()` helper in migrations.
- `JadwalConflictChecker::validate($attrs, $excludeId)` — consistent.
- `KelasSiswaPromotionService::promote($fromTapel, $toTapel, $kelasMapping)` — consistent.
- Route names: `siswa.index`, `siswa.create`, ..., `jadwal.index`, `tahun-ajaran.set-aktif`.

**Test count:** Epic 5 adds ~13 tests (3 jadwal + 2 promotion + 4 siswa CRUD + 4 others as pattern).

**Note on Task 6**: For brevity this plan groups the 9 remaining CRUDs into one task with explicit pattern references. A subagent executing Task 6 should produce 9 commits (one per controller) to keep changes reviewable. The Siswa pattern (Task 5) is the canonical reference.
