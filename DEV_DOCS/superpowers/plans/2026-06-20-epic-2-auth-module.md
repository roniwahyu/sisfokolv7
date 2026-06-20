# Epic 2: Auth Module Full — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Build full authentication: login/logout flow with throttling + bcrypt + session regenerate, ForcePasswordReset middleware (post-ETL users), Impersonation start/stop with banner + audit + sensitive-action blocking, dashboard role-aware, and AuditLog viewer. Depends on Epic 1 (foundation) — SuperAdmin + roles + permissions already seeded.

**Architecture:** Auth module at `app/Modules/Auth/`. Controllers delegate to `ImpersonationService` + `RbacBuilderService` (Epic 3). Observers auto-log auth events. Middleware chain: `auth → ResolveTenant → ForcePasswordReset → BlockWhileImpersonating → throttle`.

**Tech Stack:** Laravel 11 auth, lab404/laravel-impersonate (already installed in Epic 1), Spatie permission teams, Blade + Alpine.js.

**Spec reference:** design.md §5, ADR-005, ADR-006, DEV_DOCS-002.

---

## File Structure

- Create: `app/Modules/Auth/Controllers/{AuthController, DashboardController, ImpersonationController, AuditLogController, PasswordResetController}.php`
- Create: `app/Modules/Auth/Requests/{LoginRequest, ChangePasswordRequest, StartImpersonationRequest}.php`
- Create: `app/Modules/Auth/Services/{ImpersonationService, AuditLogger}.php`
- Create: `app/Modules/Auth/Observers/UserObserver.php`
- Create: `app/Modules/Http/Middleware/{ForcePasswordReset, BlockWhileImpersonating}.php`
- Create: `app/Modules/Auth/routes.php`
- Create: `resources/views/{auth/login, auth/change-password, dashboard/index, errors/impersonation-blocked}.blade.php`
- Create: `resources/views/partials/impersonation_banner.blade.php`
- Modify: `bootstrap/app.php` (register middleware aliases)
- Create: `tests/Feature/Auth/{LoginTest, LogoutTest, ForcePasswordResetTest, ImpersonationTest, AuditLogTest}.php`

---

## Task 1: LoginRequest + AuthController — login flow

**Files:**
- Create: `app/Modules/Auth/Requests/LoginRequest.php`
- Create: `app/Modules/Auth/Controllers/AuthController.php`
- Create: `app/Modules/Auth/routes.php`
- Create: `resources/views/auth/login.blade.php`

- [ ] **Step 1: Write the login test**

Create `tests/Feature/Auth/LoginTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
    }

    public function test_login_page_is_accessible_to_guest(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_login_redirects_authenticated_user(): void
    {
        $user = User::where('username', 'superadmin')->first();
        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
    }

    public function test_valid_credentials_log_in_superadmin(): void
    {
        $response = $this->post('/login', [
            'username' => 'superadmin',
            'password' => 'SuperAdmin#2026',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('username', 'superadmin')->first());
    }

    public function test_invalid_password_rejected(): void
    {
        $this->post('/login', ['username' => 'superadmin', 'password' => 'wrong']);
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['aktif' => false]);
        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);
        $this->assertGuest();
    }

    public function test_login_updates_last_login_at(): void
    {
        $this->post('/login', [
            'username' => 'superadmin',
            'password' => 'SuperAdmin#2026',
        ]);
        $user = User::where('username', 'superadmin')->first();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_throttle_blocks_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'superadmin', 'password' => 'wrong']);
        }
        $response = $this->post('/login', ['username' => 'superadmin', 'password' => 'wrong']);
        $response->assertStatus(429); // Too Many Requests
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Auth/LoginTest.php`
Expected: FAIL — routes don't exist

- [ ] **Step 3: Create LoginRequest**

Create `app/Modules/Auth/Requests/LoginRequest.php`:

```php
<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
```

- [ ] **Step 4: Create AuthController**

