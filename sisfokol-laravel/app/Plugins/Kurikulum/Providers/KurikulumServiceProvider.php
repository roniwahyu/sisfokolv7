<?php

namespace App\Plugins\Kurikulum\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class KurikulumServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        \App\Plugins\Kurikulum\Subscribers\EvaluationFrameworkSubscriber::class,
        \App\Plugins\Kurikulum\Subscribers\RaporSectionSubscriber::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        parent::boot();
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'kurikulum');
    }
}
