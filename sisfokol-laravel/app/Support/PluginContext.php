<?php

namespace App\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;

class PluginContext
{
    public function __construct(
        public readonly ?int $tenantId,
        public readonly array $settings = [],
        protected Dispatcher $events,
    ) {}

    public function events(): Dispatcher { return $this->events; }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function routes(\Closure $callback, array $options = []): void
    {
        Route::group(array_merge([
            'middleware' => array_filter(['web', 'auth', 'plugin:' . ($options['plugin'] ?? '')]),
            'prefix' => $options['prefix'] ?? '',
        ], $options), $callback);
    }
}