Create `app/Modules/Auth/Controllers/AuthController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('username', 'password');

        $user = \App\Models\User::where('username', $credentials['username'])->first();

        if (! $user || ! $user->aktif) {
            return back()->withErrors(['username' => 'Akun tidak ditemukan atau tidak aktif.'])->onlyInput('username');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->audit->log('login.failed', null, ['username' => $credentials['username']], $request);
            return back()->withErrors(['password' => 'Kredensial salah.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);
        $this->audit->log('login.success', $user, [], $request);

        return $this->redirectAfterLogin($user);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($user) {
            $this->audit->log('logout', $user, [], $request);
        }
        return redirect()->route('login');
    }

    private function redirectAfterLogin($user): \Illuminate\Http\RedirectResponse
    {
        if ($user->must_reset_password) {
            return redirect()->route('password.change');
        }
        return redirect()->intended(route('dashboard'));
    }
}
```

- [ ] **Step 5: Create routes**

Create `app/Modules/Auth/routes.php`:

```php
<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
```

- [ ] **Step 6: Create login Blade**

Create `resources/views/auth/login.blade.php` (minimal, styled in Epic 12):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — SISFOKOL Laravel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">SISFOKOL Laravel</h4>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        @error('username') <div class="alert alert-danger py-1">{{ $message }}</div> @enderror
                        @error('password') <div class="alert alert-danger py-1">{{ $message }}</div> @enderror
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
```

- [ ] **Step 7: Configure throttle for `/login`**

Edit `app/Modules/Auth/routes.php`, replace the `Route::post('/login', ...)` line with throttled version:

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
```

- [ ] **Step 8: Run tests**

Run: `php artisan test tests/Feature/Auth/LoginTest.php`
Expected: PASS (7 tests)

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(auth): login flow with throttle + audit + last_login_at"
```

---

## Task 2: AuditLogger Service + UserObserver

**Files:**
- Create: `app/Modules/Auth/Services/AuditLogger.php`
- Create: `app/Modules/Auth/Observers/UserObserver.php`
- Modify: `app/Providers/EventServiceProvider.php`

- [ ] **Step 1: Write AuditLogger test**

Create `tests/Unit/Auth/AuditLoggerTest.php`:

```php
<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_creates_audit_log_entry(): void
    {
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        app(AuditLogger::class)->log('test.event', $user, ['foo' => 'bar'], $request);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id'   => $user->id,
            'event'     => 'test.event',
        ]);
    }

    public function test_log_stores_old_and_new_values_as_json(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);
        $request = Request::create('/t', 'POST');

        app(AuditLogger::class)->log('e', $user, ['new' => 1], $request, ['old' => 2]);

        $log = \App\Modules\Auth\Models\AuditLog::first();
        $this->assertEquals(['new' => 1], $log->new_values);
        $this->assertEquals(['old' => 2], $log->old_values);
    }
}
```

- [ ] **Step 2: Create AuditLog model**

Create `app/Modules/Auth/Models/AuditLog.php`:

```php
<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\MassPrunable;

class AuditLog extends Model
{
    use MassPrunable;

    protected $table = 'audit_logs';
    public $timestamps = true;
    public const UPDATED_AT = null; // immutable

    protected $fillable = [
        'tenant_id', 'user_id', 'event', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class); }

    public function prunable()
    {
        // Auto-prune logs older than 2 years
        return static::where('created_at', '<', now()->subYears(2));
    }
}
```

- [ ] **Step 3: Implement AuditLogger**

Create `app/Modules/Auth/Services/AuditLogger.php`:

```php
<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\AuditLog;
use App\Modules\Tenancy\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private TenantContext $tenant) {}

    public function log(
        string $event,
        ?\App\Models\User $user,
        array $newValues = [],
        ?Request $request = null,
        array $oldValues = [],
        ?string $modelType = null,
        ?int $modelId = null,
    ): void {
        $tenantId = $user?->tenant_id ?? ($this->tenant->isInitialized() ? $this->tenant->id : null);

        AuditLog::create([
            'tenant_id'    => $tenantId,
            'user_id'      => $user?->id,
            'event'        => $event,
            'model_type'   => $modelType ?? (isset($newValues['model_type']) ? $newValues['model_type'] : null),
            'model_id'     => $modelId ?? ($newValues['model_id'] ?? null),
            'old_values'   => empty($oldValues) ? null : $oldValues,
            'new_values'   => empty($newValues) ? null : $newValues,
            'ip_address'   => $request?->ip(),
            'user_agent'   => $request?->userAgent(),
        ]);
    }
}
```

- [ ] **Step 4: Bind AuditLogger singleton**

Edit `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    $this->app->singleton(\App\Support\TenantContext::class);
    $this->app->singleton(\App\Modules\Auth\Services\AuditLogger::class);
}
```

- [ ] **Step 5: Create UserObserver**

Create `app/Modules/Auth/Observers/UserObserver.php`:

```php
<?php

