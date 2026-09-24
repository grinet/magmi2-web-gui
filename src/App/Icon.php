<?php

declare(strict_types=1);
namespace Magmi\Gui\App;

final class Icon
{
    public static function render(string $name): string
    {
        $paths = [
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
            'layers' => '<path d="m12 3 10 5-10 5L2 8l10-5Zm-9 9 9 5 9-5M3 17l9 5 9-5"/>',
            'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/>',
            'settings' => '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="15" cy="17" r="3"/>',
            'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
            'file' => '<path d="M14 2H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V8l-6-6Zm0 0v6h6M8 13h8M8 17h5"/>',
            'check' => '<path d="m5 12 4 4L19 6"/>',
            'logout' => '<path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/>',
            'moon' => '<path d="M20 15a9 9 0 0 1-11-11A9 9 0 1 0 20 15Z"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>',
            'box' => '<path d="m12 2 9 5v10l-9 5-9-5V7l9-5Zm-9 5 9 5 9-5m-9 5v10M7 4.8l10 5.5"/>',
            'chevron' => '<path d="m9 5 7 7-7 7"/>',
        ];
        return '<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
    }
}
