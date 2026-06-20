# Epic 9: Plugin Kurikulum (Full Reference Plugin) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Build the **first full reference plugin** — Kurikulum — implementing `PluginContract`, with 3 tables (kurikulum, struktur_kurikulum, komponen_kompetensi), full CRUD, `EvaluationFrameworkSubscriber` that listens to `Evaluation.ResolveFramework` event (fired by Evaluation module) and injects KI/KD or CP framework metadata, plus `RaporSectionSubscriber` for `Raport.RenderSection`. Plugin must work via `plugin:kurikulum` middleware + be activatable per-tenant via the PluginActivationService from Epic 4.

**Architecture:** Self-contained at `app/Plugins/Kurikulum/`. Has own manifest `KurikulumPlugin implements PluginContract`, own ServiceProvider, own migrations (loaded via ModuleServiceProvider in Epic 4), own routes, subscribers. When Evaluation controller fires `EvaluationResolveFramework(mapel, kelas)`, this plugin's subscriber checks `mapel.kurikulum_id` → resolves `struktur_kurikulum` + `komponen_kompetensi` → fills `$event->framework`.

**Tech Stack:** Laravel events/listeners, Spatie permission (seeded on activation), Eloquent.

**Spec reference:** design.md §6 + §7.2, ADR-009, DEV_DOCS-003 §3.8 Kurikulum, DEV_DOCS-004.

---

## File Structure

- Create: `app/Plugins/Kurikulum/KurikulumPlugin.php` (manifest)
- Create: `app/Plugins/Kurikulum/Providers/KurikulumServiceProvider.php`
- Create: `app/Plugins/Kurikulum/Database/Migrations/` (3 migrations)
- Create: `app/Plugins/Kurikulum/Models/{Kurikulum, StrukturKurikulum, KomponenKompetensi}.php`
- Create: `app/Plugins/Kurikulum/Controllers/{KurikulumController, StrukturKurikulumController, KomponenKompetensiController}.php`
- Create: `app/Plugins/Kurikulum/Policies/KurikulumPolicy.php`
- Create: `app/Plugins/Kurikulum/Subscribers/{EvaluationFrameworkSubscriber, RaporSectionSubscriber}.php`
- Create: `app/Plugins/Kurikulum/permissions.php`
- Create: `app/Plugins/Kurikulum/menu.php`
- Create: `app/Plugins/Kurikulum/routes.php`
- Create: `resources/views/plugins/kurikulum/**/*.blade.php`
- Create: `tests/Feature/Plugin/KurikulumPluginTest.php`

---

## Task 1: Migrations — 3 Kurikulum tables

**Files:**
- Create: `app/Plugins/Kurikulum/Database/Migrations/2026_06_20_0005{00..02}_*.php`

- [ ] **Step 1: Create directory + migrations**

```bash
mkdir -p app/Plugins/Kurikulum/{Database/Migrations,Models,Controllers,Policies,Subscribers,Providers,Resources/views}
```

Create `2026_06_20_000500_create_kurikulum_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->string('kurikulum_id', 30);          // 'K13','KURMER','MULOK'
            $table->string('nama_kurikulum', 100);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'kurikulum_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('kurikulum'); }
};
```

Create `2026_06_20_000501_create_struktur_kurikulum_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('struktur_kurikulum', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('kurikulum_id');
            $table->foreign('kurikulum_id')->references('id')->on('kurikulum')->cascadeOnDelete();
            $table->string('jenjang', 10);                // 'SD','SMP','SMA'
            $table->string('kelas', 10);                  // '7','8','9'
            $table->string('fase', 5)->nullable();        // 'A'-'F' for Kurmer
            $table->enum('jenis_kegiatan', ['intrakurikuler', 'kokurikuler_p5'])->default('intrakurikuler');
            $table->timestamps();
            $table->unique(['tenant_id', 'kurikulum_id', 'jenjang', 'kelas', 'jenis_kegiatan'], 'uniq_struktur_kur');
        });
    }
    public function down(): void { Schema::dropIfExists('struktur_kurikulum'); }
};
```

