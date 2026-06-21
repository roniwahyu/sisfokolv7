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
