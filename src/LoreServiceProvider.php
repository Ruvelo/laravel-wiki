<?php

namespace FrancoisBultez\Lore;

use FrancoisBultez\Lore\Markdown\Renderer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class LoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lore.php', 'lore');

        $this->app->singleton(Renderer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lore');

        View::composer('lore::*', function ($view) {
            $view->with('canEdit', Lore::canEdit());
        });

        if (config('lore.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('lore.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lore.php' => config_path('lore.php'),
            ], 'lore-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/lore'),
            ], 'lore-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'lore-migrations');
        }
    }
}
