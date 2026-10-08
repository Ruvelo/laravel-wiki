<?php

namespace Ruvelo\Wiki;

use Ruvelo\Wiki\Markdown\Renderer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WikiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wiki.php', 'wiki');

        $this->app->singleton(Renderer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wiki');

        View::composer('wiki::*', function ($view) {
            $view->with('canEdit', Wiki::canEdit());
        });

        if (config('wiki.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('wiki.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/wiki.php' => config_path('wiki.php'),
            ], 'wiki-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/wiki'),
            ], 'wiki-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'wiki-migrations');
        }
    }
}
