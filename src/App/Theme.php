<?php

declare(strict_types=1);

namespace Magmi\Gui\App;

use Magmi\Core\Config\ProfileManager;

class Theme
{
    public const CLASSIC = 'classic';
    public const MODERN_DARK = 'modern-dark';
    public const MODERN_LIGHT = 'modern-light';

    /** @var string[] */
    private const SUPPORTED = [
        self::CLASSIC,
        self::MODERN_DARK,
        self::MODERN_LIGHT,
    ];

    private static ?string $cached = null;

    public static function current(): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $configDir = BasePath::get() . '/data/config';
        $manager = new ProfileManager($configDir);
        $global = $manager->getGlobalConfig();
        $theme = $global['ui_theme'] ?? self::CLASSIC;

        return self::$cached = self::sanitize($theme);
    }

    public static function sanitize(?string $value): string
    {
        if ($value === null) {
            return self::CLASSIC;
        }
        return in_array($value, self::SUPPORTED, true) ? $value : self::CLASSIC;
    }

    public static function options(): array
    {
        return [
            self::CLASSIC => 'Light (default)',
            self::MODERN_DARK => 'Modern Dark',
            self::MODERN_LIGHT => 'Modern Light',
        ];
    }

    public static function clearCache(): void
    {
        self::$cached = null;
    }
}
