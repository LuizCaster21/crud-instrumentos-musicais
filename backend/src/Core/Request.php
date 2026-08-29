<?php

declare(strict_types=1);

namespace App\Core;

class Request {
    private string $method;
    private string $uri;
    private array $body;
    private array $queryParams;
    private array $params = [];
    private ?int $usuarioId = null;

    public function __construct() {
        // 1. Captura o método HTTP (GET, POST, PUT, DELETE, etc.)
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // 2. Limpa e padroniza a URI (remove barras sobressalentes e query strings)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->uri = '/' . trim($parsedUri, '/');

        // 3. Captura parâmetros de query (?chave=valor)
        $this->queryParams = $_GET;

        // 4. Decodifica o corpo JSON enviado pelo cliente
        $this->body = $this->parseBody();
    }

    /**
     * Lê o fluxo de entrada bruto da requisição e decodifica o JSON
     */
    private function parseBody(): array {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $decoded = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return $_POST ?? [];
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getBody(): array {
        return $this->body;
    }

    /**
     * Retorna um dado específico do corpo ou da query
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getQueryParams(): array {
        return $this->queryParams;
    }

    /**
     * Define parâmetros dinâmicos capturados pela rota (ex: ID na URL)
     */
    public function setParams(array $params): void {
        $this->params = $params;
    }

    public function getParam(string $key, mixed $default = null): mixed {
        return $this->params[$key] ?? $default;
    }

    /**
     * Extrai o Token JWT do cabeçalho HTTP Authorization
     */
    public function getBearerToken(): ?string {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if (!$authHeader && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if ($authHeader && preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Registra o ID do usuário identificado pelo token JWT
     */
    public function setUsuarioId(int $usuarioId): void {
        $this->usuarioId = $usuarioId;
    }

    public function getUsuarioId(): ?int {
        return $this->usuarioId;
    }
}
