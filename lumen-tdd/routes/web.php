<?php

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
    return $router->app->version();
});

$router->post('/api/authenticate', 'UserController@authenticate');

$router->group(['prefix' => '/api', 'middleware' => 'auth'], function ($router) {

    $router->get('/users', 'UserController@index');
    $router->post('/user', 'UserController@store');
    $router->get('/user/{id}', 'UserController@show');
    $router->put('/user/{id}', 'UserController@update');
    $router->delete('/user/{id}', 'UserController@destroy');
});
