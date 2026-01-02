<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Enable extensions (safe if already enabled)
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto;');

        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name')->nullable();
            $table->jsonb('points')->nullable(); // optional: store original [{lat,lng}] for UI/debug
            $table->timestamps();
        });

        // Store route as PostGIS geography LineString (SRID 4326)
        DB::statement('ALTER TABLE routes ADD COLUMN geom geography(LineString, 4326) NOT NULL;');

        // Spatial index for fast geo queries
        DB::statement('CREATE INDEX routes_geom_gix ON routes USING GIST (geom);');
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
