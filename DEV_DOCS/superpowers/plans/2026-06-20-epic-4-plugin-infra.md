# Epic 4: Plugin System Infrastructure — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Build the plug-and-play plugin infrastructure: `PluginContract` interface, `PluginRegistry` auto-discovery, `EnsurePluginEnabled` middleware, per-tenant activation (`tenant_plugins`), event hooks system, and admin plugin activation UI. Plugin Kurikulum (full impl) is built in Epic 9; 8 scaffold plugins in Epic 10. This epic delivers the framework those depend on.

**Architecture:** `app/Support/PluginContract.php` interface, `app/Support/PluginRegistry.php` singleton scan-and-cache, `app/Support/PluginContext.php` DI for plugin boot. Plugin activation emits `Plugin.Activated`/`Plugin.Deactivated` events → permission seed + cache reset. Middleware `plugin:<kode>` blocks access if not active in tenant.

**Tech Stack:** Laravel 11 container, event system, Spatie permission.

**Spec reference:** design.md §6, ADR-009, DEV_DOCS-004.

---

## File Structure

- Create: `app/Support/PluginContract.php`
- Create: `app/Support/PluginRegistry.php`
- Create: `app/Support/PluginContext.php`
- Create: `app/Http/Middleware/EnsurePluginEnabled.php`
- Create: `app/Modules/Auth/Services/PluginActivationService.php`
- Create: `app/Modules/Auth/Controllers/PluginController.php`
- Create: `app/Modules/Auth/Policies/PluginPolicy.php`
- Create: `app/Plugins/Infrastructure/Models/{Plugin, TenantPlugin}.php`
- Create: `resources/views/plugins/index.blade.php`
- Modify: `app/Providers/{AppServiceProvider, PluginRegistryServiceProvider}.php`
- Modify: `app/Providers/ModuleServiceProvider.php` (call PluginRegistry boot)
- Modify: `bootstrap/app.php` (register `plugin:` middleware alias)
- Modify: `app/Modules/Auth/routes.php` (plugin routes)
- Create: `tests/Feature/Plugin/{PluginRegistryTest, PluginActivationTest}.php`

---

## Task 1: PluginContract + PluginContext + Models

**Files:**
- Create: `app/Support/PluginContract.php`
- Create: `app/Support/PluginContext.php`
- Create: `app/Plugins/Infrastructure/Models/{Plugin, TenantPlugin}.php`

- [ ] **Step 1: Define PluginContract**

Create `app/Support/PluginContract.php`:

```php
<?php

namespace App\Support;

interface PluginContract
{
    /** Unique plugin kode (e.g., 'kurikulum') */
    public function kode(): string;
    public function nama(): string;
    public function versi(): string;

    /** Core modules return true; plugins return false. */
    public function isCore(): bool;

    /** Array of plugin kode this plugin depends on (must be active first). */
    public function dependencies(): array;

    /** Full class name of the plugin's ServiceProvider. */
    public function providerClass(): string;

    /**
     * Permissions contributed by this plugin. Each item: ['name' => 'kode.view', 'display_name' => '...', 'module' => 'PluginName'].
     */
    public function permissions(): array;

    /**
     * Menu items contributed. Each: ['kode' => 'plugin.menu', 'label' => '...', 'route' => 'plugin.route', 'permission_required' => 'plugin.view', 'urutan' => 100].
     */
    public function menu(): array;

    /** Boot the plugin — register listeners, routes, etc. via PluginContext. */
    public function boot(PluginContext $ctx): void;
}
```

- [ ] **Step 2: Create PluginContext**

Create `app/Support/PluginContext.php`:

```php
<?php

namespace App\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;

class PluginContext
{
    public function __construct(
        public readonly ?int $tenantId,
        public readonly array $settings = [],
        protected Dispatcher $events,
    ) {}

    public function events(): Dispatcher { return $this->events; }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function routes(\Closure $callback, array $options = []): void
    {
        Route::group(array_merge([
            'middleware' => array_filter(['web', 'auth', 'plugin:' . ($options['plugin'] ?? '')]),
            'prefix' => $options['prefix'] ?? '',
        ], $options), $callback);
    }
}
```

