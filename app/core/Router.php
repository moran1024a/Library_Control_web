<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->convertPattern($pattern),
            'original' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri)
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = array_values(array_filter(
                    $matches,
                    static fn($key) => is_int($key),
                    ARRAY_FILTER_USE_KEY
                ));

                return call_user_func_array($route['handler'], $params);
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => '接口不存在']);
        return null;
    }

    private function convertPattern(string $pattern): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
