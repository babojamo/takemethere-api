<?php

namespace App\Providers;

use App\Services\RouteEdgeService\IRouteEdgeService;
use App\Services\RouteEdgeService\RouteEdgeService;
use App\Services\RouteService\IRouteService;
use App\Services\RouteService\RouteService;
use App\Services\UserService\IUserService;
use App\Services\UserService\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register services
     */
    protected function registerServices()
    {
        $this->app->bind(IRouteEdgeService::class, RouteEdgeService::class);
        $this->app->bind(IRouteService::class, RouteService::class);
        $this->app->bind(IUserService::class, UserService::class);
    }
}
