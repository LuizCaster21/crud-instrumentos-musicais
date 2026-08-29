<?php

namespace Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database {
    private string $host;
    private string $port;
    private string $dbName;
    private string $user;
    private string $pass;
    private ?PDO $conn = null;

    public function __construct() {
        // Carrega .env automaticamente caso ainda não tenha sido inicializado
        if (empty($_ENV['DB_HOST']) && empty($_SERVER['DB_HOST'])) {
            $envPath = dirname(__DIR__);
            if (file_exists($envPath . '/.env')) {
                $dotenv = Dotenv::createImmutable($envPath);
                $dotenv->safeLoad();
            }
        }

        $this->host   = $_ENV['DB_HOST']   ?? $_SERVER['DB_HOST']   ?? getenv('DB_HOST')   ?: 'localhost';
        $this->port   = $_ENV['DB_PORT']   ?? $_SERVER['DB_PORT']   ?? getenv('DB_PORT')   ?: '5432';
        $this->dbName = $_ENV['DB_NAME']   ?? $_SERVER['DB_NAME']   ?? getenv('DB_NAME')   ?: 'crud_instrumentos_musicais';
        $this->user   = $_ENV['DB_USER']   ?? $_SERVER['DB_USER']   ?? getenv('DB_USER')   ?: 'postgres';
        $this->pass   = $_ENV['DB_PASS']   ?? $_SERVER['DB_PASS']   ?? getenv('DB_PASS')   ?: '';
    }

    public function getConnection(): PDO {
        if ($this->conn === null) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbName};options='--client_encoding=UTF8'";
            
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);
        }
        return $this->conn;
    }
}