- [ ] **Step 3: Create Plugin + TenantPlugin models**

Create `app/Plugins/Infrastructure/Models/Plugin.php`:

```php
<?php

namespace App\Plugins\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    protected $table = 'plugins';
    protected $fillable = ['kode', 'nama', 'deskripsi', 'versi', 'is_core', 'provider_class', 'aktif_global'];

    protected function casts(): array
    {
        return ['is_core' => 'boolean', 'aktif_global' => 'boolean'];
    }

    public function tenantPlugins(): HasMany { return $this->hasMany(TenantPlugin::class); }
}
```

Create `app/Plugins/Infrastructure/Models/TenantPlugin.php`:

```php
<?php

namespace App\Plugins\Infrastructure\Models;

use App\Modules\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class TenantPlugin extends Model
{
    protected $table = 'tenant_plugins';
    protected $fillable = ['tenant_id', 'plugin_id', 'aktif', 'pengaturan', 'diaktifkan_oleh', 'diaktifkan_pada'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'pengaturan' => 'array', 'diaktifkan_pada' => 'datetime'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function plugin(): BelongsTo { return $this->belongsTo(Plugin::class); }
    public function activator(): BelongsTo { return $this->belongsTo(User::class, 'diaktifkan_oleh'); }
}
```

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(plugin): PluginContract interface + PluginContext DI + Plugin/TenantPlugin models"
```

---

## Task 2: PluginRegistry — auto-discovery + sync to DB

**Files:**
- Create: `app/Support/PluginRegistry.php`
- Create: `app/Providers/PluginRegistryServiceProvider.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Write PluginRegistry test**

Create `tests/Feature/Plugin/PluginRegistryTest.php`:

```php
<?php

namespace Tests\Feature\Plugin;

use App\Support\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_returns_empty_when_no_plugins_on_disk(): void
    {
        $registry = app(PluginRegistry::class);
        $this->assertCount(0, $registry->all());
    }

    public function test_registry_discovers_plugin_manifest_files(): void
    {
        // Create a fake plugin manifest
        $this->createFakePlugin('TestPlugin');

        $registry = app(PluginRegistry::class);
        $registry->rescan();

        $this->assertCount(1, $registry->all());
        $this->assertSame('testplugin', $registry->get('testplugin')->kode());
    }

    public function test_registry_syncs_to_database(): void
    {
        $this->createFakePlugin('TestPlugin');
        $registry = app(PluginRegistry::class);
        $registry->rescan();
        $registry->syncToDatabase();

        $this->assertDatabaseHas('plugins', ['kode' => 'testplugin', 'nama' => 'Test Plugin']);
    }

    public function test_is_active_for_tenant_returns_false_when_not_activated(): void
    {
        $this->createFakePlugin('TestPlugin');
        $registry = app(PluginRegistry::class);
        $registry->rescan();

        $this->assertFalse($registry->isActiveForTenant('testplugin', 1));
    }

    private function createFakePlugin(string $name): void
    {
        $dir = app_path("Plugins/{$name}");
        @mkdir($dir, 0777, true);
        file_put_contents($dir . "/{$name}Plugin.php", <<<PHP
<?php
namespace App\\Plugins\\{$name};

use App\\Support\\{PluginContract, PluginContext};

class {$name}Plugin implements PluginContract {
    public function kode(): string { return strtolower('{$name}'); }
    public function nama(): string { return '{$name} Plugin'; }
    public function versi(): string { return '1.0.0'; }
    public function isCore(): bool { return false; }
    public function dependencies(): array { return []; }
    public function providerClass(): string { return ''; }
    public function permissions(): array { return []; }
    public function menu(): array { return []; }
    public function boot(PluginContext \$ctx): void {}
}
PHP
        );
    }

    protected function tearDown(): void
    {
        @array_map('unlink', glob(app_path('Plugins/TestPlugin/*')) ?: []);
        @rmdir(app_path('Plugins/TestPlugin'));
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Plugin/PluginRegistryTest.php`
Expected: FAIL — PluginRegistry class missing

- [ ] **Step 3: Implement PluginRegistry**

Create `app/Support/PluginRegistry.php`:

