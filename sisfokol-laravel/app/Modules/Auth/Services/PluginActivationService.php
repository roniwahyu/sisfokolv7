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
                ]
            );

            // Seed plugin permissions
            if ($manifest) {
                $registrar = app(PermissionRegistrar::class);
                $registrar->setPermissionsTeamId($tenantId);
                foreach ($manifest->permissions() as $perm) {
                    Permission::firstOrCreate([
                        'name' => $perm['name'],
                        'guard_name' => $perm['guard_name'] ?? 'web',
                    ], [
                        'module' => $perm['module'] ?? $manifest->nama(),
                    ]);
                }
                $registrar->setPermissionsTeamId(null);
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
            
            // Query without global scope to update correctly
            TenantPlugin::where('tenant_id', $tenantId)
                ->where('plugin_id', $plugin->id)
                ->update(['aktif' => false]);

            $this->registry->clearTenantCache($tenantId, $kode);
            MenuRenderer::clearCache();
            FieldAcl::clearCache();

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
