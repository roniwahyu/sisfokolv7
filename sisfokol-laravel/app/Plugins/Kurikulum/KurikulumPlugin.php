<?php

namespace App\Plugins\Kurikulum;

use App\Support\{PluginContract, PluginContext};

class KurikulumPlugin implements PluginContract
{
    public function kode(): string
    {
        return 'kurikulum';
    }

    public function nama(): string
    {
        return 'Kurikulum';
    }

    public function versi(): string
    {
        return '1.0.0';
    }

    public function isCore(): bool
    {
        return false;
    }

    public function dependencies(): array
    {
        return [];
    }

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
        // Handled by ServiceProvider
    }
}
