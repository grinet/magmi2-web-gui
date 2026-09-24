<?php

declare(strict_types=1);

namespace Magmi\Gui\Controller;

use Magmi\Gui\App\Theme;
use Magmi\Gui\App\View;
use Magmi\Gui\Auth\AuthManager;

class AuthController
{
    private AuthManager $auth;
    private View $view;

    public function __construct()
    {
        $this->auth = new AuthManager();
        $this->view = new View();
    }

    public function index(): void
    {
        if ($this->auth->isAuthenticated()) {
            header('Location: /');
            exit;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $mode = $this->auth->getAuthMode();
        $emergency = $mode === 'emergency' ? $this->auth->getEmergencyCredentials() : null;

        $this->view->render('login', [
            'mode' => $mode,
            'emergency' => $emergency,
            'error' => $error,
            'theme' => Theme::current(),
        ]);
    }

    public function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->auth->authenticate($username, $password)) {
            session_regenerate_id(true);
            $_SESSION['magmi_authenticated'] = true;
            $_SESSION['magmi_username'] = $username;
            $_SESSION['magmi_auth_mode'] = $this->auth->getAuthMode();
            header('Location: /');
            exit;
        }

        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: /login');
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}
