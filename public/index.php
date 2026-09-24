<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../vendor/autoload.php';

use Magmi\Gui\App\Router;

$router = new Router();
$router->dispatch();
