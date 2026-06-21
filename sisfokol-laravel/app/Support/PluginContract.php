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
