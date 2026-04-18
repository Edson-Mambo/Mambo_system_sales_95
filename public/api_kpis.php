<?php

require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

header('Content-Type: application/json');

try {

    /* =========================
       💰 VENDAS HOJE
    ========================= */
    $vendasHoje = $pdo->query("
        SELECT IFNULL(SUM(total),0)
        FROM vendas
        WHERE DATE(data_venda) = CURDATE()
    ")->fetchColumn();

    $vendasHoje += $pdo->query("
        SELECT IFNULL(SUM(total),0)
        FROM vendas_takeaway
        WHERE DATE(data_venda) = CURDATE()
    ")->fetchColumn();

    /* =========================
       💳 FATURAÇÃO TOTAL
    ========================= */
    $faturacaoTotal = $pdo->query("
        SELECT IFNULL(SUM(total),0) FROM vendas
    ")->fetchColumn();

    $faturacaoTotal += $pdo->query("
        SELECT IFNULL(SUM(total),0) FROM vendas_takeaway
    ")->fetchColumn();

    /* =========================
       📦 PRODUTOS
    ========================= */
    $totalProdutos = $pdo->query("
        SELECT (SELECT COUNT(*) FROM produtos)
             + (SELECT COUNT(*) FROM produtos_takeaway)
    ")->fetchColumn();

    /* =========================
       ⚠️ STOCK BAIXO (CORRIGIDO)
    ========================= */
    $stockBaixo = $pdo->query("
        SELECT COUNT(*)
        FROM produtos
        WHERE estoque <= 5
    ")->fetchColumn();

    /* =========================
       👤 UTILIZADORES
    ========================= */
    $totalUsuarios = $pdo->query("
        SELECT COUNT(*) FROM usuarios
    ")->fetchColumn();

    echo json_encode([
        "vendasHoje" => $vendasHoje,
        "faturacaoTotal" => $faturacaoTotal,
        "totalProdutos" => $totalProdutos,
        "stockBaixo" => $stockBaixo,
        "totalUsuarios" => $totalUsuarios
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}