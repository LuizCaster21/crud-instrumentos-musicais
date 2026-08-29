<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Throwable;

class AuthMiddleware {
    /**
     * Intercepta a requisição e valida o token JWT
     */
    public function handle(Request $request): void {
        $token = $request->getBearerToken();

        if (!$token) {
            Response::error('Acesso não autorizado: Token de autenticação não fornecido.', 401);
        }

        try {
            $secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? 'fallback_secret_key';
            
            // Decodifica o token usando o algoritmo HS256
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Verifica se o ID do usuário está presente no subject (sub) do token
            if (empty($decoded->sub)) {
                Response::error('Token inválido: Identificador de usuário ausente.', 401);
            }

            // Injeta o ID do usuário no objeto Request para uso nos Controllers
            $request->setUsuarioId((int) $decoded->sub);

        } catch (ExpiredException $e) {
            Response::error('Token expirado. Por favor, faça login novamente.', 401);
        } catch (SignatureInvalidException $e) {
            Response::error('Token com assinatura inválida.', 401);
        } catch (Throwable $e) {
            Response::error('Token inválido ou corrompido: ' . $e->getMessage(), 401);
        }
    }
}
