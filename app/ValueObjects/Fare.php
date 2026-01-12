<?php

namespace App\ValueObjects;

use App\Constants\RouteConst;
use App\Models\Route;

final class Fare
{
    public float $estimate_fare;
    public float $estimate_distance;

    public function __construct(
        public Route $route, // Route
        public $end_coordinate, // End coordinate from trimmed intersecting routes
        protected float $distance

    ) {
        $this->init();
    }

    protected function init()
    {
        $this->estimate_distance = $this->distance;
        $this->estimate_fare = $this->computeFareRoute($this->route, $this->distance);
    }

    /**
     * Compuete fare
     * 
     * @param $distance Distance from origin to destination points in Meter
     * @param $baseFare Base fare of the route
     * @param $distanceToIncrement
     * @param $incrementFare
     * @param $fareUnit KM or Meter 
     */
    protected function computeFare(float $distance, float $baseFare, float $distanceToIncrement, float $incrementFare, string $fareUnit)
    {

        $distance = $distance / 1000; // Convert to KM

        if ($fareUnit == RouteConst::DISTANCE_METER)
            $distanceToIncrement = $distanceToIncrement / 1000; // Convert to KM


        // Compute additional fare if the distance exceeds the minimum base fare distance
        $additionalFare = ($distance > $distanceToIncrement) ? ($incrementFare * ceil($distance - $distanceToIncrement)) : 0;

        return $baseFare + $additionalFare;
    }

    protected function computeFareRoute(Route $route, float $distance)
    {
        return $this->computeFare(
            (float) $distance,
            (float) $route->base_fare,
            (float) $route->base_fare_minimum_unit,
            (float) $route->base_fare_increment,
            $route->fare_unit
        );
    }
}
