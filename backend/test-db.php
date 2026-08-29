<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Config\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    // Carrega o arquivo .env
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Tenta obter a conexão PDO
    $db = new Database();
    $conn = $db->getConnection();

    // Consulta simples para validar a versão do banco
    $stmt = $conn->query("SELECT version();");
    $version = $stmt->fetchColumn();

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Conexão com o PostgreSQL realizada com sucesso!",
        "versao_postgres" => $version
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Falha ao conectar:",
        "detalhe" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}