<?php

declare(strict_types=1);

namespace App\Models;

use Config\Database;
use PDO;

class Instrumento {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Lista todos os instrumentos de um usuário específico
     */
    public function findAllByUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, nome, categoria, preco, quantidade_estoque, criado_em 
            FROM instrumentos 
            WHERE usuario_id = :usuario_id 
            ORDER BY id DESC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um instrumento específico pelo ID e usuário
     */
    public function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, nome, categoria, preco, quantidade_estoque, criado_em 
            FROM instrumentos 
            WHERE id = :id AND usuario_id = :usuario_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        $instrumento = $stmt->fetch();
        return $instrumento ?: null;
    }

    /**
     * Cria um novo instrumento
     */
    public function create(int $usuarioId, string $nome, string $categoria, float $preco, int $quantidadeEstoque): int {
        $stmt = $this->db->prepare("
            INSERT INTO instrumentos (usuario_id, nome, categoria, preco, quantidade_estoque) 
            VALUES (:usuario_id, :nome, :categoria, :preco, :quantidade_estoque) 
            RETURNING id
        ");
        $stmt->execute([
            'usuario_id'         => $usuarioId,
            'nome'               => trim($nome),
            'categoria'          => trim($categoria),
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Atualiza um instrumento existente
     */
    public function update(int $id, int $usuarioId, string $nome, string $categoria, float $preco, int $quantidadeEstoque): bool {
        $stmt = $this->db->prepare("
            UPDATE instrumentos 
            SET nome = :nome, categoria = :categoria, preco = :preco, quantidade_estoque = :quantidade_estoque 
            WHERE id = :id AND usuario_id = :usuario_id
        ");
        return $stmt->execute([
            'id'                 => $id,
            'usuario_id'         => $usuarioId,
            'nome'               => trim($nome),
            'categoria'          => trim($categoria),
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
    }

    /**
     * Remove um instrumento
     */
    public function delete(int $id, int $usuarioId): bool {
        $stmt = $this->db->prepare("DELETE FROM instrumentos WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        return $stmt->rowCount() > 0;
    }
}
