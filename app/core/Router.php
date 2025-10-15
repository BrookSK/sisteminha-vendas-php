<?php
namespace Core;

class Router
{
    private string $base;
    private array $routes = [];

    public function __construct(string $base = '/')
    {
        $this->base = rtrim($base, '/') ?: '/';
    }

    public function get(string $path, string $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function group(string $prefix, callable $callback, callable $guard = null, string $redirectIfFail = '/login'): void
    {
        $originalBase = $this->base;
        $this->base = rtrim($this->base . '/' . trim($prefix, '/'), '/') ?: '/';
        $callback($this);
        // armazena guard e redirect para todas rotas definidas dentro
        foreach ($this->routes as &$route) {
            if (!isset($route['guard']) && str_starts_with($route['path'], $this->base)) {
                $route['guard'] = $guard;
                $route['redirect'] = $redirectIfFail;
            }
        }
        $this->base = $originalBase;
    }

    private function map(string $method, string $path, string $handler): void
    {
        $fullPath = rtrim($this->base . '/' . ltrim($path, '/'), '/') ?: '/';
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // normaliza path
        $normalized = (rtrim($path, '/') ?: '/');
        $basePrefixed = (rtrim(($this->base === '/' ? '' : $this->base) . ($normalized === '/' ? '' : $normalized), '/') ?: '/');
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && ($route['path'] === $normalized || $route['path'] === $basePrefixed)) {
                // guarda opcional
                if (isset($route['guard']) && is_callable($route['guard'])) {
                    $ok = (bool)call_user_func($route['guard']);
                    if (!$ok) {
                        // Save next URL to return after login
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $q = parse_url($uri, PHP_URL_QUERY);
                            $p = parse_url($uri, PHP_URL_PATH) ?? '/';
                            $_SESSION['next_url'] = $p . ($q ? ('?' . $q) : '');
                        }
                        header('Location: ' . ($route['redirect'] ?? '/login'));
                        exit;
                    }
                }
                $this->invoke($route['handler']);
                return;
            }
        }
        http_response_code(404);
        // Try to render 404 view with layout
        $baseDir = dirname(__DIR__); // app/
        $view = $baseDir . '/views/errors/404.php';
        $layout = $baseDir . '/views/layouts/main.php';
        $title = 'Página não encontrada';
        $content = '';
        if (is_file($view)) {
            ob_start();
            include $view;
            $content = (string)ob_get_clean();
        } else {
            $content = '<div class="container py-4"><h3>404 Not Found</h3><p>A página solicitada não foi encontrada.</p></div>';
        }
        if (is_file($layout)) {
            include $layout;
        } else {
            echo $content;
        }
    }

    private function invoke(string $handler): void
    {
        if (!str_contains($handler, '@')) {
            throw new \RuntimeException('Invalid handler: ' . $handler);
        }
        [$class, $method] = explode('@', $handler, 2);
        if (!class_exists($class)) {
            throw new \RuntimeException('Controller not found: ' . $class);
        }
        $controller = new $class();
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException('Method not found: ' . $method);
        }
        $controller->$method();
    }
}
