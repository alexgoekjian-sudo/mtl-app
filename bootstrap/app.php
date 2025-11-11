<?php
/**
 * Minimal bootstrap for Lumen-like app
 */

require_once __DIR__.'/../vendor/autoload.php';

// Create the app
$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/..')
);

// Register routes
$app->router->group([], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
