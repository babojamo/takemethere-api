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
    ];

    protected $casts = [
        'geom' => LineString::class,
        'points' => 'array',
    ];
}
