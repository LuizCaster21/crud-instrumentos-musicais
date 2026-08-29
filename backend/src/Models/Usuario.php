<?php

declare(strict_types=1);

namespace App\Models;

use Config\Database;
use PDO;

class Usuario {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Busca um usuário pelo e-mail
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT id, nome, email, senha, criado_em FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Busca um usuário pelo ID (sem retornar a senha)
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, nome, email, criado_em FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Cria um novo usuário com senha criptografada em BCRYPT
     */
    public function create(string $nome, string $email, string $senha): int {
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nome, email, senha) 
            VALUES (:nome, :email, :senha) 
            RETURNING id
        ");

        $stmt->execute([
            'nome'  => trim($nome),
            'email' => strtolower(trim($email)),
            'senha' => $senhaHash
        ]);

        return (int) $stmt->fetchColumn();
    }
}