```php
<?php

namespace App\Support;

use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PluginRegistry
{
    /** @var array<string, PluginContract> keyed by kode */
    private array $plugins = [];
    private bool $scanned = false;

    public function all(): array
    {
        $this->ensureScanned();
        return $this->plugins;
    }

    public function get(string $kode): ?PluginContract
    {
        $this->ensureScanned();
        return $this->plugins[$kode] ?? null;
    }

    public function rescan(): void
    {
        $this->plugins = [];
        $this->scanned = true;
        $pluginsPath = app_path('Plugins');
        if (! File::isDirectory($pluginsPath)) return;

        foreach (File::directories($pluginsPath) as $pluginDir) {
            $pluginName = basename($pluginDir);
            // Skip Infrastructure meta-module
            if ($pluginName === 'Infrastructure') continue;
            $manifestFile = $pluginDir . DIRECTORY_SEPARATOR . "{$pluginName}Plugin.php";
            if (! File::exists($manifestFile)) continue;

            $class = "App\\Plugins\\{$pluginName}\\{$pluginName}Plugin";
            if (! class_exists($class)) require_once $manifestFile;

            try {
                $instance = app($class);
                if ($instance instanceof PluginContract) {
                    $this->plugins[$instance->kode()] = $instance;
                }
            } catch (\Throwable $e) {
                logger()->error("Plugin manifest load failed: {$class} — {$e->getMessage()}");
            }
        }
    }

    public function syncToDatabase(): void
    {
        $this->ensureScanned();
        foreach ($this->plugins as $kode => $plugin) {
            Plugin::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama'           => $plugin->nama(),
                    'versi'          => $plugin->versi(),
                    'is_core'        => $plugin->isCore(),
                    'provider_class' => $plugin->providerClass(),
                    'aktif_global'   => true,
                ],
            );
        }
    }

    public function isActiveForTenant(string $kode, ?int $tenantId): bool
    {
        if ($tenantId === null) return true; // SuperAdmin bypass

        return Cache::remember("plugin.active.{$tenantId}.{$kode}", 60, function () use ($kode, $tenantId) {
            $plugin = Plugin::where('kode', $kode)->first();
            if (! $plugin) return false;
            return TenantPlugin::where('tenant_id', $tenantId)
                ->where('plugin_id', $plugin->id)
                ->where('aktif', true)
                ->exists();
        });
    }

    public function clearTenantCache(int $tenantId, ?string $kode = null): void
    {
        if ($kode) {
            \Illuminate\Support\Facades\Cache::forget("plugin.active.{$tenantId}.{$kode}");
        } else {
            \Illuminate\Support\Facades\Cache::flush();
        }
    }

    private function ensureScanned(): void
    {
        if (! $this->scanned) $this->rescan();
    }
}

// Use Cache facade at top
```

Fix imports at top of file:

```php
<?php

namespace App\Support;

use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class PluginRegistry { /* ... as above ... */ }
```

- [ ] **Step 4: Create PluginRegistryServiceProvider**

Create `app/Providers/PluginRegistryServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Support\PluginRegistry;
use Illuminate\Support\ServiceProvider;

class PluginRegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginRegistry::class, function ($app) {
            $registry = new PluginRegistry();
            $registry->rescan();
            $registry->syncToDatabase();
            return $registry;
        });
    }

    public function boot(): void
    {
        // Boot each plugin's service provider (deferred to per-tenant request in middleware for tenant_plugins)
        // For now just call PluginContext for globally active plugins
    }
}
```

- [ ] **Step 5: Register provider**

Edit `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Providers\PluginRegistryServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    Lab404\Impersonate\ImpersonateServiceProvider::class,
];
```

- [ ] **Step 6: Run tests**

Run: `php artisan test tests/Feature/Plugin/PluginRegistryTest.php`
Expected: PASS (4 tests)

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(plugin): PluginRegistry auto-discovery + DB sync"
```

---

## Task 3: EnsurePluginEnabled middleware

**Files:**
- Create: `app/Http/Middleware/EnsurePluginEnabled.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Write middleware test**

Create `tests/Feature/Plugin/EnsurePluginEnabledTest.php`:

```php
<?php

namespace Tests\Feature\Plugin;

use App\Http\Middleware\EnsurePluginEnabled;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use App\Support\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsurePluginEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_bypasses_plugin_check(): void
    {
        $this->withMiddleware(EnsurePluginEnabled::class, 'kurikulum');
        $super = User::factory()->create(['tenant_id' => null, 'tipe' => 'super_admin']);

        $response = $this->actingAs($super)->get('/_test/plugin-route');
        // Even without plugin active, SuperAdmin should not get 403
        $this->assertNotEquals(403, $response->status());
    }

    public function test_tenant_user_blocked_when_plugin_inactive(): void
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsurePluginEnabled(app(PluginRegistry::class));
        $response = $middleware->handle($request, fn() => new Response('OK'), 'kurikulum');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_tenant_user_allowed_when_plugin_active(): void
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $plugin = Plugin::create(['kode' => 'kurikulum', 'nama' => 'Kurikulum']);
        TenantPlugin::create([
            'tenant_id' => $tenant->id, 'plugin_id' => $plugin->id, 'aktif' => true,
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsurePluginEnabled(app(PluginRegistry::class));
        $response = $middleware->handle($request, fn() => new Response('OK'), 'kurikulum');
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

- [ ] **Step 2: Implement EnsurePluginEnabled**

Create `app/Http/Middleware/EnsurePluginEnabled.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\PluginRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePluginEnabled
{
    public function __construct(private PluginRegistry $registry) {}

    public function handle(Request $request, Closure $next, string $pluginKode): Response
    {
        $user = $request->user();

        // SuperAdmin bypass
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantId = $user?->tenant_id;
        if (! $this->registry->isActiveForTenant($pluginKode, $tenantId)) {
            abort(403, "Plugin '{$pluginKode}' tidak aktif untuk tenant Anda.");
        }
        return $next($request);
    }
}
```

- [ ] **Step 3: Register alias**

Edit `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ResolveTenant::class,
        \App\Http\Middleware\ForcePasswordReset::class,
        \App\Http\Middleware\BlockWhileImpersonating::class,
    ]);
    $middleware->alias([
        'tenant'           => \App\Http\Middleware\ResolveTenant::class,
        'force.reset'      => \App\Http\Middleware\ForcePasswordReset::class,
        'impersonate.block'=> \App\Http\Middleware\BlockWhileImpersonating::class,
        'plugin'           => \App\Http\Middleware\EnsurePluginEnabled::class,
    ]);
})
```

- [ ] **Step 4: Run tests**

Run: `php artisan test tests/Feature/Plugin/EnsurePluginEnabledTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(plugin): EnsurePluginEnabled middleware with SuperAdmin bypass"
```

---

## Task 4: PluginActivationService + admin UI

**Files:**
- Create: `app/Modules/Auth/Services/PluginActivationService.php`
- Create: `app/Modules/Auth/Controllers/PluginController.php`
- Create: `app/Modules/Auth/Policies/PluginPolicy.php`
- Create: `resources/views/plugins/index.blade.php`
- Modify: `app/Modules/Auth/routes.php`

- [ ] **Step 1: Write activation test**

Create `tests/Feature/Plugin/PluginActivationTest.php`:

```php
<?php

namespace Tests\Feature\Plugin;