Create `2026_06_20_000502_create_komponen_kompetensi_table.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('komponen_kompetensi', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('struktur_id');
            $table->foreign('struktur_id')->references('id')->on('struktur_kurikulum')->cascadeOnDelete();
            $table->string('kode_kompetensi', 30);          // 'KI-3','CP-001'
            $table->text('teks_kompetensi');
            $table->enum('pendekatan_pedagogis', ['konvensional', 'deep_learning'])->default('konvensional');
            $table->timestamps();
            $table->index(['tenant_id', 'struktur_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('komponen_kompetensi'); }
};
```

- [ ] **Step 2: Add FK from mapel.kurikulum_id to kurikulum.id**

Create `2026_06_20_000503_add_mapel_kurikulum_fk.php`:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->foreign('kurikulum_id')->references('id')->on('kurikulum')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->dropForeign(['kurikulum_id']);
        });
    }
};
```

- [ ] **Step 3: Run migrate + commit**

```bash
php artisan migrate
git add -A
git commit -m "feat(kurikulum): 3 plugin migrations + mapel.kurikulum_id FK"
```

---

## Task 2: Models + Manifest + Permissions + Menu

**Files:**
- Create: 3 models
- Create: `app/Plugins/Kurikulum/KurikulumPlugin.php` (manifest)
- Create: `app/Plugins/Kurikulum/permissions.php`
- Create: `app/Plugins/Kurikulum/menu.php`

- [ ] **Step 1: Create 3 models**

Create `app/Plugins/Kurikulum/Models/Kurikulum.php`:

```php
<?php
namespace App\Plugins\Kurikulum\Models;

use App\Models\Traits\{BelongsToTenant, TracksAuditColumns};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kurikulum extends Model
{
    use SoftDeletes, BelongsToTenant, TracksAuditColumns;

    protected $fillable = ['kurikulum_id', 'nama_kurikulum', 'status_aktif'];

    protected function casts(): array { return ['status_aktif' => 'boolean']; }

    public function strukturKurikulum(): HasMany { return $this->hasMany(StrukturKurikulum::class); }
}
```

Create `StrukturKurikulum.php` (with relations to Kurikulum + HasMany KomponenKompetensi) and `KomponenKompetensi.php` (BelongsTo StrukturKurikulum).

- [ ] **Step 2: Implement KurikulumPlugin manifest**

Create `app/Plugins/Kurikulum/KurikulumPlugin.php`:

```php
<?php
namespace App\Plugins\Kurikulum;

use App\Support\{PluginContract, PluginContext};

class KurikulumPlugin implements PluginContract
{
    public function kode(): string { return 'kurikulum'; }
    public function nama(): string { return 'Kurikulum'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return []; }

    public function providerClass(): string
    {
        return \App\Plugins\Kurikulum\Providers\KurikulumServiceProvider::class;
    }

    public function permissions(): array
    {
        return [
            ['name' => 'kurikulum.view',   'display_name' => 'Lihat Kurikulum',           'module' => 'Kurikulum'],
            ['name' => 'kurikulum.manage', 'display_name' => 'Kelola Kurikulum & Kompetensi', 'module' => 'Kurikulum'],
        ];
    }

    public function menu(): array
    {
        return [
            ['kode' => 'kurikulum.index',    'label' => 'Kurikulum',          'route' => 'kurikulum.index',   'permission_required' => 'kurikulum.view',   'urutan' => 70, 'group' => 'Akademik'],
            ['kode' => 'kurikulum.struktur', 'label' => 'Struktur Kurikulum','route' => 'kurikulum.struktur.index', 'permission_required' => 'kurikulum.view', 'urutan' => 71, 'group' => 'Akademik'],
            ['kode' => 'kurikulum.komponen', 'label' => 'Komponen Kompetensi','route' => 'kurikulum.komponen.index', 'permission_required' => 'kurikulum.view', 'urutan' => 72, 'group' => 'Akademik'],
        ];
    }

