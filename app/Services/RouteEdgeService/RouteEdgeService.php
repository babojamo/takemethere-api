<?php

namespace App\Services\RouteEdgeService;

use App\Constants\RouteConst;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

class RouteEdgeService implements IRouteEdgeService
{
    /**
     * Create route network
     * 
     *  @param $route
     */
    public function makeEdges(Route $route) {
        $id = $route->id;
        $radius = RouteConst::EDGE_TOLERANCE;

        /**
         * Create route network:
         *  Connect routes to other nearest routes by using ST_DWithin on a specific radius
         */
        DB::unprepared("
            /*MAKE PATH*/
            INSERT INTO route_edges (a, b)
                SELECT r1.id, r2.id
                FROM routes r1
                JOIN routes r2
                ON r1.id < r2.id
                AND ST_DWithin(r1.geom, r2.geom, {$radius}) AND r1.id = '{$id}';


            /*REVERSE PATH*/

            INSERT INTO route_edges (a, b)
                SELECT b, a FROM route_edges where a = '{$id}';
        ");

        return true;
    }
    
    /**
     * Clear route network
     * 
     * @param $route
     */
    public function clearEdges(Route $route) {

        DB::table('route_edges')
            ->orWhere('a', $route->id)
            ->orWhere('b', $route->id)
            ->delete();
            
        return true;
    }
}
