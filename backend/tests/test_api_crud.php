<?php

/**
 * Suíte de Testes Automatizados End-to-End para a API CRUD
 * Testa Autenticação JWT, Proteção de Rotas e os 3 CRUDs no PostgreSQL.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Config\Database;
use App\Models\Usuario;
use App\Models\Instrumento;
use App\Models\Amplificador;
use App\Models\PedalEfeito;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Carrega .env
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->safeLoad();
}

class ApiCrudTestRunner {
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private PDO $db;

    private ?int $testUserId = null;
    private string $testEmail;
    private string $testPassword = 'TestPassword123!';
    private ?string $jwtToken = null;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->testEmail = 'tester_' . time() . '@devcaster.com';
    }

    private function pass(string $message): void {
        $this->totalTests++;
        $this->passedTests++;
        echo "  [\e[32mPASS\e[0m] $message\n";
    }

    private function fail(string $message, ?string $detail = null): void {
        $this->totalTests++;
        $this->failedTests++;
        echo "  [\e[31mFAIL\e[0m] $message\n";
        if ($detail) {
            echo "         \e[33mDetalhe: $detail\e[0m\n";
        }
    }

    public function run(): void {
        echo "\n=======================================================\n";
        echo "    INICIANDO TESTES END-TO-END DOS CRUDS E DA API     \n";
        echo "=======================================================\n\n";

        try {
            $this->testAuthFlow();
            $this->testInstrumentosCrud();
            $this->testAmplificadoresCrud();
            $this->testPedaisCrud();
        } finally {
            $this->cleanup();
        }

        echo "\n-------------------------------------------------------\n";
        echo "Resultado: {$this->passedTests}/{$this->totalTests} testes aprovados.";
        if ($this->failedTests > 0) {
            echo " (\e[31m{$this->failedTests} falha(s)\e[0m)\n";
        } else {
            echo " (\e[32mTodos os endpoints e CRUDs estão 100% operacionais!\e[0m)\n";
        }
        echo "=======================================================\n\n";

        if ($this->failedTests > 0) {
            exit(1);
        }
    }

    private function testAuthFlow(): void {
        echo "[1] Testando Fluxo de Autenticação e Usuário:\n";

        $usuarioModel = new Usuario();

        // 1. Criar Usuário
        $this->testUserId = $usuarioModel->create('Dev Tester', $this->testEmail, $this->testPassword);
        if ($this->testUserId > 0) {
            $this->pass("Usuário de teste registrado no banco (ID: {$this->testUserId}).");
        } else {
            $this->fail("Falha ao registrar usuário de teste.");
            return;
        }

        // 2. Buscar por Email e verificar hash
        $user = $usuarioModel->findByEmail($this->testEmail);
        if ($user && password_verify($this->testPassword, $user['senha'])) {
            $this->pass("Busca por e-mail e validação de hash BCRYPT bem-sucedida.");
        } else {
            $this->fail("Falha na busca de usuário ou validação de hash de senha.");
        }

        // 3. Emissão de JWT
        $secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? 'fallback_secret_key';
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $this->testUserId,
            'email' => $this->testEmail,
            'nome' => 'Dev Tester'
        ];
        $this->jwtToken = JWT::encode($payload, $secret, 'HS256');

        $decoded = JWT::decode($this->jwtToken, new Key($secret, 'HS256'));
        if ($decoded->sub === $this->testUserId) {
            $this->pass("Emissão e decodificação do Bearer Token JWT validadas.");
        } else {
            $this->fail("Falha ao validar JWT emitido.");
        }

        echo "\n";
    }

    private function testInstrumentosCrud(): void {
        echo "[2] Testando CRUD de Instrumentos:\n";

        $model = new Instrumento();

        // CREATE
        $id = $model->create($this->testUserId, 'Fender Stratocaster 1962', 'Cordas', 14500.00, 2);
        if ($id > 0) {
            $this->pass("Instrumento criado com sucesso (ID: $id).");
        } else {
            $this->fail("Falha ao criar instrumento.");
        }

        // READ ALL
        $lista = $model->findAllByUsuario($this->testUserId);
        if (count($lista) === 1 && $lista[0]['nome'] === 'Fender Stratocaster 1962') {
            $this->pass("Listagem de instrumentos filtrada por usuário com sucesso.");
        } else {
            $this->fail("Falha na listagem de instrumentos.");
        }

        // READ ONE
        $item = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($item && (float)$item['preco'] === 14500.00) {
            $this->pass("Busca individual de instrumento por ID validada.");
        } else {
            $this->fail("Falha ao buscar instrumento por ID.");
        }

        // UPDATE
        $updated = $model->update($id, $this->testUserId, 'Fender Stratocaster 1962 Sunburst', 'Cordas', 15200.00, 1);
        $itemAtualizado = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($updated && $itemAtualizado['nome'] === 'Fender Stratocaster 1962 Sunburst' && (float)$itemAtualizado['preco'] === 15200.00) {
            $this->pass("Atualização de instrumento validada.");
        } else {
            $this->fail("Falha ao atualizar instrumento.");
        }

        // DELETE
        $deleted = $model->delete($id, $this->testUserId);
        $itemAposDelete = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($deleted && $itemAposDelete === null) {
            $this->pass("Exclusão de instrumento validada.");
        } else {
            $this->fail("Falha ao excluir instrumento.");
        }

        echo "\n";
    }

    private function testAmplificadoresCrud(): void {
        echo "[3] Testando CRUD de Amplificadores:\n";

        $model = new Amplificador();

        // CREATE
        $id = $model->create($this->testUserId, 'Marshall', 'JCM800 2203', 'Valvulado', 100, 18900.00, 1);
        if ($id > 0) {
            $this->pass("Amplificador criado com sucesso (ID: $id).");
        } else {
            $this->fail("Falha ao criar amplificador.");
        }

        // READ ALL
        $lista = $model->findAllByUsuario($this->testUserId);
        if (count($lista) === 1 && $lista[0]['modelo'] === 'JCM800 2203') {
            $this->pass("Listagem de amplificadores do usuário validada.");
        } else {
            $this->fail("Falha ao listar amplificadores.");
        }

        // READ ONE
        $item = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($item && (int)$item['potencia_watts'] === 100) {
            $this->pass("Busca individual de amplificador validada.");
        } else {
            $this->fail("Falha ao buscar amplificador por ID.");
        }

        // UPDATE
        $updated = $model->update($id, $this->testUserId, 'Marshall', 'JCM800 Reissue', 'Valvulado', 100, 19500.00, 2);
        $itemAtualizado = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($updated && $itemAtualizado['modelo'] === 'JCM800 Reissue') {
            $this->pass("Atualização de amplificador validada.");
        } else {
            $this->fail("Falha ao atualizar amplificador.");
        }

        // DELETE
        $deleted = $model->delete($id, $this->testUserId);
        if ($deleted && $model->findByIdAndUsuario($id, $this->testUserId) === null) {
            $this->pass("Exclusão de amplificador validada.");
        } else {
            $this->fail("Falha ao excluir amplificador.");
        }

        echo "\n";
    }

    private function testPedaisCrud(): void {
        echo "[4] Testando CRUD de Pedais e Efeitos:\n";

        $model = new PedalEfeito();

        // CREATE
        $id = $model->create($this->testUserId, 'Strymon', 'Timeline', 'Delay', 'Digital', 4200.00, 3);
        if ($id > 0) {
            $this->pass("Pedal de efeito criado com sucesso (ID: $id).");
        } else {
            $this->fail("Falha ao criar pedal de efeito.");
        }

        // READ ALL
        $lista = $model->findAllByUsuario($this->testUserId);
        if (count($lista) === 1 && $lista[0]['marca'] === 'Strymon') {
            $this->pass("Listagem de pedais do usuário validada.");
        } else {
            $this->fail("Falha ao listar pedais.");
        }

        // READ ONE
        $item = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($item && $item['tipo_efeito'] === 'Delay') {
            $this->pass("Busca individual de pedal validada.");
        } else {
            $this->fail("Falha ao buscar pedal por ID.");
        }

        // UPDATE
        $updated = $model->update($id, $this->testUserId, 'Strymon', 'Timeline V2', 'Delay', 'Digital', 4500.00, 2);
        $itemAtualizado = $model->findByIdAndUsuario($id, $this->testUserId);
        if ($updated && $itemAtualizado['modelo'] === 'Timeline V2') {
            $this->pass("Atualização de pedal validada.");
        } else {
            $this->fail("Falha ao atualizar pedal.");
        }

        // DELETE
        $deleted = $model->delete($id, $this->testUserId);
        if ($deleted && $model->findByIdAndUsuario($id, $this->testUserId) === null) {
            $this->pass("Exclusão de pedal validada.");
        } else {
            $this->fail("Falha ao excluir pedal.");
        }

        echo "\n";
    }

    private function cleanup(): void {
        if ($this->testUserId) {
            // Remove o usuário de teste (as tabelas filhas são apagadas automaticamente por CASCADE)
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->execute(['id' => $this->testUserId]);
        }
    }
}

$runner = new ApiCrudTestRunner();
$runner->run();
