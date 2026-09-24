<?php

declare(strict_types=1);

namespace Magmi\Gui\Controller;

use Magmi\Core\Config\ProfileManager;
use Magmi\Core\Database\DbHelper;
use Magmi\Gui\App\BasePath;
use Magmi\Gui\App\Theme;
use Magmi\Gui\App\View;
use Magmi\Gui\Database\DbConfigFactory;

class ConfigController
{
    private ProfileManager $profileManager;
    private View $view;
    private string $configDir;

    public function __construct()
    {
        $this->configDir = BasePath::get() . '/data/config';
        $this->profileManager = new ProfileManager($this->configDir);
        $this->view = new View();
    }

    public function index(): void
    {
        $globalFile = $this->configDir . '/global.json';
        $savedAt = file_exists($globalFile) ? date('c', filemtime($globalFile)) : 'Not saved yet';

        $this->view->render('config', [
            'global' => $this->profileManager->getGlobalConfig(),
            'profiles' => $this->profileManager->list(),
            'savedAt' => $savedAt,
            'dbTest' => $_SESSION['db_test'] ?? null,
            'pathTest' => $_SESSION['path_test'] ?? null,
            'theme' => Theme::current(),
            'themeOptions' => Theme::options(),
        ]);
        unset($_SESSION['db_test']);
        unset($_SESSION['path_test']);
    }

    public function save(): void
    {
        $global = [
            'db_connection_mode' => $_POST['db_connection_mode'] ?? 'env_php',
            'db_host' => $_POST['db_host'] ?? 'localhost',
            'db_port' => $_POST['db_port'] ?? '3306',
            'db_name' => $_POST['db_name'] ?? '',
            'db_user' => $_POST['db_user'] ?? '',
            'db_pass' => $_POST['db_pass'] ?? '',
            'db_prefix' => $_POST['db_prefix'] ?? '',
            'magento_version' => $_POST['magento_version'] ?? '2.4.x',
            'magento_path' => $_POST['magento_path'] ?? '',
            'debug_log_path' => trim($_POST['debug_log_path'] ?? ''),
            'enable_msi' => isset($_POST['enable_msi']),
            'report_step' => $_POST['report_step'] ?? '0.5',
            'multiselect_separator' => $_POST['multiselect_separator'] ?? ',',
            'dir_permissions' => $_POST['dir_permissions'] ?? '755',
            'file_permissions' => $_POST['file_permissions'] ?? '644',
            'disable_attribute_set_update' => isset($_POST['disable_attribute_set_update']),
            'ui_theme' => Theme::sanitize($_POST['ui_theme'] ?? Theme::CLASSIC),
        ];

        $this->profileManager->saveGlobalConfig($global);
        Theme::clearCache();

        header('Location: /config');
        exit;
    }

    public function testDb(): void
    {
        $global = $this->profileManager->getGlobalConfig();
        $result = ['status' => 'error', 'message' => 'Database not configured.'];

        $dbConfig = DbConfigFactory::fromGlobalConfig($global);
        if ($dbConfig !== null) {
            try {
                $dbHelper = new DbHelper($dbConfig);
                $dbHelper->connect();

                $table = $dbHelper->tableName('catalog_product_entity');
                $count = (int) $dbHelper->selectOne("SELECT COUNT(*) AS c FROM {$table}", [], 'c');

                $result = [
                    'status' => 'success',
                    'message' => "Database connected. catalog_product_entity has {$count} rows.",
                ];
            } catch (\Exception $e) {
                $result = [
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $e->getMessage(),
                ];
            }
        }

        $_SESSION['db_test'] = $result;
        header('Location: /config');
        exit;
    }

    public function testPath(): void
    {
        $mode = $_POST['db_connection_mode'] ?? 'env_php';
        $magentoPath = trim($_POST['magento_path'] ?? '');
        $result = ['status' => 'error', 'message' => 'Magento path is empty.'];

        if ($magentoPath !== '') {
            $dirCheck = $this->checkWithErrorCapture(fn() => is_dir($magentoPath));
            if ($dirCheck['result'] === false) {
                $error = $dirCheck['error'] ? ' (' . $dirCheck['error'] . ')' : '';
                $result = [
                    'status' => 'error',
                    'message' => 'Magento path is not accessible. The directory may exist but PHP cannot read it.' . $error,
                ];
            } else {
                $messages = [];
                $status = 'success';

                $envFile = rtrim($magentoPath, '/\\') . '/app/etc/env.php';
                if ($mode === 'env_php') {
                    $envCheck = $this->checkWithErrorCapture(fn() => file_exists($envFile) && is_readable($envFile));
                    if ($envCheck['result']) {
                        $messages[] = 'env.php is readable.';
                    } else {
                        $status = 'error';
                        $error = $envCheck['error'] ? ' (' . $envCheck['error'] . ')' : '';
                        $messages[] = 'Cannot read app/etc/env.php. Check file permissions and open_basedir restrictions.' . $error;
                    }
                }

                $mediaDir = rtrim($magentoPath, '/\\') . '/pub/media/catalog/product';
                $mediaCheck = $this->checkWithErrorCapture(fn() => is_dir($mediaDir) && is_writable($mediaDir));
                $mediaReadableCheck = $this->checkWithErrorCapture(fn() => is_dir($mediaDir) && is_readable($mediaDir));
                if ($mediaCheck['result']) {
                    $messages[] = 'pub/media/catalog/product is writable.';
                } elseif ($mediaReadableCheck['result']) {
                    $messages[] = 'pub/media/catalog/product is readable but not writable. Image uploads may fail; consider FTP/SFTP for media.';
                } else {
                    $error = $mediaReadableCheck['error'] ?? $mediaCheck['error'];
                    $error = $error ? ' (' . $error . ')' : '';
                    $messages[] = 'Cannot access pub/media/catalog/product. Image uploads will not work via local path; use FTP/SFTP for media.' . $error;
                }

                $varImportDir = rtrim($magentoPath, '/\\') . '/var/import';
                $varImportCheck = $this->checkWithErrorCapture(fn() => is_dir($varImportDir));
                if ($varImportCheck['result']) {
                    $csvFiles = $this->checkWithErrorCapture(fn() => glob($varImportDir . '/*.{csv,xls,xlsx}', GLOB_BRACE));
                    $files = $csvFiles['result'] ?? [];
                    if (empty($files)) {
                        $messages[] = 'var/import folder exists but is empty. No CSV/XLS files found.';
                    } else {
                        $messages[] = 'var/import folder found with ' . count($files) . ' import file(s).';
                    }
                } else {
                    $error = $varImportCheck['error'] ? ' (' . $varImportCheck['error'] . ')' : '';
                    $messages[] = 'var/import folder not found. Import files must be placed there or uploaded via FTP/SFTP.' . $error;
                }

                $result = [
                    'status' => $status,
                    'message' => implode(' ', $messages),
                ];
            }
        }

        $_SESSION['path_test'] = $result;
        header('Location: /config');
        exit;
    }

    private function checkWithErrorCapture(callable $callable): array
    {
        $lastError = null;
        set_error_handler(function ($severity, $message) use (&$lastError) {
            $lastError = $message;
            return true;
        });
        $result = $callable();
        restore_error_handler();
        return ['result' => $result, 'error' => $lastError];
    }
}
