<?php

namespace App\Services\RouteEdgeService;

use App\Models\Route;

interface IRouteEdgeService
{
    public function clearEdges(Route $route);
    
    public function makeEdges(Route $route);
}
