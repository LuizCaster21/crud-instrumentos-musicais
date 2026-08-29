<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;
use Firebase\JWT\JWT;

class AuthController {
    private Usuario $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * POST /api/auth/register
     * Registra um novo usuário na aplicação
     */
    public function register(Request $request): void {
        $nome  = trim((string) $request->get('nome', ''));
        $email = trim((string) $request->get('email', ''));
        $senha = (string) $request->get('senha', '');

        // Validações de entrada
        $errors = [];
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        if (strlen($senha) < 6) {
            $errors['senha'] = 'A senha deve conter no mínimo 6 caracteres.';
        }

        if (!empty($errors)) {
            Response::error('Dados inválidos para cadastro.', 422, $errors);
        }

        // Verifica se o e-mail já está em uso
        $usuarioExistente = $this->usuarioModel->findByEmail($email);
        if ($usuarioExistente) {
            Response::error('Este e-mail já está cadastrado no sistema.', 409);
        }

        // Cria o usuário
        $novoUsuarioId = $this->usuarioModel->create($nome, $email, $senha);

        Response::success([
            'usuario' => [
                'id'    => $novoUsuarioId,
                'nome'  => $nome,
                'email' => strtolower($email)
            ]
        ], 'Usuário cadastrado com sucesso!', 201);
    }

    /**
     * POST /api/auth/login
     * Autentica o usuário e emite o token JWT
     */
    public function login(Request $request): void {
        $email = trim((string) $request->get('email', ''));
        $senha = (string) $request->get('senha', '');

        if (empty($email) || empty($senha)) {
            Response::error('E-mail e senha são obrigatórios.', 400);
        }

        $usuario = $this->usuarioModel->findByEmail($email);

        // Validação de senha usando hash seguro
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            Response::error('Credenciais inválidas. Verifique seu e-mail e senha.', 401);
        }

        // Configuração do Payload do JWT (Validade de 24 horas)
        $secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? 'fallback_secret_key';
        $issuedAt = time();
        $expire = $issuedAt + 86400; // 24 horas

        $payload = [
            'iat'   => $issuedAt,
            'exp'   => $expire,
            'sub'   => $usuario['id'],
            'email' => $usuario['email'],
            'nome'  => $usuario['nome']
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        Response::success([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'usuario'    => [
                'id'        => $usuario['id'],
                'nome'      => $usuario['nome'],
                'email'     => $usuario['email'],
                'criado_em' => $usuario['criado_em']
            ]
        ], 'Login realizado com sucesso!');
    }

    /**
     * GET /api/auth/me
     * Retorna os dados do usuário autenticado atual
     */
    public function me(Request $request): void {
        $usuarioId = $request->getUsuarioId();

        if (!$usuarioId) {
            Response::error('Usuário não autenticado.', 401);
        }

        $usuario = $this->usuarioModel->findById($usuarioId);

        if (!$usuario) {
            Response::error('Usuário não encontrado.', 404);
        }

        Response::success([
            'usuario' => $usuario
        ], 'Perfil recuperado com sucesso!');
    }
}
