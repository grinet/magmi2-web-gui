<?php

namespace Magmi\Gui\App;

final class Version
{
    private static array $cache = [];

    public static function webGui(): string
    {
        return self::format(self::readVersion(self::packageRoot()));
    }

    public static function rawWebGui(): string
    {
        return self::readVersion(self::packageRoot()) ?? 'dev-master';
    }

    private static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function readVersion(string $basePath): ?string
    {
        if (isset(self::$cache[$basePath])) {
            return self::$cache[$basePath];
        }

        if (class_exists(\Composer\InstalledVersions::class) && \Composer\InstalledVersions::isInstalled('grinet/magmi2-web-gui')) {
            $version = \Composer\InstalledVersions::getPrettyVersion('grinet/magmi2-web-gui');
            if ($version !== null) {
                return self::$cache[$basePath] = $version;
            }
        }

        $composerPath = rtrim($basePath, '/\\') . '/composer.json';
        if (!is_file($composerPath)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($composerPath), true);
        $version = is_array($json) ? ($json['version'] ?? null) : null;
        self::$cache[$basePath] = is_string($version) ? $version : null;

        return self::$cache[$basePath];
    }

    private static function format(?string $version): string
    {
        if ($version === null || $version === '') {
            return 'dev-master';
        }

        $trimmed = ltrim($version, 'vV');
        $parts = preg_split('/-+/', $trimmed) ?: [$trimmed];
        $base = $parts[0] ?? $trimmed;
        $suffix = '';
        if (count($parts) > 1) {
            $suffixParts = array_slice($parts, 1);
            $suffix = ' ' . implode(' ', array_map(static fn(string $p): string => ucfirst($p), $suffixParts));
        }

        return 'v' . $base . $suffix;
    }
}
