<?php

declare(strict_types=1);

namespace Magmi\Gui\Controller;

use Magmi\Core\Config\ProfileManager;
use Magmi\Core\Database\DbHelper;
use Magmi\Core\Datasource\DatasourceFactory;
use Magmi\Core\Import\ImportEngine;
use Magmi\Core\Plugin\PluginDiscovery;
use Magmi\Core\Plugin\PluginManager;
use Magmi\Gui\App\BasePath;
use Magmi\Gui\App\Theme;
use Magmi\Gui\App\View;
use Magmi\Gui\Database\DbConfigFactory;
use Magmi\Gui\Support\ImportFileLocator;

class ImportController
{
    private ProfileManager $profileManager;
    private View $view;
    private string $configDir;
    private string $pluginDir;

    public function __construct()
    {
        $this->configDir = BasePath::get() . '/data/config';
        $this->pluginDir = BasePath::get() . '/plugins';
        $this->profileManager = new ProfileManager($this->configDir);
        $this->view = new View();
    }

    public function index(): void
    {
        $report = $_SESSION['import_report'] ?? null;
        unset($_SESSION['import_report']);

        $preview = $_SESSION['import_preview'] ?? null;
        unset($_SESSION['import_preview']);

        $global = $this->profileManager->getGlobalConfig();
        $currentProfile = $this->getCurrentProfile();
        $dsConfig = $currentProfile->getDatasourceConfig();
        $selectedFile = ImportFileLocator::resolveProfileFile($dsConfig, $global);
        if ($selectedFile && file_exists($selectedFile)) {
            $selectedFile = realpath($selectedFile) ?: $selectedFile;
        }

        $this->view->render('import', [
            'profiles' => $this->profileManager->list(),
            'currentProfile' => $currentProfile,
            'report' => $report,
            'preview' => $preview,
            'selectedImportFile' => $selectedFile,
            'selectedImportMode' => $dsConfig['mode'] ?? 'xcreate',
            'debugChecked' => $_SESSION['debug_checked'] ?? false,
            'debugLogChecked' => $_SESSION['debug_log_checked'] ?? false,
            'importLimit' => $_SESSION['import_limit'] ?? '',
            'theme' => Theme::current(),
        ]);
        unset($_SESSION['debug_checked'], $_SESSION['debug_log_checked'], $_SESSION['import_limit']);
    }

