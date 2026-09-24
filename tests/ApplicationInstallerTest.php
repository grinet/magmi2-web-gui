<?php

declare(strict_types=1);
use Magmi\Gui\Installer\ApplicationInstaller;
use PHPUnit\Framework\TestCase;

final class ApplicationInstallerTest extends TestCase
{
    public function testInitializesApplicationAndPreservesUserSettings(): void
    {
        $base = MAGMI_GUI_BASE_DIR . '/install';
        $autoload = is_file(getcwd() . '/vendor/autoload.php') ? getcwd() . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        $installer = new ApplicationInstaller();
        $installer->initialize($base, $autoload);
        self::assertFileExists($base . '/public/css/style.css');
        self::assertFileExists($base . '/public/js/app.js');
        self::assertFileExists($base . '/data/config/Default.json');
        self::assertStringContainsString('MAGMI_GUI_BASE_DIR', file_get_contents($base . '/public/index.php'));
        file_put_contents($base . '/data/config/Default.json', '{"name":"Default","datasource":{"file":"keep.csv"}}');
        $installer->initialize($base, $autoload);
        self::assertStringContainsString('keep.csv', file_get_contents($base . '/data/config/Default.json'));
    }

    public function testRefusesToOverwriteAnotherApplication(): void
    {
        $base = MAGMI_GUI_BASE_DIR . '/existing';
        mkdir($base . '/public', 0755, true);
        file_put_contents($base . '/public/index.php', '<?php /* another application */');
        $this->expectException(RuntimeException::class);
        (new ApplicationInstaller())->initialize($base, __FILE__);
    }
}
