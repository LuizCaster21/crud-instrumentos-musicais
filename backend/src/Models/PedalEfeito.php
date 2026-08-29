<?php

declare(strict_types=1);

namespace App\Models;

use Config\Database;
use PDO;

class PedalEfeito {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Lista todos os pedais de efeitos de um usuário
     */
    public function findAllByUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, marca, modelo, tipo_efeito, tecnologia, preco, quantidade_estoque, criado_em 
            FROM pedais_efeitos 
            WHERE usuario_id = :usuario_id 
            ORDER BY id DESC
        ");
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca um pedal específico pelo ID e usuário
     */
    public function findByIdAndUsuario(int $id, int $usuarioId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, marca, modelo, tipo_efeito, tecnologia, preco, quantidade_estoque, criado_em 
            FROM pedais_efeitos 
            WHERE id = :id AND usuario_id = :usuario_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        $pedal = $stmt->fetch();
        return $pedal ?: null;
    }

    /**
     * Cria um novo pedal de efeito
     */
    public function create(
        int $usuarioId,
        string $marca,
        string $modelo,
        string $tipoEfeito,
        ?string $tecnologia,
        float $preco,
        int $quantidadeEstoque
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO pedais_efeitos (usuario_id, marca, modelo, tipo_efeito, tecnologia, preco, quantidade_estoque) 
            VALUES (:usuario_id, :marca, :modelo, :tipo_efeito, :tecnologia, :preco, :quantidade_estoque) 
            RETURNING id
        ");
        $stmt->execute([
            'usuario_id'         => $usuarioId,
            'marca'              => trim($marca),
            'modelo'             => trim($modelo),
            'tipo_efeito'        => trim($tipoEfeito),
            'tecnologia'         => $tecnologia ? trim($tecnologia) : null,
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Atualiza um pedal existente
     */
    public function update(
        int $id,
        int $usuarioId,
        string $marca,
        string $modelo,
        string $tipoEfeito,
        ?string $tecnologia,
        float $preco,
        int $quantidadeEstoque
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE pedais_efeitos 
            SET marca = :marca, modelo = :modelo, tipo_efeito = :tipo_efeito, tecnologia = :tecnologia, preco = :preco, quantidade_estoque = :quantidade_estoque 
            WHERE id = :id AND usuario_id = :usuario_id
        ");
        return $stmt->execute([
            'id'                 => $id,
            'usuario_id'         => $usuarioId,
            'marca'              => trim($marca),
            'modelo'             => trim($modelo),
            'tipo_efeito'        => trim($tipoEfeito),
            'tecnologia'         => $tecnologia ? trim($tecnologia) : null,
            'preco'              => $preco,
            'quantidade_estoque' => $quantidadeEstoque
        ]);
    }

    /**
     * Remove um pedal
     */
    public function delete(int $id, int $usuarioId): bool {
        $stmt = $this->db->prepare("DELETE FROM pedais_efeitos WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->execute([
            'id'         => $id,
            'usuario_id' => $usuarioId
        ]);
        return $stmt->rowCount() > 0;
    }
}
