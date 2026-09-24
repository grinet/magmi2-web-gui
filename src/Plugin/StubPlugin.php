<?php

declare(strict_types=1);

namespace Magmi\Gui\Plugin;

use Magmi\Core\Plugin\AbstractPlugin;

class StubPlugin extends AbstractPlugin
{
    public function __construct(string $id, string $name, string $version, string $category)
    {
        parent::__construct($id, $name, $version, $category);
    }
}
