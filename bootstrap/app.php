<?php
/**
 * Minimal bootstrap for Lumen-like app
 */

require_once __DIR__.'/../vendor/autoload.php';

// Create the app
$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/..')
);

// Enable facades and Eloquent if not already enabled (some hosts require explicit enablement)
if (method_exists($app, 'withFacades')) {
    $app->withFacades();
}
if (method_exists($app, 'withEloquent')) {
    $app->withEloquent();
}

// Bind the exception handler interface to the app's handler implementation
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Route middleware
$app->routeMiddleware([
    'auth.token' => App\Http\Middleware\ApiTokenAuth::class,
]);

// Register routes
$app->router->group([], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
