<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use MatanYadaev\EloquentSpatial\Objects\LineString;

class Route extends Model
{
    use HasSpatial;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'geom',
        'points',

        'base_fare',
        'base_fare_minimum_unit',
        'base_fare_increment',
        'fare_unit',

        'status'
    ];

    protected $casts = [
        'geom' => LineString::class,
        'points' => 'array',
    ];
}