namespace App\Modules\Auth\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        app(\App\Modules\Auth\Services\AuditLogger::class)->log(
            'user.created',
            $user,
            ['username' => $user->username, 'nama' => $user->nama, 'tipe' => $user->tipe],
            request(),
            modelType: User::class,
            modelId: $user->id,
        );
    }

    public function updated(User $user): void
    {
        app(\App\Modules\Auth\Services\AuditLogger::class)->log(
            'user.updated',
            $user,
            $user->getChanges(),
            request(),
            $user->getOriginal(),
            modelType: User::class,
            modelId: $user->id,
        );
    }
}
```

- [ ] **Step 6: Register observer**

Edit `app/Providers/EventServiceProvider.php` (or create `App\Providers\EventServiceProvider` and add to `bootstrap/providers.php`):

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \App\Models\User::observe(\App\Modules\Auth\Observers\UserObserver::class);
    }
}
```

Add `App\Providers\EventServiceProvider::class` to `bootstrap/providers.php` if not already there.

- [ ] **Step 7: Run tests**

Run: `php artisan test tests/Unit/Auth/AuditLoggerTest.php`
Expected: PASS (2 tests)

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(auth): AuditLogger service + AuditLog model + UserObserver"
```

---

## Task 3: ForcePasswordReset middleware + change-password page

**Files:**
- Create: `app/Http/Middleware/ForcePasswordReset.php`
- Create: `app/Modules/Auth/Controllers/PasswordResetController.php`
- Create: `app/Modules/Auth/Requests/ChangePasswordRequest.php`
- Create: `resources/views/auth/change-password.blade.php`
- Modify: `bootstrap/app.php` (register middleware alias)
- Modify: `app/Modules/Auth/routes.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Auth/ForcePasswordResetTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_must_reset_is_redirected_to_change_password(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $user = User::factory()->create([
            'tenant_id' => null,
            'tipe' => 'admin_sekolah',
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect('/password/change');
    }

    public function test_change_password_clears_flag(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $user = User::factory()->create([
            'tenant_id' => null,
            'must_reset_password' => true,
        ]);

        $this->actingAs($user)
            ->post('/password/change', [
                'current_password' => 'password',
                'password' => 'NewSecure#2026',
                'password_confirmation' => 'NewSecure#2026',
            ])
            ->assertRedirect('/dashboard');

        $this->assertFalse($user->fresh()->must_reset_password);
    }

    public function test_change_password_route_not_blocked_by_middleware(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $user = User::factory()->create(['must_reset_password' => true]);
        $this->actingAs($user)->get('/password/change')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Auth/ForcePasswordResetTest.php`
Expected: FAIL

- [ ] **Step 3: Implement ForcePasswordReset middleware**

Create `app/Http/Middleware/ForcePasswordReset.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordReset
{
    private array $exemptRoutes = ['password.change', 'password.change.store', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->must_reset_password && ! $request->routeIs($this->exemptRoutes)) {
            return redirect()->route('password.change');
        }
        return $next($request);
    }
}
```

- [ ] **Step 4: Register middleware**

Edit `bootstrap/app.php`, add to the `withMiddleware` alias array:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ResolveTenant::class,
        \App\Http\Middleware\ForcePasswordReset::class,
    ]);
    $middleware->alias([
        'tenant'           => \App\Http\Middleware\ResolveTenant::class,
        'force.reset'      => \App\Http\Middleware\ForcePasswordReset::class,
        // BlockWhileImpersonating added in Task 4
    ]);
})
```

- [ ] **Step 5: Create ChangePasswordRequest**

Create `app/Modules/Auth/Requests/ChangePasswordRequest.php`:

```php
<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function ($attr, $value, $fail) {
                if (! \Hash::check($value, $this->user()->password)) {
                    $fail('Password saat ini tidak cocok.');
                }
            }],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
```

- [ ] **Step 6: Create PasswordResetController**

Create `app/Modules/Auth/Controllers/PasswordResetController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Services\AuditLogger;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function show()
    {
        return view('auth.change-password');
    }

    public function store(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $oldHash = $user->password;
        $user->update([
            'password' => $request->password,
            'must_reset_password' => false,
        ]);
        $this->audit->log('password.changed', $user, [], $request, ['old_password_hash' => $oldHash]);
        return redirect()->route('dashboard')->with('status', 'Password berhasil diubah.');
    }
}
```

- [ ] **Step 7: Add routes**

Edit `app/Modules/Auth/routes.php`, add inside `auth` group:

```php
Route::get('/password/change', [PasswordResetController::class, 'show'])->name('password.change');
Route::post('/password/change', [PasswordResetController::class, 'store'])->name('password.change.store');
```

Add import at top: `use App\Modules\Auth\Controllers\PasswordResetController;`

- [ ] **Step 8: Create Blade**

Create `resources/views/auth/change-password.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ganti Password — SISFOKOL Laravel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="alert alert-warning">Anda wajib mengganti password sebelum melanjutkan.</div>
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3">Ganti Password</h4>
                    <form method="POST" action="{{ route('password.change.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru (min. 8, huruf+angka)</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ulangi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
```

- [ ] **Step 9: Run tests**

Run: `php artisan test tests/Feature/Auth/ForcePasswordResetTest.php`
Expected: PASS (3 tests)

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(auth): ForcePasswordReset middleware + change-password page"
```

---

## Task 4: Impersonation start/stop + BlockWhileImpersonating + banner

**Files:**
- Create: `app/Http/Middleware/BlockWhileImpersonating.php`
- Create: `app/Modules/Auth/Controllers/ImpersonationController.php`
- Create: `app/Modules/Auth/Services/ImpersonationService.php`
- Create: `app/Modules/Auth/Requests/StartImpersonationRequest.php`
- Create: `resources/views/partials/impersonation_banner.blade.php`
- Create: `resources/views/errors/impersonation-blocked.blade.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Modules/Auth/routes.php`

- [ ] **Step 1: Write the test**

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);
    }

    public function test_impersonation_disabled_returns_404(): void
    {
        config(['impersonate.enabled' => false]);
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create();

        $response = $this->actingAs($superadmin)
            ->post("/impersonate/{$target->id}/start");
        $response->assertStatus(404);
    }

    public function test_superadmin_can_impersonate_any_user(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create(['nama' => 'Target User']);

        $response = $this->actingAs($superadmin)
            ->post("/impersonate/{$target->id}/start");

        $response->assertRedirect('/dashboard');
        $this->assertEquals($target->id, session('impersonated_by_origin_id'));
        $this->assertEquals($target->id, auth()->id());
    }

    public function test_stop_returns_to_original(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create();

        $this->actingAs($superadmin)
            ->post("/impersonate/{$target->id}/start");
        $this->actingAs($target) // simulate impersonated session
            ->withSession(['impersonated_by' => $superadmin->id])
            ->post('/impersonate/stop');

        // After stop, original user should be restored
        $this->assertEquals($superadmin->id, auth()->id());
    }

    public function test_admin_sekolah_can_impersonate_tenant_user_only(): void
    {
        $t1 = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $t2 = Tenant::create(['nama' => 'T2', 'npsn' => '22222222']);

        $adminT1 = User::factory()->create(['tenant_id' => $t1->id, 'tipe' => 'admin_sekolah']);
        $adminT1->assignRole('admin_sekolah');

        $targetInT1 = User::factory()->create(['tenant_id' => $t1->id, 'tipe' => 'guru']);
        $targetInT2 = User::factory()->create(['tenant_id' => $t2->id, 'tipe' => 'guru']);

        // OK in same tenant
        $this->actingAs($adminT1)
            ->post("/impersonate/{$targetInT1->id}/start")
            ->assertRedirect('/dashboard');

        // Forbidden cross-tenant
        $this->actingAs($adminT1)->post('/impersonate/stop');
        $response = $this->actingAs($adminT1)
            ->post("/impersonate/{$targetInT2->id}/start");
        $response->assertStatus(403);
    }

    public function test_blocked_action_while_impersonating_returns_403(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create();

        $this->actingAs($superadmin)->post("/impersonate/{$target->id}/start");
        $this->withSession(['impersonated_by' => $superadmin->id]);

        // POST to /users (sensitive) should be blocked
        $response = $this->post('/users', ['username' => 'test']);
        $response->assertStatus(403);
    }

    public function test_impersonation_creates_audit_logs(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create();

        $this->actingAs($superadmin)->post("/impersonate/{$target->id}/start");
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superadmin->id,
            'event' => 'impersonate.start',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Auth/ImpersonationTest.php`
Expected: FAIL

- [ ] **Step 3: Implement ImpersonationService**

Create `app/Modules/Auth/Services/ImpersonationService.php`:

```php
<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public function __construct(private AuditLogger $audit) {}

    public function canStart(User $impersonator, User $target): bool
    {
        if (! config('impersonate.enabled', false)) return false;
        if (! $impersonator->canImpersonate()) return false;
        if (! $impersonator->canBeImpersonated($target)) return false;
        return true;
    }

    public function start(User $impersonator, User $target, Request $request): void
    {
        session()->put('impersonated_by', $impersonator->id);
        Auth::login($target);
        $this->audit->log(
            'impersonate.start', $impersonator,
            ['target_user_id' => $target->id, 'target_username' => $target->username],
            $request,
        );
    }

    public function stop(Request $request): ?User
    {
        $impersonatorId = session()->pull('impersonated_by');
        if (! $impersonatorId) return null;

        $impersonator = User::find($impersonatorId);
        if ($impersonator) {
            Auth::login($impersonator);
            $this->audit->log(
                'impersonate.stop', $impersonator,
                ['was_impersonating_user_id' => $request->user()?->id],
                $request,
            );
        }
        return $impersonator;
    }

    public function isImpersonating(): bool
    {
        return session()->has('impersonated_by');
    }
}
```

- [ ] **Step 4: Implement ImpersonationController**

Create `app/Modules/Auth/Controllers/ImpersonationController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(private ImpersonationService $impersonation) {}

    public function start(User $target, Request $request)
    {
        // 404 if feature disabled entirely (defense in depth)
        if (! config('impersonate.enabled', false)) abort(404);

        $impersonator = $request->user();
        if (! $this->impersonation->canStart($impersonator, $target)) {
            abort(403, 'Anda tidak dapat melakukan impersonation ke user ini.');
        }

        $this->impersonation->start($impersonator, $target, $request);
        return redirect()->route('dashboard')->with('status', "Login sebagai {$target->nama}");
    }

    public function stop(Request $request)
    {
        $this->impersonation->stop($request);
        return redirect()->route('dashboard')->with('status', 'Kembali ke akun Anda.');
    }
}
```

- [ ] **Step 5: Implement BlockWhileImpersonating**

Create `app/Http/Middleware/BlockWhileImpersonating.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockWhileImpersonating
{
    /** Routes blocked while impersonating (sensitive actions per ADR-005) */
    private array $blockedPatterns = [
        'users.store', 'users.update', 'users.destroy',
        'rbac.*', 'plugins.activate', 'plugins.deactivate',
        'password.change.store',
    ];

    public function __construct(private ImpersonationService $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->isImpersonating() && $request->isMethod('POST|PUT|PATCH|DELETE')) {
            foreach ($this->blockedPatterns as $pattern) {
                if ($request->routeIs($pattern)) {
                    abort(403, 'Aksi ini diblokir selama impersonation.');
                }
            }
        }
        return $next($request);
    }
}
```

- [ ] **Step 6: Register middleware + routes**

Edit `bootstrap/app.php`, add to aliases:

```php
'impersonate.block' => \App\Http\Middleware\BlockWhileImpersonating::class,
```

Add to `web(append:)`:

```php
\App\Http\Middleware\BlockWhileImpersonating::class,
```

Edit `app/Modules/Auth/routes.php`, add at end:

```php
use App\Modules\Auth\Controllers\ImpersonationController;

Route::middleware(['auth'])->group(function () {
    Route::post('/impersonate/{user}/start', [ImpersonationController::class, 'start'])
         ->name('impersonate.start');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
         ->name('impersonate.stop');
});
```

- [ ] **Step 7: Create banner partial**

Create `resources/views/partials/impersonation_banner.blade.php`:

```blade
@php
    $imp = app(\App\Modules\Auth\Services\ImpersonationService::class);
@endphp
@if($imp->isImpersonating())
    @php
        $original = \App\Models\User::find(session('impersonated_by'));
    @endphp
    <div class="alert alert-danger d-flex justify-content-between align-items-center mb-0 rounded-0">
        <span>
            ⚠️ Anda sedang login sebagai <strong>{{ auth()->user()->nama }}</strong>
            (impersonated by {{ $original?->nama }}).
            <strong>Aksi sensitif diblokir.</strong>
        </span>
        <form method="POST" action="{{ route('impersonate.stop') }}">
            @csrf
            <button class="btn btn-sm btn-light" type="submit">Kembali ke akun saya</button>
        </form>
    </div>
@endif
```

- [ ] **Step 8: Create blocked error page**

Create `resources/views/errors/impersonation-blocked.blade.php`:

```blade
@extends('layouts.app')
@section('content')
    <div class="alert alert-danger">
        <h4>Aksi Diblokir</h4>
        <p>Anda sedang dalam mode impersonation. Aksi ini (mengubah kredensial/role/plugin) tidak diizinkan untuk alasan keamanan.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection
```

- [ ] **Step 9: Register `impersonate.enabled` from .env**

Edit `config/impersonate.php`, ensure at top:

```php
'enabled' => env('IMPERSONATION_ENABLED', false),
```

- [ ] **Step 10: Run tests**

Run: `php artisan test tests/Feature/Auth/ImpersonationTest.php`
Expected: PASS (6 tests)

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "feat(auth): impersonation start/stop + block sensitive + banner + audit"
```

---

## Task 5: Dashboard + AuditLog Viewer

**Files:**
- Create: `app/Modules/Auth/Controllers/{DashboardController, AuditLogController}.php`
- Create: `resources/views/dashboard/index.blade.php`
- Create: `resources/views/audit/index.blade.php`
- Modify: `app/Modules/Auth/routes.php`
- Create: `app/Modules/Auth/Policies/AuditLogPolicy.php`

- [ ] **Step 1: Write Dashboard test**

Create `tests/Feature/Auth/DashboardTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\{RolePermissionSeeder, SuperAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee($user->nama);
    }

    public function test_dashboard_shows_impersonation_banner_when_active(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        config(['impersonate.enabled' => true]);
        $superadmin = User::where('username', 'superadmin')->first();
        $target = User::factory()->create();

        $this->actingAs($superadmin)->post("/impersonate/{$target->id}/start");
        $response = $this->get('/dashboard');
        $response->assertSee('Kembali ke akun saya');
    }
}
```

- [ ] **Step 2: Create DashboardController**

Create `app/Modules/Auth/Controllers/DashboardController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('dashboard.index', [
            'user' => $user,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }
}
```

- [ ] **Step 3: Create layout + dashboard views**

Create `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SISFOKOL Laravel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@include('partials.impersonation_banner')
<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">
        <span class="navbar-brand">SISFOKOL Laravel</span>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">{{ auth()->user()?->nama }}</span>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-light" type="submit">Logout</button>
            </form>
        </div>
    </div>
</nav>
<main class="container mt-4">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
```

Create `resources/views/dashboard/index.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1>Dashboard</h1>
    <p class="lead">Selamat datang, <strong>{{ $user->nama }}</strong>.</p>
    @if($isSuperAdmin)
        <div class="alert alert-info">Anda login sebagai SuperAdmin platform.</div>
    @endif
    <p>Modul dashboard lanjutan (widget per role) akan dibangun di epic berikutnya.</p>
@endsection
```

- [ ] **Step 4: Create AuditLogController + Policy**

Create `app/Modules/Auth/Policies/AuditLogPolicy.php`:

```php
<?php

namespace App\Modules\Auth\Policies;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->can('audit.view') && $user->tenant_id === $log->tenant_id;
    }
}
```

Create `app/Modules/Auth/Controllers/AuditLogController.php`:

```php
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with('user')->latest();
        if ($request->filled('event')) $query->where('event', 'like', "%{$request->event}%");
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if (! $request->user()->isSuperAdmin()) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        $logs = $query->paginate(50);
        return view('audit.index', compact('logs'));
    }
}
```

Create `resources/views/audit/index.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
    <h1>Audit Log</h1>
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4"><input name="event" class="form-control" placeholder="Filter event..." value="{{ request('event') }}"></div>
        <div class="col-md-3"><input name="user_id" class="form-control" placeholder="User ID..." value="{{ request('user_id') }}"></div>
        <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
    </form>
    <table class="table table-sm table-bordered">
        <thead><tr><th>Waktu</th><th>User</th><th>Event</th><th>IP</th><th>Detail</th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $log->user?->nama ?? '—' }}</td>
                <td><code>{{ $log->event }}</code></td>
                <td>{{ $log->ip_address }}</td>
                <td><small>{{ json_encode($log->new_values) }}</small></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
