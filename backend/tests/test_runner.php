<?php

/**
 * Script de Testes Automatizados da Aplicação
 * Executa verificações de ambiente, conexão ao PostgreSQL, tabelas do banco e JWT.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Database;

class TestRunner {
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;

    private function logSuccess(string $message): void {
        $this->totalTests++;
        $this->passedTests++;
        echo "  [\e[32mPASS\e[0m] $message\n";
    }

    private function logFailure(string $message, ?string $detail = null): void {
        $this->totalTests++;
        $this->failedTests++;
        echo "  [\e[31mFAIL\e[0m] $message\n";
        if ($detail) {
            echo "         \e[33mDetalhe: $detail\e[0m\n";
        }
    }

    private function logInfo(string $message): void {
        echo "  [\e[34mINFO\e[0m] $message\n";
    }

    public function run(): void {
        echo "\n=======================================================\n";
        echo "   INICIANDO SUÍTE DE TESTES E DIAGNÓSTICO DO PROJETO   \n";
        echo "=======================================================\n\n";

        $this->testEnvironment();
        $this->testDatabaseConnection();
        $this->testDatabaseTables();
        $this->testJwtEncodingAndDecoding();

        echo "\n-------------------------------------------------------\n";
        echo "Resultado: {$this->passedTests}/{$this->totalTests} testes aprovados.";
        if ($this->failedTests > 0) {
            echo " (\e[31m{$this->failedTests} falha(s)\e[0m)\n";
        } else {
            echo " (\e[32mTodos os testes passaram com sucesso!\e[0m)\n";
        }
        echo "=======================================================\n\n";

        if ($this->failedTests > 0) {
            exit(1);
        }
    }

    private function testEnvironment(): void {
        echo "[1] Testando Configurações de Ambiente (.env):\n";

        $envPath = dirname(__DIR__);
        if (!file_exists($envPath . '/.env')) {
            $this->logFailure("Arquivo .env não encontrado em $envPath");
            return;
        }
        $this->logSuccess("Arquivo .env localizado.");

        try {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
            $this->logSuccess("Variáveis do .env carregadas com sucesso via Dotenv.");
        } catch (\Throwable $e) {
            $this->logFailure("Erro ao carregar .env", $e->getMessage());
            return;
        }

        $requiredKeys = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (empty($_ENV[$key]) && empty($_SERVER[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (empty($missingKeys)) {
            $this->logSuccess("Todas as variáveis essenciais estão definidas (" . implode(', ', $requiredKeys) . ").");
        } else {
            $this->logFailure("Variáveis ausentes no .env: " . implode(', ', $missingKeys));
        }

        echo "\n";
    }

    private function testDatabaseConnection(): void {
        echo "[2] Testando Conexão com o PostgreSQL:\n";

        try {
            $db = new Database();
            $conn = $db->getConnection();
            $this->logSuccess("Instância do PDO criada com sucesso.");

            $stmt = $conn->query("SELECT version();");
            $version = $stmt->fetchColumn();
            $this->logSuccess("Conexão estabelecida com o PostgreSQL!");
            $this->logInfo("Versão do BD: " . substr((string)$version, 0, 50) . "...");
        } catch (\PDOException $e) {
            $this->logFailure("Falha ao conectar com o banco de dados PostgreSQL.", $e->getMessage());
        } catch (\Throwable $e) {
            $this->logFailure("Erro inesperado ao conectar ao banco.", $e->getMessage());
        }

        echo "\n";
    }

    private function testDatabaseTables(): void {
        echo "[3] Testando Estrutura de Tabelas no Banco de Dados:\n";

        try {
            $db = new Database();
            $conn = $db->getConnection();

            $expectedTables = ['usuarios', 'instrumentos', 'amplificadores', 'pedais_efeitos'];
            
            $query = "
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                  AND table_name = ANY(:tables)
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute(['tables' => '{' . implode(',', $expectedTables) . '}']);
            $foundTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($expectedTables as $table) {
                if (in_array($table, $foundTables, true)) {
                    $this->logSuccess("Tabela '$table' encontrada no banco.");
                } else {
                    $this->logFailure("Tabela '$table' NÃO encontrada.", "Execute o script 'database/schema.sql' no seu banco PostgreSQL.");
                }
            }

        } catch (\Throwable $e) {
            $this->logFailure("Erro ao inspecionar tabelas do banco.", $e->getMessage());
        }

        echo "\n";
    }

    private function testJwtEncodingAndDecoding(): void {
        echo "[4] Testando Autenticação e Criptografia JWT:\n";

        try {
            $secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? 'fallback_test_secret';
            $issuedAt = time();
            $expire = $issuedAt + 3600;

            $payload = [
                'iat' => $issuedAt,
                'exp' => $expire,
                'sub' => 123,
                'email' => 'dev@devcaster.com',
                'nome' => 'Dev Caster'
            ];

            $jwt = JWT::encode($payload, $secret, 'HS256');
            $this->logSuccess("Token JWT gerado com sucesso com algoritmo HS256.");

            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            
            if ($decoded->sub === 123 && $decoded->email === 'dev@devcaster.com') {
                $this->logSuccess("Token JWT decodificado e validado com sucesso.");
            } else {
                $this->logFailure("Dados decodificados do token JWT não coincidem com o payload original.");
            }

        } catch (\Throwable $e) {
            $this->logFailure("Falha no teste de JWT.", $e->getMessage());
        }

        echo "\n";
    }
}

$runner = new TestRunner();
$runner->run();
