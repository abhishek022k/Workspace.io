<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

// $router->get('/', ['middleware' => 'cors', function () use ($router) {
//     // return "Backend is running!";
//     return response()->json(
//         [
//             'message' => 'Backend cors enabled',
//         ],
//         200
//     );
//     // return $router->app->version();
// }]);
$router->get('/', function () use ($router) {
    return response()->json(
        [
            'message' => 'Backend cors enabled',
        ],
        200
    );
});
$router->post('auth/login', 'AuthController@login');
$router->post('auth/signup', 'AuthController@register');
// $router->get('auth/user','AuthController@getAuthUser');

$router->group(['middleware' => ['auth', 'checkAdmin']], function ($router) {
    $router->post('users/delete','UserController@delete');
    $router->post('users/restore','UserController@restore');
});
$router->group(['middleware' => 'auth'], function ($router) {
    $router->get('users/list', 'UserController@showList');
    $router->get('users/list-all', 'UserController@allUsers');
    $router->get('users/{user}','UserController@detail');
    $router->get('auth/user', 'AuthController@getAuthUser');
    $router->post('auth/logout', 'AuthController@logout');
    $router->post('tasks/create', 'TaskController@create');
    $router->post('tasks/update', 'TaskController@update');
    $router->get('tasks/list', 'TaskController@showList');
    $router->get('tasks/pie','TaskController@retrievePieData');
    $router->get('tasks/col','TaskController@retrieveColumnData');
});
$router->get('auth/verify/{code}', 'AuthController@userVerify');
$router->post('auth/forgot-password', 'AuthController@resetPasswordMail');
$router->get('auth/reset-token/{code}', 'AuthController@checkPasswordToken');
$router->post('auth/password-reset', 'AuthController@resetPassword');
