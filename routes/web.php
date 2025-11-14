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
$router->post('/api/record-payment', 'App\Http\Controllers\RecordPaymentController@handle');
$router->post('/api/trigger-import', 'App\Http\Controllers\ImportController@trigger');
