<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\AuthController;
use \App\Models\User;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return "HELLO WORLD!";
    // return $router->app->version();
});

$router->post('auth/login', 'AuthController@login');
$router->post('auth/signup', 'AuthController@register');
// $router->get('auth/user','AuthController@getAuthUser');

$router->group(['middleware'=>['auth','checkAdmin']], function($router){
    $router->get('auth/user','AuthController@getAuthUser');
});
$router->group(['middleware'=> 'auth'],function($router){
    $router->post('auth/logout','AuthController@logout');
});

$router->get('auth/verify/{code}','AuthController@userVerify');
