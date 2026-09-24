<?php

declare(strict_types=1);

namespace Magmi\Gui\App;

class BasePath
{
    public static function get(): string
    {
        if (defined('MAGMI_GUI_BASE_DIR')) {
            return MAGMI_GUI_BASE_DIR;
        }

        return dirname(__DIR__, 2);
    }
}
