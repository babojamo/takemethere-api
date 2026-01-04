<?php

namespace App\Services;

use App\Constants\RouteConst;
use App\Http\Requests\NearestRouteRequest;
use App\Http\Requests\SaveRouteRequest;
use App\Models\Route;
use App\ValueObjects\Coordinate;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;

class RouteService
{
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
            if ($path->count() > 0) { // With multiple routes
                $paths = $this->computeDistanceMultipleRoutes($path, $origin, $destination, $radius);
            } else if ($path->count() == 0) { // Only single route
                // Compute the distance
                $path[0]['distance'] = $this->distanceBetweenCoordinates(
                    $path[0],
                    $origin,
                    $destination
                );

                // Compute the estiamte fare
                // @TODO
                $path[0]['estimate_fare'] = 0;

                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Compute the distance between two points Origin and Destination based on the route mapping
     * 
     * @param $route
     * @param $origin
     * @param $destination
     * 
     * @return $distance Distance in meter
     */
    public function distanceBetweenCoordinates(Route $route, Coordinate $origin, Coordinate $destination): float
    {
        $row = DB::selectOne("
                    WITH r AS (
                    SELECT id, geom::geometry AS g
                    FROM routes
                    WHERE id = ?
                    ),
                    p AS (
                    SELECT
                        r.id,
                        r.g,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326) AS p1,
                        ST_SetSRID(ST_MakePoint(?, ?), 4326) AS p2
                    FROM r
                    ),
                    m AS (
                    SELECT
                        id,
                        g,
                        ST_LineLocatePoint(g, ST_ClosestPoint(g, p1)) AS f1,
                        ST_LineLocatePoint(g, ST_ClosestPoint(g, p2)) AS f2
                    FROM p
                    ),
                    seg AS (
                    SELECT
                        id,
                        CASE
                        WHEN f1 <= f2 THEN ST_LineSubstring(g, f1, f2)
                        ELSE ST_LineSubstring(g, f2, f1)
                        END AS part
                    FROM m
                    )
                    SELECT ST_Length(part::geography) AS distance_meters
                    FROM seg
                ", [
            $route->id,
            $origin->lng,
            $origin->lat,
            $destination->lng,
            $destination->lat,
        ]);

        return (float) ($row->distance_meters ?? 0);
    }

    /**
     * Get Intersecting Coordinates between two routes
     * 
     * @param $originRoute Route
     * @param $destinationRoute Route
     * @param $radius Defaults to 10
     */
    public function intersectingCoordinates(Route $originRoute, Route $destinationRoute, $radius = 10): Coordinate | null
    {

        $coordinate = DB::select("
                SELECT
                ST_Y(p1) AS lat,
                ST_X(p1) AS lng
                FROM (
                SELECT
                    ST_ClosestPoint(r1.geom::geometry, r2.geom::geometry) AS p1
                FROM routes r1
                JOIN routes r2
                ON r1.id = ?
                AND r2.id = ?
                WHERE ST_DWithin(r1.geom, r2.geom, ?)
                ) t;
            ", [$originRoute->id, $destinationRoute->id, $radius]);

        if (count($coordinate) > 0)
            return new Coordinate($coordinate[0]->lat, $coordinate[0]->lng);

        return null;
    }


    /**
     * Compute distance on multiple routes in a path
     * 
     * @param $path Path
     * @param $origin
     * @param $destination
     * @param $radius Defaults to 10
     */
    public function computeDistanceMultipleRoutes($path, Coordinate $origin, Coordinate $destination, float $radius = 10)
    {
        $startingCoordinate = $origin;
        $endCoordinate = null;

        $lastIndex = count($path) - 1;

        // Multiple paths
        for ($i = 0; $i < count($path); $i++) {

            $currentPath = $path[$i];

            if ($i === $lastIndex) {
                // Compute the distance between the last recorded coordinate and the destiantion coordinates
                $path[$i]['distance'] = $this->distanceBetweenCoordinates(
                    $currentPath,
                    $startingCoordinate,
                    $destination
                );
            } else {

                // Compute the distance between the last recorded coordinate and the last recorded starting coordinates
                $routeB = $path[$i + 1];
                $endCoordinate = $this->intersectingCoordinates($currentPath, $routeB, $radius);
                $path[$i]['distance'] = $this->distanceBetweenCoordinates(
                    $currentPath,
                    $startingCoordinate,
                    $endCoordinate
                );
            }

            // Compute the estiamte fare
            // @TODO
            $path[$i]['estimate_fare'] = 0;

            // Set the starting as end coordinates for the next distance computation
            $startingCoordinate = $endCoordinate;
        }

        return $path;
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
            ), end_routes AS (
                SELECT id FROM routes
                WHERE ST_DWithin(geom, ST_MakePoint(?,?)::geography, ?) and status = 'active'
            ), walk AS (
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
            LIMIT 3
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
