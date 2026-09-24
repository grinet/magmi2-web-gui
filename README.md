# Magmi Web GUI

Standalone web interface for Magento product imports. Installs [Magmi Core](https://github.com/grinet/magmi2-core) and CSV, TSV, XLS and XLSX support through Composer.

## Requirements

PHP 8.2+, Composer 2, and access to the Magento database and media directory. Composer checks the required PHP extensions. Tested with PHP 8.4 and Magento Open Source 2.4.9.

## Install

Run as the application owner in a new directory:

```sh
mkdir magmi-app
cd magmi-app
composer init --name=local/magmi-app --no-interaction

# Required until both packages are available on Packagist:
composer config repositories.magmi-core vcs https://github.com/grinet/magmi2-core
composer config repositories.magmi-gui vcs https://github.com/grinet/magmi2-web-gui

# The first release and its Core dependency are alpha versions:
composer config minimum-stability alpha
composer config prefer-stable true
composer require grinet/magmi2-web-gui:^0.1@alpha
vendor/bin/magmi-gui init
```

Set the web server document root to **magmi-app/public**. Give the PHP worker write access to `data`. Keep `vendor`, `plugins` and `data` outside the public directory.

Apache needs `mod_rewrite` and permission to use the generated `.htaccess`. With Nginx, route missing files to the front controller:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Keep your normal PHP-FPM handler and HTTPS configuration. For a local check, run `php -S 127.0.0.1:8080 -t public` and open that address.

## First import

1. Open `/login`. On a fresh installation, read the generated credentials from `data/emergency_password.txt` on the server.
2. Sign in and open Configuration. Set the Magento root path, or configure the database and media path separately. Test and save the connection. Once the database is available, sign in with a Magento administrator account.
3. Open a profile, select the source file and import mode, then save it.
4. Preview the file, run the import and check the report. Refresh the affected Magento indexes and cache before checking the storefront.

Start with a small file against a test store. The GUI writes to the configured Magento database.

## Update

```sh
composer update grinet/magmi2-web-gui grinet/magmi2-core --with-all-dependencies
vendor/bin/magmi-gui assets
```

Profiles and settings remain in `data`. Publish assets after each update; dependency Composer scripts do not run automatically. `init --base-dir=/absolute/path` creates the application elsewhere and preserves existing profiles. Rerun `init` after moving the installation so its autoloader path is refreshed.

## Development

```sh
git clone git@github.com:grinet/magmi2-web-gui.git
cd magmi2-web-gui
composer config repositories.magmi-core vcs https://github.com/grinet/magmi2-core
composer install
composer test
```

## Status

Alpha. Sixteen legacy plugin entries are not implemented and cannot be enabled for import. Supported Magento versions outside 2.4.9 have not been verified. The separate [Magento module](https://github.com/grinet/magmi2-magento-module) provides native Magento admin and CLI entry points.

License: [OSL-3.0](LICENSE).
