<?php
/**
 * Minimal Lumen public/index.php front controller
 * Expects vendor/autoload.php to be present after `composer install`
 */

define('LUMEN_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$app->run();