@endsection
```

- [ ] **Step 5: Register routes + Policy**

Edit `app/Modules/Auth/routes.php`, add inside auth group:

```php
use App\Modules\Auth\Controllers\{DashboardController, AuditLogController};

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index')
     ->middleware('permission:audit.view');
```

Edit `app/Providers/AuthServiceProvider.php` (create if needed):

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Modules\Auth\Models\AuditLog::class => \App\Modules\Auth\Policies\AuditLogPolicy::class,
    ];
}
```

Add `App\Providers\AuthServiceProvider::class` to `bootstrap/providers.php`.

- [ ] **Step 6: Run tests**

Run: `php artisan test tests/Feature/Auth/DashboardTest.php`
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(auth): dashboard + audit log viewer + policies"
git tag epic-2-auth
```

---

## Self-Review

**Spec coverage (against design.md §5):**
- ✅ Login flow (throttle, bcrypt, session regenerate, last_login_at, audit) — Task 1
- ✅ AuditLogger + UserObserver — Task 2
- ✅ ForcePasswordReset middleware + change-password — Task 3
- ✅ Impersonation start/stop + BlockWhileImpersonating + banner + audit — Task 4
- ✅ Dashboard + AuditLog viewer + policy — Task 5
- ⏭️ RBAC Builder UI → Epic 3 (this epic covers auth only)
- ⏭️ Menu renderer → Epic 3

**Placeholder scan:** None.

**Name consistency:**
- `ImpersonationService::canStart/start/stop/isImpersonating()` — used consistently in tests + controller + middleware.
- `AuditLogger::log()` signature: `(event, user, newValues, request, oldValues=[], modelType=null, modelId=null)` — consistent.
- Route names: `login`, `logout`, `dashboard`, `password.change`, `password.change.store`, `impersonate.start`, `impersonate.stop`, `audit.index`.

**Pre-requisite check:** Epic 1 must be complete (migrations + seeders + TenantContext). Tasks reference `User::where('username', 'superadmin')` which requires `SuperAdminSeeder`.

**Test count:** Epic 2 adds ~18 tests (7 login + 2 audit + 3 force-reset + 6 impersonation + 3 dashboard).
