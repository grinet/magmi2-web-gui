<?php

declare(strict_types=1);
namespace Magmi\Gui\Controller;

use Magmi\Core\Config\ProfileManager;
use Magmi\Gui\App\BasePath;
use Magmi\Gui\App\Theme;
use Magmi\Gui\App\View;
use Magmi\Gui\Support\ImportFileLocator;

class HomeController
{
    public function index(): void
    {
        $manager = new ProfileManager(BasePath::get() . '/data/config');
        $global = $manager->getGlobalConfig();
        $profiles = $manager->list();
        $profileRows = [];
        foreach ($profiles as $profile) {
            $config = $profile->getDatasourceConfig();
            $file = ImportFileLocator::resolveProfileFile($config, $global);
            $profileRows[] = [
                'name' => $profile->getName(),
                'file' => $config['file'] ?? '',
                'available' => $file !== null && is_readable($file),
                'mode' => $config['mode'] ?? 'xcreate',
                'plugins' => count(array_filter($profile->getPluginConfig(), static fn($plugin) => !empty($plugin['enabled']))),
            ];
        }
        (new View())->render('home', [
            'theme' => Theme::current(),
            'profileRows' => $profileRows,
            'fileCount' => count(ImportFileLocator::listFiles($global)['options']),
            'configured' => !empty($global['magento_path']) || (!empty($global['db_host']) && !empty($global['db_name'])),
        ]);
    }
}
