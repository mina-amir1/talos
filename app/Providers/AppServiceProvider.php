<?php

namespace App\Providers;

use App\Console\Commands\InstallCommand;
use App\Services\ComponentService;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use App\Services\SchemaGeneratorService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContentTypeService::class);
        $this->app->singleton(ComponentService::class);
        $this->app->singleton(SchemaGeneratorService::class);
        $this->app->singleton(DynamicModelService::class);
    }

    public function boot(): void
    {
        // Use Tailwind-styled pagination
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
