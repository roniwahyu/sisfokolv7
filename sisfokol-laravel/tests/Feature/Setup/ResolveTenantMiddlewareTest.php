<?php

namespace Tests\Feature\Setup;

use App\Modules\Tenancy\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveTenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_login_leaves_context_uninitialized(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'tipe' => 'super_admin']);

        $response = $this->actingAs($user)->get('/');

        $ctx = app(TenantContext::class);
        $this->assertFalse($ctx->isInitialized());
    }

    public function test_normal_user_initializes_context_with_their_tenant(): void
    {
        $tenant = Tenant::create(['nama' => 'SMP Test', 'npsn' => '12345678']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'tipe' => 'admin_sekolah']);

        // Use a route wrapped with 'web' middleware group (includes ResolveTenant)
        $response = $this->actingAs($user)->get('/');

        $ctx = app(TenantContext::class);
        $this->assertTrue($ctx->isInitialized());
        $this->assertSame($tenant->id, $ctx->id);
    }
}
