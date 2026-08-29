<?php

declare(strict_types=1);

namespace App\Models;

use Config\Database;
use PDO;

class Amplificador {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Lista todos os amplificadores de um usuário
     */
    public function findAllByUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, marca, modelo, tipo, potencia_watts, preco, quantidade_estoque, criado_em 
            FROM amplificadores 
            WHERE usuario_id = :usuario_id 
            ORDER BY id DESC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um amplificador específico pelo ID e usuário
     */
    public function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, marca, modelo, tipo, potencia_watts, preco, quantidade_estoque, criado_em 
            FROM amplificadores 
            WHERE id = :id AND usuario_id = :usuario_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        $amplificador = $stmt->fetch();
        return $amplificador ?: null;
    }

    /**
     * Cria um novo amplificador
     */
    public function create(
        int $usuarioId,
        string $marca,
        string $modelo,
        string $tipo,
        int $potenciaWatts,
        float $preco,
        int $quantidadeEstoque
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO amplificadores (usuario_id, marca, modelo, tipo, potencia_watts, preco, quantidade_estoque) 
            VALUES (:usuario_id, :marca, :modelo, :tipo, :potencia_watts, :preco, :quantidade_estoque) 
            RETURNING id
        ");
        $stmt->execute([
            'usuario_id'         => $usuarioId,
            'marca'              => trim($marca),
            'modelo'             => trim($modelo),
            'tipo'               => trim($tipo),
            'potencia_watts'     => $potenciaWatts,
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Atualiza um amplificador existente
     */
    public function update(
        int $id,
        int $usuarioId,
        string $marca,
        string $modelo,
        string $tipo,
        int $potenciaWatts,
        float $preco,
        int $quantidadeEstoque
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE amplificadores 
            SET marca = :marca, modelo = :modelo, tipo = :tipo, potencia_watts = :potencia_watts, preco = :preco, quantidade_estoque = :quantidade_estoque 
            WHERE id = :id AND usuario_id = :usuario_id
        ");
        return $stmt->execute([
            'id'                 => $id,
            'usuario_id'         => $usuarioId,
            'marca'              => trim($marca),
            'modelo'             => trim($modelo),
            'tipo'               => trim($tipo),
            'potencia_watts'     => $potenciaWatts,
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
    }

    /**
     * Remove um amplificador
     */
    public function delete(int $id, int $usuarioId): bool {
        $stmt = $this->db->prepare("DELETE FROM amplificadores WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        return $stmt->rowCount() > 0;
    }
}
