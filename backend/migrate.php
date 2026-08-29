<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;

echo "\n=======================================================\n";
echo "       EXECUTANDO MIGRAÇÃO DO BANCO DE DADOS           \n";
echo "=======================================================\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Arquivo de schema não encontrado em: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);
    if ($sql === false || empty(trim($sql))) {
        throw new RuntimeException("Arquivo de schema está vazio ou não pôde ser lido.");
    }

    echo "Executando script schema.sql no PostgreSQL...\n";
    $conn->exec($sql);

    echo "\n  [\e[32mSUCESSO\e[0m] Tabelas e índices criados/atualizados com sucesso!\n";
    echo "=======================================================\n\n";
} catch (\Throwable $e) {
    echo "\n  [\e[31mERRO\e[0m] Falha ao executar migração: " . $e->getMessage() . "\n";
    echo "=======================================================\n\n";
    exit(1);
}