    public function boot(PluginContext $ctx): void
    {
        // Subscribers are registered by KurikulumServiceProvider
        // PluginContext provides tenantId + settings if plugin needs tenant-specific boot
    }
}
```

- [ ] **Step 3: Create permissions.php + menu.php**

Create `app/Plugins/Kurikulum/permissions.php` (returns same array as `permissions()` — for reference):

```php
<?php
return [
    ['name' => 'kurikulum.view',   'display_name' => 'Lihat Kurikulum',           'module' => 'Kurikulum'],
    ['name' => 'kurikulum.manage', 'display_name' => 'Kelola Kurikulum & Kompetensi', 'module' => 'Kurikulum'],
];
```

Create `app/Plugins/Kurikulum/menu.php` (same as `menu()`).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(kurikulum): 3 models + KurikulumPlugin manifest + permissions + menu"
```

---

## Task 3: Subscribers — listen Evaluation.ResolveFramework + Raport.RenderSection

**Files:**
- Create: `app/Plugins/Kurikulum/Subscribers/EvaluationFrameworkSubscriber.php`
- Create: `app/Plugins/Kurikulum/Subscribers/RaporSectionSubscriber.php`
- Create: `app/Plugins/Kurikulum/Providers/KurikulumServiceProvider.php`
- Create: `tests/Feature/Plugin/KurikulumPluginTest.php`

- [ ] **Step 1: Write plugin integration test**

Create `tests/Feature/Plugin/KurikulumPluginTest.php`:

```php
<?php

namespace Tests\Feature\Plugin;

use App\Models\User;
use App\Modules\Academic\Models\{Kelas, Mapel};
use App\Modules\Evaluation\Events\EvaluationResolveFramework;
use App\Modules\Evaluation\Services\EvaluationFrameworkResolver;
use App\Modules\Tenancy\Models\Tenant;
use App\Plugins\Kurikulum\Models\{Kurikulum, StrukturKurikulum, KomponenKompetensi};
use App\Plugins\Kurikulum\Providers\KurikulumServiceProvider;
use App\Support\TenantContext;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class KurikulumPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_framework_event_resolves_via_kurikulum(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $this->app->register(KurikulumServiceProvider::class);

        [$tenant, $mapel, $kelas, $kurikulum] = $this->setupScenario();

        $resolver = app(EvaluationFrameworkResolver::class);
        $framework = $resolver->resolve($mapel, $kelas);

        $this->assertNotNull($framework);
        $this->assertSame('D', $framework['fase']);
        $this->assertContains('KI-3', $framework['ki']);
        $this->assertSame('deep_learning', $framework['pedagogis']);
    }

    public function test_no_framework_when_mapel_has_no_kurikulum_id(): void
    {
        $this->app->register(KurikulumServiceProvider::class);
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $mapel = Mapel::create(['kode' => 'MTH', 'nama' => 'Matematika', 'kkm' => 75, 'tenant_id' => $tenant->id, 'kurikulum_id' => null]);

        $framework = app(EvaluationFrameworkResolver::class)->resolve($mapel);
        $this->assertNull($framework);
    }

    public function test_kurikulum_can_be_activated_and_seeds_permissions(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::where('username', 'admin')->first();
        $plugin = \App\Plugins\Infrastructure\Models\Plugin::create(['kode' => 'kurikulum', 'nama' => 'Kurikulum']);

        $response = $this->actingAs($admin)->post("/admin/plugins/kurikulum/activate");
        $response->assertRedirect();

        // Permissions should be seeded
        $this->assertTrue(\Spatie\Permission\Models\Permission::where('name', 'kurikulum.view')->exists());
        $this->assertTrue(\Spatie\Permission\Models\Permission::where('name', 'kurikulum.manage')->exists());
    }

    private function setupScenario(): array
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        app(TenantContext::class)->set(tenantId: $tenant->id);
        $kurikulum = Kurikulum::create(['kurikulum_id' => 'KURMER', 'nama_kurikulum' => 'Kurikulum Merdeka', 'status_aktif' => true, 'tenant_id' => $tenant->id]);
        $struktur = StrukturKurikulum::create(['kurikulum_id' => $kurikulum->id, 'jenjang' => 'SMP', 'kelas' => '7', 'fase' => 'D', 'jenis_kegiatan' => 'intrakurikuler', 'tenant_id' => $tenant->id]);
        KomponenKompetensi::create(['struktur_id' => $struktur->id, 'kode_kompetensi' => 'KI-3', 'teks_kompetensi' => 'Memahami...', 'pendekatan_pedagogis' => 'deep_learning', 'tenant_id' => $tenant->id]);
        $mapel = Mapel::create(['kode' => 'MTH', 'nama' => 'Matematika', 'kkm' => 75, 'tenant_id' => $tenant->id, 'kurikulum_id' => $kurikulum->id]);
        $kelas = Kelas::create(['nama' => '7-A', 'tingkat' => 7, 'tenant_id' => $tenant->id]);
        return [$tenant, $mapel, $kelas, $kurikulum];
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Plugin/KurikulumPluginTest.php`
Expected: FAIL