use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_activate_plugin_for_their_tenant(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::where('username', 'admin')->first();
        $plugin = Plugin::create(['kode' => 'testplugin', 'nama' => 'Test Plugin']);

        $response = $this->actingAs($admin)
            ->post("/admin/plugins/{$plugin->kode}/activate");

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $admin->tenant_id,
            'plugin_id' => $plugin->id,
            'aktif'     => true,
        ]);
    }

    public function test_admin_can_deactivate_plugin(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::where('username', 'admin')->first();
        $plugin = Plugin::create(['kode' => 'testplugin', 'nama' => 'Test Plugin']);
        TenantPlugin::create(['tenant_id' => $admin->tenant_id, 'plugin_id' => $plugin->id, 'aktif' => true]);

        $this->actingAs($admin)
            ->post("/admin/plugins/{$plugin->kode}/deactivate");

        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $admin->tenant_id, 'plugin_id' => $plugin->id, 'aktif' => false,
        ]);
    }

    public function test_activation_blocked_while_impersonating(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);
        $super = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($super)->post("/impersonate/{$admin->id}/start");

        $plugin = Plugin::create(['kode' => 'testplugin', 'nama' => 'Test']);
        $response = $this->post("/admin/plugins/{$plugin->kode}/activate");
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_activate(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('guru');
        $plugin = Plugin::create(['kode' => 'testplugin', 'nama' => 'Test']);

        $this->actingAs($user)->post("/admin/plugins/{$plugin->kode}/activate")->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Plugin/PluginActivationTest.php`
Expected: FAIL

- [ ] **Step 3: Implement PluginActivationService**

Create `app/Modules/Auth/Services/PluginActivationService.php`:

```php
<?php

namespace App\Modules\Auth\Services;

use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use App\Support\{PluginRegistry, MenuRenderer, FieldAcl};
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PluginActivationService
{
    public function __construct(
        private PluginRegistry $registry,
        private AuditLogger $audit,
        private Dispatcher $events,
    ) {}

    public function activate(int $tenantId, string $kode, int $activatedBy): TenantPlugin
    {
        $this->blockIfImpersonating();

        return DB::transaction(function () use ($tenantId, $kode, $activatedBy) {
            $plugin = Plugin::where('kode', $kode)->firstOrFail();
            $manifest = $this->registry->get($kode);

            $tp = TenantPlugin::updateOrCreate(
                ['tenant_id' => $tenantId, 'plugin_id' => $plugin->id],
                [
                    'aktif' => true,
                    'diaktifkan_oleh' => $activatedBy,
                    'diaktifkan_pada' => now(),
                ],
            );

            // Seed plugin permissions
            if ($manifest) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
                foreach ($manifest->permissions() as $perm) {
                    Permission::firstOrCreate(['name' => $perm['name'], 'module' => $perm['module'] ?? $manifest->nama()]);
                }
                app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            }

            $this->registry->clearTenantCache($tenantId, $kode);
            MenuRenderer::clearCache();
            FieldAcl::clearCache();

            $this->audit->log('plugin.activated', auth()->user(), [
                'plugin_kode' => $kode, 'tenant_id' => $tenantId,
            ], request());

            $this->events->dispatch('Plugin.Activated', [$kode, $tenantId]);

            return $tp;
        });
    }

    public function deactivate(int $tenantId, string $kode): void
    {
        $this->blockIfImpersonating();

        DB::transaction(function () use ($tenantId, $kode) {
            $plugin = Plugin::where('kode', $kode)->firstOrFail();
            TenantPlugin::where('tenant_id', $tenantId)->where('plugin_id', $plugin->id)
                ->update(['aktif' => false]);

            $this->registry->clearTenantCache($tenantId, $kode);
            MenuRenderer::clearCache();

            $this->audit->log('plugin.deactivated', auth()->user(), [
                'plugin_kode' => $kode, 'tenant_id' => $tenantId,
            ], request());

            $this->events->dispatch('Plugin.Deactivated', [$kode, $tenantId]);
        });
    }

    private function blockIfImpersonating(): void
    {
        if (session()->has('impersonated_by')) {
            abort(403, 'Aktivasi/nonaktifkan plugin diblokir selama impersonation.');
        }
    }
}
```

- [ ] **Step 4: Implement PluginController**

Create `app/Modules/Auth/Controllers/PluginController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PluginActivationService;
use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use App\Support\PluginRegistry;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function __construct(
        private PluginActivationService $activation,
        private PluginRegistry $registry,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('plugin.activate');
        $tenantId = $request->user()->tenant_id;
        $plugins = Plugin::orderBy('nama')->get();
        $activeMap = [];
        if ($tenantId) {
            $activeMap = TenantPlugin::where('tenant_id', $tenantId)
                ->where('aktif', true)->pluck('plugin_id', 'plugin_id')->all();
        }
        return view('plugins.index', compact('plugins', 'activeMap'));
    }

    public function activate(Request $request, string $kode)
    {
        $this->authorize('plugin.activate');
        $user = $request->user();
        if (! $user->tenant_id) abort(403, 'SuperAdmin tidak mengaktifkan plugin per-tenant.');
        $this->activation->activate($user->tenant_id, $kode, $user->id);
        return back()->with('status', "Plugin '{$kode}' diaktifkan.");
    }

    public function deactivate(Request $request, string $kode)
    {
        $this->authorize('plugin.activate');
        $user = $request->user();
        $this->activation->deactivate($user->tenant_id, $kode);
        return back()->with('status', "Plugin '{$kode}' dinonaktifkan. Data tetap aman.");
    }
}
```

Create `app/Modules/Auth/Policies/PluginPolicy.php`:

```php
<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;

