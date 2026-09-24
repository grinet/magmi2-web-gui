<?php

declare(strict_types=1);

namespace Magmi\Gui\Controller;

use Magmi\Core\Config\Profile;
use Magmi\Core\Config\ProfileManager;
use Magmi\Core\Plugin\PluginDiscovery;
use Magmi\Core\Plugin\PluginManager;
use Magmi\Gui\App\BasePath;
use Magmi\Gui\App\Theme;
use Magmi\Gui\App\View;
use Magmi\Gui\Support\ImportFileLocator;

class ProfileController
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
        $plugins = $this->getAvailablePlugins();
        $currentProfile = $this->getCurrentProfile();
        $savedAt = $this->getProfileSavedAt($currentProfile->getName());
        $global = $this->profileManager->getGlobalConfig();
        $fileData = ImportFileLocator::listFiles($global);
        $selectedFile = ImportFileLocator::resolveProfileFile($currentProfile->getDatasourceConfig(), $global);

        $this->view->render('profile', [
            'profiles' => $this->profileManager->list(),
            'currentProfile' => $currentProfile,
            'plugins' => $plugins,
            'savedAt' => $savedAt,
            'importFileOptions' => $fileData['options'],
            'selectedImportFile' => $selectedFile,
            'theme' => Theme::current(),
        ]);
    }

    public function save(): void
    {
        $profileName = $_POST['profile_name'] ?? 'Default';
        $profile = $this->profileManager->load($profileName) ?? new Profile($profileName);
        $global = $this->profileManager->getGlobalConfig();
        $basedir = trim($_POST['csv_basedir'] ?? 'var/import');

        $currentDsConfig = $profile->getDatasourceConfig();
        $previousFileValue = $currentDsConfig['file'] ?? '';
        $fileValue = $previousFileValue;

        $selectedValue = $_POST['datasource_file'] ?? '';
        $customFile = trim($_POST['datasource_file_custom'] ?? '');

        if ($customFile !== '' && $customFile !== $previousFileValue) {
            $fileValue = $customFile;
        } elseif ($selectedValue !== '') {
            $normalized = ImportFileLocator::normalizeSelection($selectedValue, $global);
            if ($normalized !== null) {
                $fileValue = ImportFileLocator::toProfileValue($normalized, $global, ['basedir' => $basedir]);
            } else {
                $fileValue = $selectedValue;
            }
        } elseif ($customFile === '' && $selectedValue === '') {
            $fileValue = '';
        }

        $profile->setDatasourceConfig([
            'type' => 'csv',
            'basedir' => $basedir,
            'file' => $fileValue,
            'mode' => $_POST['import_mode'] ?? ($currentDsConfig['mode'] ?? 'xcreate'),
            'separator' => $this->unescapeCsvControl($_POST['csv_separator'] ?? ','),
            'enclosure' => $this->unescapeCsvControl($_POST['csv_enclosure'] ?? '"'),
            'value_separator' => $_POST['csv_value_separator'] ?? ($currentDsConfig['value_separator'] ?? ','),
            'headless' => isset($_POST['csv_headless']),
            'allow_truncated_lines' => isset($_POST['csv_truncated']),
            'malformed' => isset($_POST['csv_malformed']),
        ]);

        $pluginConfig = [];
        foreach ($_POST['plugins'] ?? [] as $pluginId => $enabled) {
            $pluginConfig[$pluginId] = [
                'enabled' => true,
                'config' => $_POST['plugin_config'][$pluginId] ?? [],
            ];
        }
        $profile->setPluginConfig($pluginConfig);

        $this->profileManager->save($profile);

        header('Location: /profile');
        exit;
    }

    public function copy(): void
    {
        $from = $_POST['copy_from'] ?? '';
        $to = $_POST['copy_to'] ?? '';
        if ($from && $to) {
            $source = $this->profileManager->load($from) ?? new Profile($from);
            $new = Profile::fromArray($source->toArray());
            $ref = new \ReflectionClass($new);
            $prop = $ref->getProperty('name');
            $prop->setAccessible(true);
            $prop->setValue($new, $to);
            $this->profileManager->save($new);
        }
        header('Location: /profile');
        exit;
    }

    public function switch(): void
    {
        $profileName = $_POST['profile_name'] ?? 'Default';
        $_SESSION['current_profile'] = $profileName;
        header('Location: /profile');
        exit;
    }

    private function getCurrentProfile(): Profile
    {
        $sessionName = $_SESSION['current_profile'] ?? null;
        if ($sessionName !== null) {
            $profile = $this->profileManager->load($sessionName);
            if ($profile) {
                return $profile;
            }
            return new Profile($sessionName);
        }
        $profiles = $this->profileManager->list();
        return $profiles[0] ?? new Profile('Default');
    }

    private function getAvailablePlugins(): array
    {
        $manager = new PluginManager();

        // Scan for real plugin classes first
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

        $byCategory = [];
        foreach ($manager->getAll() as $id => $plugin) {
            $cat = $plugin->getCategory();
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [];
            }
            $byCategory[$cat][] = $plugin;
        }

        return $byCategory;
    }

    private function getProfileSavedAt(string $name): string
    {
        $file = $this->configDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '.json';
        return file_exists($file) ? date('c', filemtime($file)) : 'Not saved yet';
    }

    private function unescapeCsvControl(string $value): string
    {
        $replacements = [
            '\\t' => "\t",
            '\\n' => "\n",
            '\\r' => "\r",
            '\\0' => "\0",
        ];
        return strtr($value, $replacements);
    }
}
