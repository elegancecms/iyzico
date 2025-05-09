<?php

namespace EleganceCMS\Iyzico\Providers;

use EleganceCMS\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class IyzicoServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (!is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/iyzico')
            ->loadHelpers()
            ->loadRoutes()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->publishAssets();

        $this->app->register(HookServiceProvider::class);
    }
}