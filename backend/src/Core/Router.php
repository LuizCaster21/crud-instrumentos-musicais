<?php

declare(strict_types=1);

namespace App\Core;

class Router {
    private array $routes = [];

    /**
     * Registra uma rota GET
     */
    public function get(string $path, callable|array $handler, array $middlewares = []): self {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota POST
     */
    public function post(string $path, callable|array $handler, array $middlewares = []): self {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota PUT
     */
    public function put(string $path, callable|array $handler, array $middlewares = []): self {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota DELETE
     */
    public function delete(string $path, callable|array $handler, array $middlewares = []): self {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota genérica
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): self {
        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => '/' . trim($path, '/'),
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
        return $this;
    }

    /**
     * Processa a requisição e executa a rota correspondente
     */
    public function dispatch(Request $request): void {
        $requestMethod = $request->getMethod();
        $requestUri = $request->getUri();
        $routeMatched = false;

        foreach ($this->routes as $route) {
            // Converte parâmetros nomeados como {id} para grupos nomeados de Regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $requestUri, $matches)) {
                $routeMatched = true;

                // Se o caminho bateu mas o método HTTP for diferente, continua procurando
                if ($route['method'] !== $requestMethod) {
                    continue;
                }

                // Filtra apenas as chaves nomeadas dos parâmetros
                $params = array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // 1. Executa os Middlewares vinculados à rota
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = is_string($middleware) ? new $middleware() : $middleware;
                    if (method_exists($middlewareInstance, 'handle')) {
                        $middlewareInstance->handle($request);
                    }
                }

                // 2. Executa o Handler (Controller ou Função anônima)
                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$controllerClass, $action] = $handler;
                    $controller = new $controllerClass();
                    $controller->$action($request);
                    return;
                }

                if (is_callable($handler)) {
                    $handler($request);
                    return;
                }
            }
        }

        // Se encontrou a rota mas com método não permitido
        if ($routeMatched) {
            Response::error("Método HTTP {$requestMethod} não permitido para a rota {$requestUri}.", 405);
        }

        // Se a rota não existe
        Response::error("Rota {$requestUri} não encontrada.", 404);
    }
}
