<?php

declare(strict_types=1);

namespace Magmi\Gui\App;

use Magmi\Gui\Controller\AuthController;
use Magmi\Gui\Controller\HomeController;
use Magmi\Gui\Controller\ProfileController;
use Magmi\Gui\Controller\ConfigController;
use Magmi\Gui\Controller\ImportController;
use Magmi\Gui\Auth\AuthManager;

class Router
{
    private array $routes = [];
    private array $publicRoutes = [];

    public function __construct()
    {
        $this->routes = [
            'GET /login' => [AuthController::class, 'index'],
            'POST /login' => [AuthController::class, 'login'],
            'POST /logout' => [AuthController::class, 'logout'],
            'GET /' => [HomeController::class, 'index'],
            'GET /profile' => [ProfileController::class, 'index'],
            'POST /profile/save' => [ProfileController::class, 'save'],
            'POST /profile/copy' => [ProfileController::class, 'copy'],
            'POST /profile/switch' => [ProfileController::class, 'switch'],
            'GET /config' => [ConfigController::class, 'index'],
            'POST /config/save' => [ConfigController::class, 'save'],
            'POST /config/test-db' => [ConfigController::class, 'testDb'],
            'POST /config/test-path' => [ConfigController::class, 'testPath'],
            'GET /import' => [ImportController::class, 'index'],
            'POST /import/run' => [ImportController::class, 'run'],
            'POST /import/preview' => [ImportController::class, 'preview'],
        ];
        $this->publicRoutes = ['GET /login', 'POST /login'];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $key = $method . ' ' . $uri;

        if (!isset($this->routes[$key])) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        if (!in_array($key, $this->publicRoutes, true)) {
            $auth = new AuthManager();
            if (!$auth->isAuthenticated()) {
                header('Location: /login');
                exit;
            }
        }

        if ($method === 'POST' && !\Magmi\Gui\Auth\Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo 'Invalid form token. Reload the page and try again.';
            return;
        }

        [$controllerClass, $action] = $this->routes[$key];
        $controller = new $controllerClass();
        $controller->$action();
    }
}
