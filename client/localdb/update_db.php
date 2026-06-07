<?php

$dbPath = __DIR__ . '/mambo_local.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar colunas existentes
    $columns = [];
    $stmt = $pdo->query("PRAGMA table_info(vendas_pendentes)");
    foreach ($stmt as $row) {
        $columns[] = $row['name'];
    }

    // Função auxiliar
    function addColumnIfNotExists($pdo, $columns, $table, $column, $type) {
        if (!in_array($column, $columns)) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column $type");
            echo "Coluna '$column' adicionada com sucesso!<br>";
        } else {
            echo "Coluna '$column' já existe.<br>";
        }
    }

    addColumnIfNotExists($pdo, $columns, "vendas_pendentes", "usuario_id", "INTEGER");
    addColumnIfNotExists($pdo, $columns, "vendas_pendentes", "caixa_id", "INTEGER");

    echo "<br>Atualização concluída com sucesso!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}