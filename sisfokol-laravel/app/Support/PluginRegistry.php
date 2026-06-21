<?php

namespace App\Support;

use App\Plugins\Infrastructure\Models\{Plugin, TenantPlugin};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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

        // Defensive check: only sync if migrations have run and table exists
        if (! Schema::hasTable('plugins')) {
            return;
        }

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

        // Defensive check: if table doesn't exist, return false
        if (! Schema::hasTable('plugins') || ! Schema::hasTable('tenant_plugins')) {
            return false;
        }

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
            Cache::forget("plugin.active.{$tenantId}.{$kode}");
        } else {
            Cache::flush();
        }
    }

    private function ensureScanned(): void
    {
        if (! $this->scanned) $this->rescan();
    }
}
