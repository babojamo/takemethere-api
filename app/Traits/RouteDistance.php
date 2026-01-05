<?php

namespace App\Traits;

use App\Models\Route;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Fare;
use Illuminate\Support\Facades\DB;

trait RouteDistance
{
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
        $routes = [];
        $startingCoordinate = $origin;
        $endCoordinate = null;

        $lastIndex = count($path) - 1;

        // Multiple paths
        for ($i = 0; $i < count($path); $i++) {

            $currentPath = $path[$i];

            if ($i === $lastIndex) {
                // Compute the distance between the last recorded coordinate and the destiantion coordinates
                $distance = $this->distanceBetweenCoordinates(
                    $currentPath,
                    $startingCoordinate,
                    $destination
                );

                // Append to routes
                $routes[] = new Fare(
                    $currentPath,
                    (float) $distance
                );
            } else {

                $routeB = $path[$i + 1];
                $endCoordinate = $this->intersectingCoordinates($currentPath, $routeB, $radius);

                // Compute the distance between the last recorded coordinate and the last recorded starting coordinates
                $distance = $this->distanceBetweenCoordinates(
                    $currentPath,
                    $startingCoordinate,
                    $endCoordinate
                );

                // Append to routes
                $routes[] = new Fare(
                    $currentPath,
                    (float) $distance
                );
            }


            // Set the starting as end coordinates for the next distance computation
            $startingCoordinate = $endCoordinate;
        }

        return $routes;
    }

    public function computeDistanceSingleRoutes($path, Coordinate $origin, Coordinate $destination)
    {
        // Compute the distance
        $distance = $this->distanceBetweenCoordinates(
            $path,
            $origin,
            $destination
        );

        return new Fare(
            $path,
            (float) $distance
        );
    }
}
