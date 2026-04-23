<?php

require_once __DIR__ . '/database.php';

function getConfigEmpresa(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    return $config ?: [];
}