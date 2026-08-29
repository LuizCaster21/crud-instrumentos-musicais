<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    private string $host;
    private string $port;
    private string $dbName;
    private string $user;
    private string $pass;
    private ?PDO $conn = null;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->port = $_ENV['DB_PORT'] ?? '5432';
        $this->dbName = $_ENV['DB_NAME'] ?? 'crud_instrumentos_musicais';
        $this->user = $_ENV['DB_USER'] ?? 'postgres';
        $this->pass = $_ENV['DB_PASS'] ?? '';
    }

    public function getConnection(): ?PDO {
        if ($this->conn === null) {
            try {
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbName}";
                $this->conn = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Falha na conexão com o banco de dados: " . $e->getMessage()]);
                exit;
            }
        }
        return $this->conn;
    }
}