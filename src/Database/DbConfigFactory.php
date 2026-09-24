<?php

declare(strict_types=1);

namespace Magmi\Gui\Database;

use Magmi\Core\Database\DbConfig;

class DbConfigFactory
{
    public static function fromGlobalConfig(array $global): ?DbConfig
    {
        $mode = $global['db_connection_mode'] ?? 'manual';

        if ($mode === 'env_php' && !empty($global['magento_path']) && is_dir($global['magento_path'])) {
            $envPath = rtrim($global['magento_path'], '/\\') . '/app/etc/env.php';
            if (file_exists($envPath)) {
                return DbConfig::fromEnvPhp($global['magento_path']);
            }
        }

        if (!empty($global['db_host']) && !empty($global['db_name']) && !empty($global['db_user'])) {
            return DbConfig::fromArray([
                'host' => $global['db_host'],
                'port' => $global['db_port'] ?? 3306,
                'dbname' => $global['db_name'],
                'user' => $global['db_user'],
                'password' => $global['db_pass'] ?? '',
                'table_prefix' => $global['db_prefix'] ?? '',
            ]);
        }

        return null;
    }
}