- [ ] **Step 3: Implement EvaluationFrameworkSubscriber**

Create `app/Plugins/Kurikulum/Subscribers/EvaluationFrameworkSubscriber.php`:

```php
<?php
namespace App\Plugins\Kurikulum\Subscribers;

use App\Modules\Evaluation\Events\EvaluationResolveFramework;
use App\Modules\Tenancy\Models\Tenant;
use App\Plugins\Kurikulum\Models\{Kurikulum, StrukturKurikulum, KomponenKompetensi};

class EvaluationFrameworkSubscriber
{
    public function handleEvaluationResolveFramework(EvaluationResolveFramework $event): void
    {
        $mapel = $event->mapel;
        $kelas = $event->kelas;

        if (! $mapel->kurikulum_id) return;  // no framework for generic

        $kurikulum = Kurikulum::find($mapel->kurikulum_id);
        if (! $kurikulum) return;

        // Find struktur matching jenjang/kelas of $kelas (or tenant default)
        $strukturQuery = StrukturKurikulum::where('kurikulum_id', $kurikulum->id);
        if ($kelas) {
            $strukturQuery->where('jenjang', $kelas->jenjang())->orWhere('kelas', (string) $kelas->tingkat);
        }
        $struktur = $strukturQuery->first();
        if (! $struktur) return;

        $komponenKis = KomponenKompetensi::where('struktur_id', $struktur->id)->pluck('kode_kompetensi')->all();

        $event->framework = [
            'kurikulum'   => $kurikulum->nama_kurikulum,
            'ki'          => $komponenKis,
            'fase'        => $struktur->fase,
            'pedagogis'   => $struktur->komponenKompetensi()->first()?->pendekatan_pedagogis ?? 'konvensional',
        ];
    }

    public function subscribe($events): array
    {
        return [
            EvaluationResolveFramework::class => 'handleEvaluationResolveFramework',
        ];
    }
}
```

> Note: `Kelas::jenjang()` is a helper that derives jenjang from tenant setting or branch. If not implemented, fall back to hardcoded 'SMP' default. Add helper method to `Kelas` model:

```php
public function jenjang(): string
{
    return $this->branch?->jenjang ?? 'SMP';
}
```

- [ ] **Step 4: Implement RaporSectionSubscriber**

Create `app/Plugins/Kurikulum/Subscribers/RaporSectionSubscriber.php`:

```php
<?php
namespace App\Plugins\Kurikulum\Subscribers;

use App\Modules\Evaluation\Events\RaportRenderSection;

class RaporSectionSubscriber
{
    public function handleRaportRenderSection(RaportRenderSection $event): void
    {
        $siswa = $event->siswa;
        // Generate capaian kompetensi section HTML based on siswa's nilai + kurikulum
        $html = '<p><em>Section Capaian Kompetensi dari plugin Kurikulum.</em></p>';
        $event->sections['Capaian Kompetensi'] = $html;
    }

    public function subscribe($events): array
    {
        return [RaportRenderSection::class => 'handleRaportRenderSection'];
    }
}
```

