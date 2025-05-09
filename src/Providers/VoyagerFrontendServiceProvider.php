<?php

namespace Pvtl\VoyagerFrontend\Providers;

use Illuminate\Http\Request;
use Pvtl\VoyagerFrontend\Commands;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Console\ImportCommand;
use Illuminate\Console\Scheduling\Schedule;
use Pvtl\VoyagerFrontend\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Pvtl\VoyagerFrontend\FormFields\FrontendLayout;
use Pvtl\VoyagerFrontend\Http\Controllers\PageController;

class VoyagerFrontendServiceProvider extends ServiceProvider
{
    /**
     * Our root directory for this package to make traversal easier
     */
    const PACKAGE_DIR = __DIR__ . '/../../';

    /**
     * Bootstrap the application services
     *
     * @param Request $request
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->strapEvents();
        $this->strapRoutes();
        $this->strapPublishers();
        $this->strapViews($request);
        $this->strapHelpers();
        $this->strapCommands();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(self::PACKAGE_DIR . 'config/voyager-frontend.php', 'voyager-frontend');

        // Merge our Scout config over
        $this->mergeConfigFrom(self::PACKAGE_DIR . 'config/scout.php', 'scout');

        $this->app->alias(VoyagerFrontend::class, 'voyager-frontend');

        $this->app->resolving('TCG\\Voyager\\Voyager', function ($voyager, $app) {
            $voyager->addFormField(FrontendLayout::class);
        });

        $this->app->register(PagesEventServiceProvider::class);
    }

    /**
     * Bootstrap our Events
     */
    protected function strapEvents()
    {
        // When an Eloquent Model is updated, re-generate our indices (could get intense)
        Event::listen(['eloquent.saved: *', 'eloquent.deleted: *'], function () {
            Artisan::call("voyager-frontend:generate-search-indices");
        });
    }

    /**
     * Bootstrap our Routes
     */
    protected function strapRoutes()
    {
        // Pull default web routes
        $this->loadRoutesFrom(base_path('/routes/web.php'));

        // Then add our Pages Routes
        $this->loadRoutesFrom(self::PACKAGE_DIR . 'routes/web.php');
    }

    /**
     * Bootstrap our Publishers
     */
    protected function strapPublishers()
    {
        // Defines which files to copy the root project
        $this->publishes([
            self::PACKAGE_DIR . 'resources/assets' => base_path('resources/assets'),
        ]);
    }

    /**
     * Bootstrap our Views
     * @param Request $request
     */
    protected function strapViews(Request $request)
    {
        // Front-end views can be used like:
        //  - @include('voyager-frontend::partials.meta') OR
        $this->loadViewsFrom(self::PACKAGE_DIR . 'resources/views', 'voyager-frontend');
        $this->loadViewsFrom(self::PACKAGE_DIR . 'resources/views/vendor/voyager', 'voyager');

    }

    /**
     * Load helpers.
     */
    protected function strapHelpers()
    {
        require_once self::PACKAGE_DIR . '/src/Helpers/ImageResize.php';
    }


    /**
     * Bootstrap our Commands/Schedules
     */
    protected function strapCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\ThumbnailsClean::class
            ]);
        }

        // Register our commands
        $this->commands([
            ImportCommand::class,
            Commands\GenerateSearchIndices::class
        ]);

        // Schedule our commands
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('voyager-frontend:clean-thumbnails')->dailyAt('13:00');
            $schedule->command('voyager-frontend:generate-search-indices')->dailyAt('13:30');
        });
    }
}
