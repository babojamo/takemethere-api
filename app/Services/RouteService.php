<?php

namespace App\Services;

use App\Http\Requests\NearestRouteRequest;
use App\Http\Requests\SaveRouteRequest;
use App\Models\Route;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;

class RouteService
{
    /**
     * Store routes
     * 
     * @param $request App\Http\Requests\SaveRouteRequest
     * @return $route Instance of App\Models\Route
     */
    public function store(SaveRouteRequest $request)
    {
        $data = $request->only([
            'name',
            'geojson',
            'points',
        ]);

        if (empty($data['geojson']) && empty($data['points'])) {
            throw new Exception("geojson or points is required", 1);
        }

        // Normalize to [[lng,lat], ...]
        $coords = !empty($data['geojson'])
            ? $data['geojson']['coordinates']
            : array_map(fn($p) => [$p['lng'], $p['lat']], $data['points']);

        // Build LineString (Point takes lat, lng)
        $line = new LineString(array_map(
            fn($c) => new Point($c[1], $c[0]),
            $coords
        ));

        // Save route
        $route = Route::create([
            'name' => $data['name'] ?? null,
            'geom' => $line,
            'points' => $data['points'] ?? null,
        ]);

        return $route;
    }

    /**
     * Find nearest routes
     * 
     * @param $request App\Http\Requests\NearestRouteRequest
     * @return $routes Collection of App\Models\Route
     */
    public function nearest(NearestRouteRequest $request): Collection
    {
        $data = $request->only([
            'origin_lat',
            'origin_lng',
            'destination_lat',
            'destination_lng',
            'radius',
        ]);

        $radius = $data['radius'] ?? 50; // default 2km

        // Nearest route from origin
        $originRoute = DB::table('routes')
            ->whereRaw(
                'ST_DWithin(geom, ST_MakePoint(?, ?)::geography, ?)',
                [$data['origin_lng'], $data['origin_lat'], $radius]
            )
            ->pluck('id')
            ->toArray();


        // Nearest route from destination
        $destinationRoute = DB::table('routes')
            ->whereRaw(
                'ST_DWithin(geom, ST_MakePoint(?, ?)::geography, ?)',
                [$data['destination_lng'], $data['destination_lat'], $radius]
            )
            ->pluck('id')
            ->toArray();

        // Match routes origin and destination
        $commonRoutes = array_values(array_intersect($originRoute, $destinationRoute));

        // Fetch routes
        $routes = Route::select(['id', 'name', 'points'])->whereIn('id', $commonRoutes)->get();

        return $routes;
    }
}
