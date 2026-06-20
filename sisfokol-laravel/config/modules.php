<?php

return [
    // 6 core modules (always active) — ADR-004
    'core' => ['Tenancy', 'Auth', 'Academic', 'Evaluation', 'Finance', 'Presence'],

    // Plugin discovery path
    'plugins_path' => app_path('Plugins'),

    // Namespace pattern
    'module_namespace' => 'App\\Modules\\',
    'plugin_namespace' => 'App\\Plugins\\',
];