    public function run(): void
    {
        $profileName = $_POST['profile'] ?? 'Default';
        $_SESSION['current_profile'] = $profileName;
        $profile = $this->profileManager->load($profileName);
        $global = $this->profileManager->getGlobalConfig();

        $report = [
            'status' => 'error',
            'message' => 'Profile not found: ' . $profileName,
            'profile' => $profile ? $profile->getName() : 'Not Found',
        ];

        try {
            if ($profile) {
                $dsConfig = $profile->getDatasourceConfig();
                $selectedMode = $_POST['import_mode'] ?? ($dsConfig['mode'] ?? 'xcreate');
                $filePath = ImportFileLocator::resolveProfileFile($dsConfig, $global);
                $pluginManager = new PluginManager();

                $dbHelper = null;
                $dbConfig = DbConfigFactory::fromGlobalConfig($global);
                if ($dbConfig === null) {
                    throw new \RuntimeException('Database configuration is required for imports.');
                }
                $dbHelper = new DbHelper($dbConfig);
                $dbHelper->connect();

                // Register available plugins and enable based on profile config
                $pluginConfig = $profile->getPluginConfig();
                $this->registerBuiltinPlugins($pluginManager);
                foreach ($pluginConfig as $pluginId => $config) {
                    if ($config['enabled'] ?? false) {
                        $pluginManager->enable($pluginId);
                        $plugin = $pluginManager->getAll()[$pluginId];
                        PluginDiscovery::assertImplemented($plugin);
                        if ($plugin instanceof \Magmi\Core\Plugin\ConfigurablePluginInterface) {
                            $plugin->setConfig($config['config'] ?? []);
                        }
                    }
                }

                $magentoMediaDir = $global['magento_media_dir'] ?? null;
                if (!$magentoMediaDir && !empty($global['magento_path'])) {
                    $magentoMediaDir = rtrim($global['magento_path'], '/\\') . '/pub/media';
                }

                $importDir = $filePath ? dirname($filePath) : null;

                $engine = new ImportEngine($pluginManager, $dbHelper);
                $engine->setContext([
                    'profile' => $profileName,
                    'report_step' => $global['report_step'] ?? 0.5,
                    'magento_path' => $global['magento_path'] ?? null,
                    'magento_media_dir' => $magentoMediaDir,
                    'import_file' => $filePath,
                    'import_dir' => $importDir,
                    'debug' => isset($_POST['debug']) || isset($_POST['debug_log']),
                    'debug_log' => isset($_POST['debug_log']),
                    'debug_log_path' => $global['debug_log_path'] ?? null,
                    'enable_msi' => !empty($global['enable_msi']),
                    'mode' => $selectedMode,
                    'csv_value_separator' => $dsConfig['value_separator'] ?? ',',
                    'import_limit' => (int) ($_POST['import_limit'] ?? 0),
                ]);

                if ($filePath === null) {
                    $report = [
                        'status' => 'error',
                        'message' => 'No import file selected or found. Please choose a file.',
                    ];
                } else {
                    $_SESSION['last_import_file'] = $filePath;
                    $datasource = DatasourceFactory::open($filePath, $dsConfig);
                    try {
                        $engine->setDatasource($datasource);
                        $report = $engine->import();
                    } finally {
                        $datasource->close();
                    }
                    if (!isset($_POST['debug'])) {
                        $report['debug'] = [];
                    }
                }
            }

        } catch (\Throwable $error) {
            $report = ['status' => 'error', 'message' => $error->getMessage()];
        }

        $_SESSION['import_report'] = $report;
        $_SESSION['debug_checked'] = isset($_POST['debug']);
        $_SESSION['debug_log_checked'] = isset($_POST['debug_log']);
        $_SESSION['import_limit'] = $_POST['import_limit'] ?? '';
        header('Location: /import');
        exit;
    }

    public function preview(): void
    {
        $profileName = $_POST['profile'] ?? 'Default';
        $profile = $this->profileManager->load($profileName);
        $global = $this->profileManager->getGlobalConfig();

        $preview = [
            'status' => 'error',
            'profile' => $profileName,
            'message' => 'Profile not found.',
            'headers' => [],
            'rows' => [],
        ];

        if ($profile) {
            $dsConfig = $profile->getDatasourceConfig();
            $filePath = ImportFileLocator::resolveProfileFile($dsConfig, $global);

            if ($filePath === null || !file_exists($filePath)) {
                $preview = [
                    'status' => 'error',
                    'profile' => $profileName,
                    'message' => 'Import file not found: ' . ($dsConfig['file'] ?? 'undefined'),
                    'headers' => [],
                    'rows' => [],
                ];
            } else {
                try {
                    $rows = $this->readPreviewRows($filePath, $dsConfig);
                    $preview = [
                        'status' => 'success',
                        'profile' => $profileName,
                        'message' => 'First 5 rows from ' . basename($filePath),
                        'headers' => $rows[0] ?? [],
                        'rows' => array_slice($rows, 1, 5),
                    ];
                } catch (\Exception $e) {
                    $preview = [
                        'status' => 'error',
                        'profile' => $profileName,
                        'message' => 'Could not read file: ' . $e->getMessage(),
                        'headers' => [],
                        'rows' => [],
                    ];
                }
            }
        }

        $_SESSION['import_preview'] = $preview;
        header('Location: /import');
        exit;
    }

    private function readPreviewRows(string $filePath, array $dsConfig): array
    {
        $source = DatasourceFactory::open($filePath, $dsConfig);
        try {
            $rows = [$source->getColumnNames()];
            foreach ($source->getRows() as $row) {
                $rows[] = array_values($row);
                if (count($rows) === 6) {
                    break;
                }
            }
            return $rows;
        } finally {
            $source->close();
        }
    }

