<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class Coordinate implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {
        if ($lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90');
        }
        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180');
        }
    }

    /** GeoJSON / PostGIS order */
    public function toArray(): array
    {
        return [
            'lng' => $this->lng,
            'lat' => $this->lat,
        ];
    }

    /** PostGIS friendly [lng,lat] */
    public function toGeoArray(): array
    {
        return [$this->lng, $this->lat];
    }

    /** Raw SQL helper */
    public function toSqlPoint(): string
    {
        return "ST_SetSRID(ST_MakePoint({$this->lng}, {$this->lat}), 4326)";
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
