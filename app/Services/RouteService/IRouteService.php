<?php

namespace App\Services\RouteService;

use App\Http\Requests\NearestRouteRequest;
use App\Http\Requests\SaveRouteRequest;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface IRouteService
{
    public function all();

    public function findRoutes(NearestRouteRequest $request);

    public function nearestRoutes(Coordinate $origin, Coordinate $destination, float $radius = 50): Collection;

    public function paths(Coordinate $origin, Coordinate $destination, float $radius = 50, $hoops = 10): SupportCollection;
    
    public function store(SaveRouteRequest $request);
}
