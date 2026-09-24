<?php

declare(strict_types=1);

namespace Magmi\Gui\App;

class View
{
    private string $templateDir;

    public function __construct(string $templateDir = __DIR__ . '/../View')
    {
        $this->templateDir = $templateDir;
    }

    public function render(string $template, array $data = []): void
    {
        extract($data);
        $templateFile = $this->templateDir . '/' . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$template}");
        }
        include $templateFile;
    }

    public function partial(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        $templateFile = $this->templateDir . '/partials/' . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Partial not found: {$template}");
        }
        include $templateFile;
        return ob_get_clean();
    }
}
