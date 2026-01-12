<?php

namespace App\Services\RouteService;

use App\Constants\RouteConst;
use App\Http\Requests\NearestRouteRequest;
use App\Http\Requests\SaveRouteRequest;
use App\Models\Route;
use App\Services\RouteService\Traits\RouteDistance;
use App\ValueObjects\Coordinate;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;

class RouteService implements IRouteService
{
    use RouteDistance;

    public function all()
    {
        return Route::orderBy('name')->select(['name', 'id', 'points'])->where('status', RouteConst::STATUS_ACTIVE)->get();
    }

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
    public function findRoutes(NearestRouteRequest $request)
    {
        $data = $request->only([
            'origin_lat',
            'origin_lng',
            'destination_lat',
            'destination_lng',
            'radius',
        ]);

        $paths = [];
        $origin = new Coordinate((float) $data['origin_lat'], (float) $data['origin_lng']);
        $destination = new Coordinate((float) $data['destination_lat'], (float) $data['destination_lng']);
        $radius = (float) $data['radius'];

        $results = $this->paths($origin, $destination, $radius);

        // Compute fares and distance
        foreach ($results as $path) {
            if ($path->count() > 0) {
                // With multiple routes
                $paths[] = $this->computeDistanceMultipleRoutes($path, $origin, $destination, $radius);
            } else if ($path->count() == 0) {
                // Only single route
                $paths[] = $this->computeDistanceSingleRoutes($path[0], $origin, $destination);
            }
        }

        return $paths;
    }

    /**
     * Get the nearest routes based on origin and destination coordinates
     * 
     * @param $origin Origin Coordinates
     * @param $destination Destination Coordinates
     * @param $radius Radius in meters defaults to 50 meters
     * 
     * @return Collection
     */
    public function nearestRoutes(Coordinate $origin, Coordinate $destination, float $radius = 50): Collection
    {
        // Nearest route from origin
        $originRoute = DB::table('routes')
            ->whereRaw(
                'ST_DWithin(geom, ST_MakePoint(?, ?)::geography, ?)',
                [$origin->lng, $origin->lat, $radius]
            )
            ->pluck('id')
            ->toArray();


        // Nearest route from destination
        $destinationRoute = DB::table('routes')
            ->whereRaw(
                'ST_DWithin(geom, ST_MakePoint(?, ?)::geography, ?)',
                [$destination->lng, $destination->lat, $radius]
            )
            ->pluck('id')
            ->toArray();

        // Match routes origin and destination
        $commonRoutes = array_values(array_intersect($originRoute, $destinationRoute));

        // Fetch routes
        $routes = Route::select(['id', 'name', 'points'])->whereIn('id', $commonRoutes)->get();

        return $routes;
    }

    /**
     * Get possible paths
     * 
     * @param $origin Origin Coordinates
     * @param $destination Destination Coordinates
     * @param $radius Radius in meters defaults to 50 meters
     * @param $hoops Routes to intersect and defaults to 10.
     */
    public function paths(Coordinate $origin, Coordinate $destination, float $radius = 50, $hoops = 10): SupportCollection
    {
        $paths = [];
        $result = DB::select("
            WITH RECURSIVE start_routes AS (
                SELECT id FROM routes
                WHERE ST_DWithin(geom, ST_MakePoint(?,?)::geography, ?) and status = 'active'
            ),
            
            end_routes AS (
                SELECT id FROM routes
                WHERE ST_DWithin(geom, ST_MakePoint(?,?)::geography, ?) and status = 'active'
            ), 

            walk AS (
                SELECT s.id AS current, ARRAY[s.id] AS path, 0 AS hops
                FROM start_routes s
                UNION ALL
                SELECT e.b AS current, w.path || e.b, w.hops + 1
                FROM walk w
                JOIN route_edges e ON e.a = w.current
                WHERE NOT e.b = ANY(w.path)
                AND w.hops < ?
            ) SELECT path
            FROM walk
            WHERE current IN (SELECT id FROM end_routes)
            ORDER BY array_length(path, 1) ASC
            LIMIT 2
        ", [
            $origin->lng,
            $origin->lat,
            $radius,
            $destination->lng,
            $destination->lat,
            $radius,
            $hoops
        ]);

        foreach ($result as $value) {
            $ids = $this->parsePgArray($value->path);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Set paths
            $paths[] = Route::whereIn('id', $ids)
                ->orderByRaw("array_position(ARRAY[$placeholders]::uuid[], id)", $ids) // Order the routes based on the order of the path
                ->get();
        }

        return collect($paths);
    }

    /**
     * Parse postgres SQL Array to PHP Array
     */
    protected function parsePgArray(string $value): array
    {
        $value = trim($value, '{}');
        return $value === '' ? [] : explode(',', $value);
    }
}
