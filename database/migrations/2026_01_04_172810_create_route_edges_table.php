<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('route_edges', function (Blueprint $table) {
            $table->foreignUuid('a');
            $table->foreignUuid('b');
            $table->primary(['a','b']);
            $table->index('a');
            $table->index('b');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_edges');
    }
};
