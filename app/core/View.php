<?php
namespace Core;

class View
{
    public function render(string $template, array $data = [], string $layout = 'layouts/main'): string
    {
        $viewsPath = dirname(__DIR__) . '/views/';
        $templateFile = $viewsPath . $template . '.php';
        $layoutFile = $viewsPath . $layout . '.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException('View não encontrada: ' . $templateFile);
        }
        // extrai dados
        extract($data);
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        if (file_exists($layoutFile)) {
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }
        return $content;
    }
}
