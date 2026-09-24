<?php
require getenv('MAGMI_TEST_AUTOLOAD') ?: (is_file(getcwd() . '/vendor/autoload.php') ? getcwd() . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php');
$base = sys_get_temp_dir() . '/magmi-gui-tests-' . bin2hex(random_bytes(6));
mkdir($base, 0700);
define('MAGMI_GUI_BASE_DIR', $base);
register_shutdown_function(static function () use ($base): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($base);
});
