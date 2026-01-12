<?php

namespace App\Http\Controllers;

use App\Http\Requests\NearestRouteRequest;
use App\Http\Requests\SaveRouteRequest;
use App\Services\RouteService\IRouteService;

class RouteController extends Controller
{
    public function __construct(protected IRouteService $route_service) {}

    public function index() {
        return response()->json($this->route_service->all());
    }

    /**
     * Store Route
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function store(SaveRouteRequest $request)
    {
        try {
            $route = $this->route_service->store($request);
            return response()->json($route);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /**
     * Find nearest route based on origin and destination coordinates
     * 
     * @param origin_lat Origin Latitude
     * @param origin_lng Origin Latitude
     * @param destination_lat Destination Latitude
     * @param destination_lng Destination Longitude
     * @param radius Radius threshold
     * 
     * @return Illuminate\Http\JsonResponse
     */
    public function nearest(NearestRouteRequest $request)
    {
        try {
            $route = $this->route_service->findRoutes($request);
            return response()->json($route);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
