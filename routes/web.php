<?php

$router->get('/', function () use ($router) {
    return response()->json(['ok' => true, 'app' => 'MTL_App']);
});

$router->get('/health', function () use ($router) {
    return response()->json(['status' => 'ok']);
});

$router->get('/status', function () use ($router) {
    return response()->json(['status' => 'ok', 'route' => '/status']);
});

// Minimal API endpoints for Retool to call for side-effectful operations.
// Retool endpoints (protected)
// These are protected by API token auth in the group below.

// Authentication
$router->post('/api/auth/login', 'App\Http\Controllers\AuthController@login');

// Protected APIs (require token)
$router->group(['middleware' => 'auth.token', 'prefix' => 'api'], function () use ($router) {
    $router->post('/auth/logout', 'App\Http\Controllers\AuthController@logout');
    $router->get('/auth/me', 'App\Http\Controllers\AuthController@me');

    // Protected Retool endpoints
    $router->post('/record-payment', 'App\Http\Controllers\RecordPaymentController@handle');
    $router->post('/trigger-import', 'App\Http\Controllers\ImportController@trigger');
    // additional protected endpoints can be added here
});
