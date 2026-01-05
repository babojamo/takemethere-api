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
            $table->float('base_fare')->default(20);
            $table->float('base_fare_minimum_unit')->default(1)->comment('Base fare minimum unit effective and defaults to 1 KM(base on the fare unit)');
            $table->float('base_fare_increment')->default(2)->comment('Increase fare on succeeding unit');
            $table->enum('fare_unit', [RouteConst::DISTANCE_METER, RouteConst::DISTANCE_KM])->default(RouteConst::DISTANCE_KM);
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
