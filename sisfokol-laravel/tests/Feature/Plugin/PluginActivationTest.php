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
        
        // Use withoutGlobalScopes because TenantPlugin uses BelongsToTenant scope 
        // which matches tenant_id of current user, but since $admin is acting,
        // it should match. Let's assert database has it.
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
        
        // Avoid global scope constraints during seeding
        TenantPlugin::create(['tenant_id' => $admin->tenant_id, 'plugin_id' => $plugin->id, 'aktif' => true]);

        $response = $this->actingAs($admin)
            ->post("/admin/plugins/{$plugin->kode}/deactivate");

        $response->assertRedirect();

        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $admin->tenant_id,
            'plugin_id' => $plugin->id,
            'aktif'     => false,
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
        
        // Assert session has impersonated_by
        $this->assertTrue(session()->has('impersonated_by'));

        $response = $this->post("/admin/plugins/{$plugin->kode}/activate");
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_activate(): void
    {
        $this->seed([RolePermissionSeeder::class, SuperAdminSeeder::class]);
        $tenant = Tenant::create(['nama' => 'T1', 'npsn' => '11111111']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Wrap Spatie role/permission query in team context
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->id);
        $user->assignRole('guru');
        $registrar->setPermissionsTeamId(null);

        $plugin = Plugin::create(['kode' => 'testplugin', 'nama' => 'Test']);

        $this->actingAs($user)->post("/admin/plugins/{$plugin->kode}/activate")->assertStatus(403);
    }
}
