<?php

declare(strict_types=1);

// 1. Configuração de CORS (Cross-Origin Resource Sharing)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Se o navegador enviar uma requisição de pré-checagem OPTIONS, encerra com sucesso 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Carregamento do Autoload do Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use Config\Database;
use App\Controllers\AuthController;
use App\Controllers\InstrumentoController;
use App\Controllers\AmplificadorController;
use App\Controllers\PedalEfeitoController;
use App\Middlewares\AuthMiddleware;

// 3. Carregamento das Variáveis de Ambiente (.env)
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->safeLoad();
}

try {
    // 4. Inicialização do Request e Router
    $request = new Request();
    $router  = new Router();

    // -------------------------------------------------------------
    // ROTA DE SAÚDE DA API (Health Check)
    // -------------------------------------------------------------
    $router->get('/api/status', function(Request $req) {
        $db = new Database();
        $conn = $db->getConnection();
        
        Response::success([
            'api_name'    => 'API CRUD Instrumentos Musicais',
            'version'     => '1.0.0',
            'db_status'   => 'Conectado ao PostgreSQL com sucesso',
            'server_time' => date('Y-m-d H:i:s')
        ], 'API operando normalmente');
    });

    // -------------------------------------------------------------
    // ROTAS DE AUTENTICAÇÃO
    // -------------------------------------------------------------
    $router->post('/api/auth/register', [AuthController::class, 'register']);
    $router->post('/api/auth/login',    [AuthController::class, 'login']);
    $router->get('/api/auth/me',        [AuthController::class, 'me'], [AuthMiddleware::class]);

    // -------------------------------------------------------------
    // CRUD DE INSTRUMENTOS (Rotas Protegidas)
    // -------------------------------------------------------------
    $router->get('/api/instrumentos',         [InstrumentoController::class, 'index'],   [AuthMiddleware::class]);
    $router->get('/api/instrumentos/{id}',    [InstrumentoController::class, 'show'],    [AuthMiddleware::class]);
    $router->post('/api/instrumentos',        [InstrumentoController::class, 'store'],   [AuthMiddleware::class]);
    $router->put('/api/instrumentos/{id}',     [InstrumentoController::class, 'update'],  [AuthMiddleware::class]);
    $router->delete('/api/instrumentos/{id}',  [InstrumentoController::class, 'destroy'], [AuthMiddleware::class]);

    // -------------------------------------------------------------
    // CRUD DE AMPLIFICADORES (Rotas Protegidas)
    // -------------------------------------------------------------
    $router->get('/api/amplificadores',        [AmplificadorController::class, 'index'],   [AuthMiddleware::class]);
    $router->get('/api/amplificadores/{id}',   [AmplificadorController::class, 'show'],    [AuthMiddleware::class]);
    $router->post('/api/amplificadores',       [AmplificadorController::class, 'store'],   [AuthMiddleware::class]);
    $router->put('/api/amplificadores/{id}',    [AmplificadorController::class, 'update'],  [AuthMiddleware::class]);
    $router->delete('/api/amplificadores/{id}', [AmplificadorController::class, 'destroy'], [AuthMiddleware::class]);

    // -------------------------------------------------------------
    // CRUD DE PEDAIS E EFEITOS (Rotas Protegidas)
    // -------------------------------------------------------------
    $router->get('/api/pedais',                [PedalEfeitoController::class, 'index'],   [AuthMiddleware::class]);
    $router->get('/api/pedais/{id}',           [PedalEfeitoController::class, 'show'],    [AuthMiddleware::class]);
    $router->post('/api/pedais',               [PedalEfeitoController::class, 'store'],   [AuthMiddleware::class]);
    $router->put('/api/pedais/{id}',           [PedalEfeitoController::class, 'update'],  [AuthMiddleware::class]);
    $router->delete('/api/pedais/{id}',        [PedalEfeitoController::class, 'destroy'], [AuthMiddleware::class]);

    // 5. Despacha a requisição para a rota correspondente
    $router->dispatch($request);

} catch (\Throwable $e) {
    Response::error(
        'Erro interno no servidor: ' . $e->getMessage(),
        500
    );
}