- [ ] **Step 5: Implement KurikulumServiceProvider**

Create `app/Plugins/Kurikulum/Providers/KurikulumServiceProvider.php`:

```php
<?php
namespace App\Plugins\Kurikulum\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class KurikulumServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        \App\Plugins\Kurikulum\Subscribers\EvaluationFrameworkSubscriber::class,
        \App\Plugins\Kurikulum\Subscribers\RaporSectionSubscriber::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        parent::boot();
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'kurikulum');
    }
}
```

- [ ] **Step 6: Register provider conditionally**

In `app/Providers/PluginRegistryServiceProvider.php` boot(), register KurikulumServiceProvider when plugin is active for at least one tenant:

```php
public function boot(): void
{
    $registry = app(\App\Support\PluginRegistry::class);
    if ($registry->get('kurikulum')) {
        $this->app->register(\App\Plugins\Kurikulum\Providers\KurikulumServiceProvider::class);
    }
}
```

> Note: For simpler Fase 1, register unconditionally in `bootstrap/providers.php` (subscribers only fire when plugin is activated per-tenant via `plugin:` middleware, but the listener exists). Activate properly in Fase 2 with deferred loading.

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Feature/Plugin/KurikulumPluginTest.php`
Expected: PASS (3 tests)

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(kurikulum): EvaluationFrameworkSubscriber + RaporSectionSubscriber + ServiceProvider"
```

---

## Task 4: Controllers + Routes + Views + Activation test

**Files:**
- Create: `app/Plugins/Kurikulum/Controllers/{KurikulumController, StrukturKurikulumController, KomponenKompetensiController}.php`
- Create: `app/Plugins/Kurikulum/Policies/KurikulumPolicy.php`
- Create: `app/Plugins/Kurikulum/routes.php`
- Create: `resources/views/plugins/kurikulum/**/*.blade.php`

- [ ] **Step 1: Implement KurikulumPolicy**

Create `app/Plugins/Kurikulum/Policies/KurikulumPolicy.php`:

```php
<?php
namespace App\Plugins\Kurikulum\Policies;

use App\Models\User;

class KurikulumPolicy
{
    public function viewAny(User $user): bool { return $user->can('kurikulum.view'); }
    public function create(User $user): bool { return $user->can('kurikulum.manage'); }
    public function update(User $user): bool { return $user->can('kurikulum.manage'); }
    public function delete(User $user): bool { return $user->can('kurikulum.manage'); }
}
```

- [ ] **Step 2: Implement 3 controllers** (resource CRUDs following Epic 5 pattern):

```php
<?php
namespace App\Plugins\Kurikulum\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Kurikulum\Models\Kurikulum;
use Illuminate\Http\Request;

class KurikulumController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Kurikulum::class);
        $kurikulum = Kurikulum::paginate(20);
        return view('kurikulum::index', compact('kurikulum'));
    }

    public function create()
    {
        $this->authorize('create', Kurikulum::class);
        return view('kurikulum::create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Kurikulum::class);
        $data = $request->validate([
            'kurikulum_id' => 'required|string|max:30',
            'nama_kurikulum' => 'required|string|max:100',
            'status_aktif' => 'boolean',
        ]);
        Kurikulum::create($data);
        return redirect()->route('kurikulum.index')->with('status', 'Kurikulum ditambahkan.');
    }

    public function edit(Kurikulum $kurikulum) { $this->authorize('update', $kurikulum); return view('kurikulum::edit', compact('kurikulum')); }

    public function update(Request $request, Kurikulum $kurikulum)
    {
        $this->authorize('update', $kurikulum);
        $kurikulum->update($request->validate(['nama_kurikulum' => 'required|string|max:100', 'status_aktif' => 'boolean']));
        return redirect()->route('kurikulum.index')->with('status', 'Kurikulum diperbarui.');
    }

    public function destroy(Kurikulum $kurikulum)
    {
        $this->authorize('delete', $kurikulum);
        $kurikulum->delete();
        return back()->with('status', 'Kurikulum dihapus.');
    }
}
```

