<?php

declare(strict_types=1);

namespace Magmi\Gui\Support;

use Magmi\Gui\App\BasePath;

class ImportFileLocator
{
    private const PATTERN_SUFFIX = '.{csv,CSV,tsv,TSV,xls,XLS,xlsx,XLSX}';

    /**
     * @return array{sources: array<int, array{label: string, path: string, files: array<int, array{path: string, name: string}>}>, options: array<int, array{value: string, label: string}>}
     */
    public static function listFiles(array $globalConfig): array
    {
        $sources = [];
        $options = [];

        foreach (self::getSourceDefinitions($globalConfig) as $source) {
            [$label, $dir] = $source;
            $files = [];
            $found = [];
            if (is_dir($dir)) {
                foreach (glob(rtrim($dir, '\\/') . '/*' . self::PATTERN_SUFFIX, GLOB_BRACE) ?: [] as $file) {
                    if (is_file($file)) {
                        $found[$file] = true;
                    }
                }
            }

            if (!empty($found)) {
                $paths = array_keys($found);
                sort($paths, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($paths as $path) {
                    $real = realpath($path) ?: $path;
                    $sizeBytes = @filesize($real);
                    $sizeBytes = $sizeBytes !== false ? (int) $sizeBytes : 0;
                    $sizeLabel = self::formatSize($sizeBytes);
                    $files[] = [
                        'path' => $real,
                        'name' => basename($real),
                        'size_bytes' => $sizeBytes,
                        'size_label' => $sizeLabel,
                    ];
                    $options[] = [
                        'value' => $real,
                        'label' => sprintf('%s — %s (Size: %s)', $label, basename($real), $sizeLabel),
                        'size_label' => $sizeLabel,
                        'size_bytes' => $sizeBytes,
                    ];
                }
            }

            $sources[] = [
                'label' => $label,
                'path' => $dir,
                'files' => $files,
            ];
        }

        return [
            'sources' => $sources,
            'options' => $options,
        ];
    }

    public static function resolveProfileFile(array $dsConfig, array $global): ?string
    {
        $file = $dsConfig['file'] ?? '';
        if ($file === '') {
            return null;
        }

        if (str_starts_with($file, '/')) {
            return file_exists($file) ? (realpath($file) ?: $file) : null;
        }

        $magentoPath = trim((string) ($global['magento_path'] ?? ''));
        if ($magentoPath !== '') {
            $baseDir = rtrim($magentoPath, '/\\') . '/' . trim($dsConfig['basedir'] ?? 'var/import', '/\\');
            $fullPath = $baseDir . '/' . $file;
            if (file_exists($fullPath)) {
                return realpath($fullPath) ?: $fullPath;
            }

            return null;
        }

        $dataPath = BasePath::get() . '/data/' . $file;
        if (file_exists($dataPath)) {
            return realpath($dataPath) ?: $dataPath;
        }

        return null;
    }

    public static function normalizeSelection(?string $selection, array $global): ?string
    {
        if ($selection === null) {
            return null;
        }
        $selection = trim($selection);
        if ($selection === '' || !file_exists($selection)) {
            return null;
        }

        $real = realpath($selection);
        if ($real === false) {
            return null;
        }

        foreach (self::getAllowedImportDirectories($global) as $dir) {
            $dirReal = realpath($dir);
            if ($dirReal && str_starts_with($real, $dirReal . DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }

    public static function toProfileValue(string $absolutePath, array $global, array $dsConfig): string
    {
        $real = realpath($absolutePath) ?: $absolutePath;

        $magentoPath = trim((string) ($global['magento_path'] ?? ''));
        if ($magentoPath !== '') {
            $baseDir = rtrim($magentoPath, '/\\') . '/' . trim($dsConfig['basedir'] ?? 'var/import', '/\\');
            $baseReal = realpath($baseDir) ?: $baseDir;
            if ($baseReal && str_starts_with($real, rtrim($baseReal, '/\\') . DIRECTORY_SEPARATOR)) {
                return ltrim(substr($real, strlen(rtrim($baseReal, '/\\')) + 1), DIRECTORY_SEPARATOR);
            }

            return $real;
        }

        $dataDir = realpath(BasePath::get() . '/data');
        if ($dataDir && str_starts_with($real, $dataDir . DIRECTORY_SEPARATOR)) {
            return ltrim(substr($real, strlen($dataDir) + 1), DIRECTORY_SEPARATOR);
        }

        return $real;
    }

    /**
     * @return string[]
     */
    public static function getAllowedImportDirectories(array $global): array
    {
        $magentoPath = trim((string) ($global['magento_path'] ?? ''));
        if ($magentoPath !== '') {
            return [rtrim($magentoPath, '/\\') . '/var/import'];
        }

        return [BasePath::get() . '/data'];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private static function getSourceDefinitions(array $global): array
    {
        $sources = [];
        $magentoPath = trim((string) ($global['magento_path'] ?? ''));

        if ($magentoPath === '') {
            $sources[] = ['Magmi data', BasePath::get() . '/data'];
            $sources[] = ['Magento var/import', '(magento path not set)'];
        } else {
            $sources[] = ['Magento var/import', rtrim($magentoPath, '/\\') . '/var/import'];
        }

        return $sources;
    }

    private static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return sprintf('%.3f MB', $bytes / 1048576);
        }
        if ($bytes >= 1024) {
            return sprintf('%.3f KB', $bytes / 1024);
        }
        return sprintf('%d B', $bytes);
    }
}
