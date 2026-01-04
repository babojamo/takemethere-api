<?php

use App\Constants\RouteConst;
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
        Schema::table('routes', function (Blueprint $table) {
            $table->float('base_fare')->nullable();
            $table->float('base_fare_minimum_unit')->nullable()->comment('Base fare minimum unit effective');
            $table->float('base_fare_increment')->nullable()->comment('Increase fare on succeeding unit');
            $table->enum('fare_unit', ['km', 'm'])->nullable();
            $table->string('status')->default(RouteConst::STATUS_ACTIVE);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('base_fare', 'base_fare_minimum_unit', 'base_fare_increment', 'fare_unit');
        });
    }
};