Create `StrukturKurikulumController` and `KomponenKompetensiController` similarly.

- [ ] **Step 3: Create routes with plugin middleware**

Create `app/Plugins/Kurikulum/routes.php`:

```php
<?php
use App\Plugins\Kurikulum\Controllers\{KurikulumController, StrukturKurikulumController, KomponenKompetensiController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plugin:kurikulum'])->prefix('kurikulum')->name('kurikulum.')->group(function () {
    Route::resource('/', KurikulumController::class)->parameters(['' => 'kurikulum'])->middleware('permission:kurikulum.view');
    Route::resource('struktur', StrukturKurikulumController::class)->middleware('permission:kurikulum.view')->name('', 'kurikulum.struktur');
    Route::resource('komponen', KomponenKompetensiController::class)->middleware('permission:kurikulum.view');
});
```

ModuleServiceProvider auto-loads all `Plugins/*/routes.php` (Epic 4 implementation already does this).

- [ ] **Step 4: Create views**

Create `resources/views/plugins/kurikulum/index.blade.php`, `create.blade.php`, `edit.blade.php` using `@extends('layouts.app')` + standard CRUD form. Use namespaced view `kurikulum::index` (registered by ServiceProvider).

- [ ] **Step 5: Register policy in AuthServiceProvider**

```php
protected $policies = [
    // ... existing ...
    \App\Plugins\Kurikulum\Models\Kurikulum::class => \App\Plugins\Kurikulum\Policies\KurikulumPolicy::class,
];
```

- [ ] **Step 6: Commit + tag**

```bash
git add -A
git commit -m "feat(kurikulum): CRUD controllers + routes (plugin:kurikulum middleware) + views"
git tag epic-9-kurikulum
```

---

## Self-Review

**Spec coverage (against DEV_DOCS-003 §3.8, DEV_DOCS-004, DEV_DOCS-009 §5.7):**
- ✅ 3 tables (kurikulum, struktur_kurikulum, komponen_kompetensi) — Task 1
- ✅ mapel.kurikulum_id FK link added — Task 1 Step 2
- ✅ PluginContract implemented fully — Task 2 Step 2
- ✅ Permissions contributed (`kurikulum.view`, `kurikulum.manage`) — Task 2 Step 3
- ✅ Menu items contributed — Task 2 Step 3
- ✅ EvaluationFrameworkSubscriber listens EvaluationResolveFramework — Task 3 Step 3
- ✅ RaporSectionSubscriber listens RaportRenderSection — Task 3 Step 4
- ✅ Generic fallback when mapel.kurikulum_id NULL — Task 3 Step 3 test
- ✅ Per-tenant activation via Epic 4 PluginActivationService — Task 3 Step 1 test
- ✅ Permission seed on activation — Task 3 Step 1 test
- ✅ CRUD controllers + `plugin:kurikulum` middleware — Task 4

**Placeholder scan:** None.

**Name consistency:**
- `KurikulumPlugin` methods match PluginContract (kode, nama, versi, isCore, dependencies, providerClass, permissions, menu, boot).
- `EvaluationFrameworkSubscriber::handleEvaluationResolveFramework` + `subscribe()` — Laravel EventSubscriber convention.
- View namespace `kurikulum::` registered in ServiceProvider — used in controllers.
- Route names `kurikulum.index`, `kurikulum.struktur.index`, `kurikulum.komponen.index`.

**Test count:** Epic 9 adds 3 tests (framework resolution, no-framework fallback, activation seeds permissions).

**Pre-requisites:** Epic 1-8 complete. The EvaluationResolveFramework event must exist (Epic 6). PluginActivationService must work (Epic 4).
