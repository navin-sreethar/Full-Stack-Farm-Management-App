<?php
/**
 * URL Router
 * Matches incoming request against route definitions and dispatches to controller
 */

class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path prefix for subfolder deployment
        $basePath = App::baseUrl();
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
            if ($uri === '' || $uri === false) {
                $uri = '/';
            }
        }

        // Strip index.php for PATH_INFO routing (no .htaccess)
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, strlen('/index.php'));
            if ($uri === '' || $uri === false) {
                $uri = '/';
            }
        }

        // Remove trailing slash (except root)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            [$routeMethod, $routeUri, $controllerName, $action, $authRequired, $allowedRoles] = $route;

            if ($method !== $routeMethod) {
                continue;
            }

            $params = $this->matchUri($routeUri, $uri);
            if ($params === false) {
                continue;
            }

            // Auth check
            if ($authRequired && !Auth::check()) {
                Session::flash('error', 'Please log in to continue.');
                header('Location: ' . url('/login'));
                exit;
            }

            // Role check
            if (!empty($allowedRoles) && Auth::check()) {
                $userRole = Auth::user()['role'] ?? '';
                if (!in_array($userRole, $allowedRoles)) {
                    http_response_code(403);
                    echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
                    exit;
                }
            }

            // CSRF check on POST
            if ($method === 'POST') {
                CSRF::validate();
            }

            // Load and call controller
            $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
            if (!file_exists($controllerFile)) {
                http_response_code(500);
                echo "Controller not found: {$controllerName}";
                exit;
            }
            require_once $controllerFile;
            $controller = new $controllerName();
            call_user_func_array([$controller, $action], $params);
            return;
        }

        // No route found
        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }

    private function matchUri(string $routeUri, string $requestUri): array|false
    {
        // Convert route params {id} to regex groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routeUri);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            // Return only named params
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }
            return $params;
        }
        return false;
    }
}
