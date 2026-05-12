<?php

$dbPath = __DIR__ . '/mambo_local.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);

    $pdo->exec("ALTER TABLE vendas_pendentes ADD COLUMN usuario_id INTEGER");
    $pdo->exec("ALTER TABLE vendas_pendentes ADD COLUMN caixa_id INTEGER");

    echo "Tabela atualizada com sucesso!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}