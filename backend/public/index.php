<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Resuelve automáticamente la raíz de Laravel tanto en local como en producción.
$appBasePathCandidates = [
    dirname(__DIR__),
    __DIR__.'/../../data/fitloop',
];

$appBasePath = null;

foreach ($appBasePathCandidates as $candidate) {
    if (file_exists($candidate.'/vendor/autoload.php') && file_exists($candidate.'/bootstrap/app.php')) {
        $appBasePath = $candidate;
        break;
    }
}

if ($appBasePath === null) {
    throw new RuntimeException('No se pudo localizar la raiz de Laravel.');
}

if (file_exists($maintenance = $appBasePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appBasePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appBasePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
