<?php

declare(strict_types=1);

namespace App\Core;

class Response {
    /**
     * Envia uma resposta JSON customizada
     */
    public static function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Resposta padronizada de sucesso
     */
    public static function success(mixed $data = null, string $message = 'Sucesso', int $statusCode = 200): void {
        self::json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ], $statusCode);
    }

    /**
     * Resposta padronizada de erro
     */
    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void {
        $payload = [
            'status'  => 'error',
            'message' => $message
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $statusCode);
    }
}
