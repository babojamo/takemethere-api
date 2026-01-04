<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
});


Route::get('/routes', [RouteController::class, 'index']);
Route::post('/routes', [RouteController::class, 'store']);
Route::get('/routes/show/{id}', [RouteController::class, 'show']);
Route::get('/routes/nearest', [RouteController::class, 'nearest']);

// Route::group(['prefix' => 'utils'], function () {
//     Route::get('/item-types', [UtilityController::class, 'itemTypes'])->name('utils.item_types');
//     Route::get('/buyers', [UtilityController::class, 'buyers'])->name('utils.buyers');
//     Route::get('/departments', [UtilityController::class, 'departments'])->name('utils.departments');
//     Route::get('/sections', [UtilityController::class, 'sections'])->name('utils.sections');
//     Route::get('/processes', [UtilityController::class, 'processes'])->name('utils.processes');
//     Route::get('/find-styles', [UtilityController::class, 'findStyles'])->name('utils.find-styles');
//     Route::get('/operators', [UtilityController::class, 'operators'])->name('utils.operators');
//     Route::get('/find-bundles', [UtilityController::class, 'findBundles'])->name('utils.find-bundles');
//     Route::get('/production-track-classifications', [UtilityController::class, 'prodTrackClassifications'])->name('utils.production-track-classifications');
// });