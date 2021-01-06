<?php

use App\Microservice\Tools\ErrorsGenerator;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Route scheme is: https://site.com/ api [api routes group] / {microservice name} / ... / {object name} / ... / {action name}
 */

/**
 * Routes for included authentication API
 */
Route::prefix('auth')->group(function (){
    Route::any('register','AuthController@register');
    Route::any('logout','AuthController@logout');
    Route::any('login','AuthController@login');

    Route::prefix('tokens')->group(function (){
        Route::any('create','AuthController@createToken');
        Route::any('get','AuthController@getTokens');
    });
});

/**
 * Routes microservice API
 */
Route::prefix('{microserviceName}')->group(function (){
    Route::any('hello','MainController@hello');
});


/**
 * API 404 handling
 */
Route::fallback(function (ErrorsGenerator $errorGenerator){
    return $errorGenerator->createContent([],1);
});