    private function registerBuiltinPlugins(PluginManager $manager): void
    {
        $discovery = new PluginDiscovery();
        $realPlugins = $discovery->scanAvailable($this->pluginDir);
        foreach ($realPlugins as $plugin) {
            $manager->register($plugin);
        }

        // Fallback to built-in stubs if no real plugins found
        if (empty($realPlugins)) {
            $builtin = [
                ['id' => 'categorycreator', 'name' => 'On the fly category creator/importer', 'version' => 'v0.2.5', 'cat' => 'itemprocessors'],
                ['id' => 'attributedelete', 'name' => 'Attribute Deleter', 'version' => 'v1.0', 'cat' => 'itemprocessors'],
                ['id' => 'downloadable', 'name' => 'Downloadable products importer', 'version' => 'v1.0.0.1', 'cat' => 'itemprocessors'],
                ['id' => 'groupprice', 'name' => 'Group Price Importer', 'version' => 'v0.0.4', 'cat' => 'itemprocessors'],
                ['id' => 'imageattr', 'name' => 'Image attributes processor', 'version' => 'v1.0.33a', 'cat' => 'itemprocessors'],
                ['id' => 'indexer', 'name' => 'On the fly indexer', 'version' => 'v0.2', 'cat' => 'itemprocessors'],
                ['id' => 'customoptions', 'name' => 'Custom Options', 'version' => 'v0.0.7a', 'cat' => 'itemprocessors'],
                ['id' => 'productdelete', 'name' => 'Product Deleter', 'version' => 'v0.0.2', 'cat' => 'itemprocessors'],
                ['id' => 'producttags', 'name' => 'Product Tags Importer', 'version' => 'v0.0.3', 'cat' => 'itemprocessors'],
                ['id' => 'tierprice', 'name' => 'Tier price importer', 'version' => 'v0.0.9a', 'cat' => 'itemprocessors'],
                ['id' => 'weeetax', 'name' => 'Weee Tax importer', 'version' => 'v0.0.5', 'cat' => 'itemprocessors'],
                ['id' => 'columnmapper', 'name' => 'Column mapper', 'version' => 'v0.0.3b', 'cat' => 'preprocessors'],
                ['id' => 'skufinder', 'name' => 'SKU Finder', 'version' => 'v0.0.3', 'cat' => 'preprocessors'],
                ['id' => 'defaultvalues', 'name' => 'Default Values setter', 'version' => 'v0.0.5', 'cat' => 'preprocessors'],
                ['id' => 'limiter', 'name' => 'Magmi Import Limiter', 'version' => 'v0.0.7', 'cat' => 'preprocessors'],
                ['id' => 'genericmapper', 'name' => 'Generic mapper', 'version' => 'v0.0.6a', 'cat' => 'preprocessors'],
                ['id' => 'valuereplacer', 'name' => 'Value Replacer', 'version' => 'v0.0.8a', 'cat' => 'preprocessors'],
                ['id' => 'valuetrimmer', 'name' => 'Value Trimmer for select/multiselect', 'version' => 'v0.0.3', 'cat' => 'preprocessors'],
                ['id' => 'groupeditem', 'name' => 'Grouped Item processor', 'version' => 'v1.4.1', 'cat' => 'producttypes'],
                ['id' => 'bundleitem', 'name' => 'Bundle Item processor', 'version' => 'v1.1', 'cat' => 'producttypes'],
                ['id' => 'configurableitem', 'name' => 'Configurable Item processor', 'version' => 'v1.3.7a', 'cat' => 'producttypes'],
                ['id' => 'crossupsell', 'name' => 'Cross/Upsell Importer', 'version' => 'v1.0.3', 'cat' => 'relatedproducts'],
                ['id' => 'productrelater', 'name' => 'Product relater', 'version' => 'v1.0.3', 'cat' => 'relatedproducts'],
            ];
            foreach ($builtin as $p) {
                $manager->register(new \Magmi\Gui\Plugin\StubPlugin(
                    $p['id'],
                    $p['name'],
                    $p['version'],
                    $p['cat']
                ));
            }
        }
    }

    private function getCurrentProfile(): \Magmi\Core\Config\Profile
    {
        $profileName = $_SESSION['current_profile'] ?? 'Default';
        return $this->profileManager->load($profileName) ?? new \Magmi\Core\Config\Profile($profileName);
    }
}