class PluginPolicy
{
    public function activate(User $user): bool
    {
        return $user->can('plugin.activate');
    }
}
```

- [ ] **Step 5: Add routes**

Edit `app/Modules/Auth/routes.php`, add:

```php
use App\Modules\Auth\Controllers\PluginController;

Route::middleware(['auth', 'permission:plugin.activate'])->prefix('admin/plugins')->group(function () {
    Route::get('/', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('/{kode}/activate', [PluginController::class, 'activate'])->name('plugins.activate');
    Route::post('/{kode}/deactivate', [PluginController::class, 'deactivate'])->name('plugins.deactivate');
});
```

- [ ] **Step 6: Create plugin index view**

Create `resources/views/plugins/index.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Plugin')
@section('content')
<h1>Plugin</h1>
<p>Kelola plugin aktif untuk tenant Anda. Nonaktifkan tidak menghapus data — bisa diaktifkan kembali kapan saja.</p>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table">
    <thead><tr><th>Plugin</th><th>Versi</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach($plugins as $p)
        <tr>
            <td><strong>{{ $p->nama }}</strong><br><small class="text-muted">{{ $p->deskripsi }}</small></td>
            <td>{{ $p->versi }}</td>
            <td>@if(isset($activeMap[$p->id])) <span class="badge bg-success">Aktif</span> @else <span class="badge bg-secondary">Nonaktif</span> @endif</td>
            <td>
                @if(isset($activeMap[$p->id]))
                    <form method="POST" action="{{ route('plugins.deactivate', $p->kode) }}" class="d-inline">@csrf <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan?')">Nonaktifkan</button></form>
                @else
                    <form method="POST" action="{{ route('plugins.activate', $p->kode) }}" class="d-inline">@csrf <button class="btn btn-sm btn-primary">Aktifkan</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
```

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Feature/Plugin/PluginActivationTest.php`
Expected: PASS (4 tests)

- [ ] **Step 8: Commit + tag**

```bash
git add -A
git commit -m "feat(plugin): PluginActivationService + admin UI + events + cache reset"
git tag epic-4-plugin-infra
```

---

## Self-Review

**Spec coverage (against ADR-009):**
- ✅ PluginContract interface (9 methods per ADR-009) — Task 1
- ✅ PluginContext DI (tenantId, settings, events) — Task 1
- ✅ PluginRegistry auto-discovery + sync to `plugins` table — Task 2
- ✅ EnsurePluginEnabled middleware + SuperAdmin bypass — Task 3
- ✅ Per-tenant activation via `tenant_plugins` — Task 4
- ✅ Event emission `Plugin.Activated`/`Plugin.Deactivated` — Task 4
- ✅ Permission seed on activation — Task 4
- ✅ Cache reset on activation/deactivation — Task 4
- ✅ Block during impersonation — Task 4
- ✅ Admin UI for activate/deactivate — Task 4

**Placeholder scan:** None.

**Name consistency:**
- `PluginContract` methods match between interface + fake plugin in test.
- `PluginRegistry::all/get/rescan/syncToDatabase/isActiveForTenant/clearTenantCache` — used consistently.
- `PluginActivationService::activate(tenantId, kode, activatedBy)` + `deactivate(tenantId, kode)` — used consistently.
- Route names: `plugins.index`, `plugins.activate`, `plugins.deactivate`.

**Pre-requisites:** Epic 1-3 complete (auth, RBAC, audit, impersonation middleware).

**Test count:** Epic 4 adds ~11 tests (4 registry + 3 middleware + 4 activation).
