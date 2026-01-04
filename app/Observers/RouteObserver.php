<?php

namespace App\Observers;

use App\Models\Route;
use App\Services\RouteEdgeService;

class RouteObserver
{
    public function __construct(protected RouteEdgeService $routeEdgeService) {}

    /**
     * Handle the Route "created" event.
     */
    public function created(Route $route): void
    {
        $this->routeEdgeService->makeEdges($route);
    }

    /**
     * Handle the Route "updated" event.
     */
    public function updated(Route $route): void
    {
        // Clear Edges
        $this->routeEdgeService->clearEdges($route);

        // Remake Edges
        $this->routeEdgeService->makeEdges($route);
    }

    /**
     * Handle the Route "deleted" event.
     */
    public function deleted(Route $route): void
    {
        $this->routeEdgeService->clearEdges($route);
    }

    /**
     * Handle the Route "restored" event.
     */
    public function restored(Route $route): void
    {
        $this->routeEdgeService->makeEdges($route);
    }

    /**
     * Handle the Route "force deleted" event.
     */
    public function forceDeleted(Route $route): void
    {
        $this->routeEdgeService->clearEdges($route);
    }
}
