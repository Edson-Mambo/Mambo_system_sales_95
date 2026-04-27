<?php

function hasPermission($pdo, $nivel, $modulo, $acao = 'pode_ver')
{
    $stmt = $pdo->prepare("
        SELECT $acao
        FROM permissoes
        WHERE nivel = ? AND modulo = ?
        LIMIT 1
    ");

    $stmt->execute([$nivel, $modulo]);
    $perm = $stmt->fetchColumn();

    return (int)$perm === 1;
}