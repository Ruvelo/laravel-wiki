<?php

declare(strict_types=1);

namespace Ruvelo\Wiki;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Ruvelo\Wiki\Console\ExportCommand;
use Ruvelo\Wiki\Console\ImportCommand;
use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\Navigation;

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

        View::composer('wiki::*', function ($view): void {
            $view->with('canEdit', Wiki::canEdit());
        });

        // The menu renders only if the layout shows it (not in the editor).
        View::composer('wiki::layout', function ($view): void {
            $data = $view->getData();
            $current = ($data['page'] ?? null) instanceof Page ? $data['page']->slug : ($data['slug'] ?? null);

            $view->with('sidebar', fn (): string => Navigation::sidebar(is_string($current) ? $current : null));
        });

        if (config('wiki.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('wiki.api.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        if (config('wiki.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([ImportCommand::class, ExportCommand::class]);

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
