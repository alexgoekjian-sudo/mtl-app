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
