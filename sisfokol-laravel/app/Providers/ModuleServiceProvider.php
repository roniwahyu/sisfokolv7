<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register migrations from each module/plugin (topological by filename)
        $this->loadMigrationsFrom(
            collect(array_merge(
                $this->coreModuleMigrationPaths(),
                $this->pluginMigrationPaths(),
            ))->flatten()->all()
        );
    }

    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleViews();
    }

    private function coreModuleMigrationPaths(): array
    {
        $paths = [];
        $coreModules = config('modules.core');
        if (is_array($coreModules)) {
            foreach ($coreModules as $module) {
                $path = app_path("Modules/{$module}/Database/Migrations");
                if (File::isDirectory($path)) $paths[] = $path;
            }
        }
        return $paths;
    }

    private function pluginMigrationPaths(): array
    {
        $paths = [];
        $pluginsPath = config('modules.plugins_path');
        if ($pluginsPath && File::isDirectory($pluginsPath)) {
            foreach (File::directories($pluginsPath) as $pluginDir) {
                $migPath = $pluginDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
                if (File::isDirectory($migPath)) $paths[] = $migPath;
            }
        }
        return $paths;
    }

    private function loadModuleRoutes(): void
    {
        $coreModules = config('modules.core');
        if (is_array($coreModules)) {
            foreach ($coreModules as $module) {
                $routeFile = app_path("Modules/{$module}/routes.php");
                if (File::exists($routeFile)) {
                    $this->loadRoutesFrom($routeFile);
                }
            }
        }
        // Plugin routes loaded conditionally per-tenant via EnsurePluginEnabled middleware (next epics)
    }

    private function loadModuleViews(): void
    {
        $coreModules = config('modules.core');
        if (is_array($coreModules)) {
            foreach ($coreModules as $module) {
                $viewPath = app_path("Modules/{$module}/Resources/views");
                if (File::isDirectory($viewPath)) {
                    $this->loadViewsFrom($viewPath, strtolower($module));
                }
            }
        }
    }
}
