<?php
/**
 * Minimal bootstrap for Lumen-like app
 */

require_once __DIR__.'/../vendor/autoload.php';

// Load environment variables from the app root if phpdotenv is available.
// This ensures the web process reads the same DB_* values you put in /mtl_app/.env
if (class_exists('Dotenv\\Dotenv')) {
    try {
        \Dotenv\Dotenv::createImmutable(realpath(__DIR__.'/..'))->safeLoad();
    } catch (Throwable $e) {
        // Ignore - if environment can't be loaded we'll fall back to defaults
    }
}

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
    'cors' => App\Http\Middleware\CorsMiddleware::class,
]);

// Register routes
$app->router->group([], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
