<?php
// src/Controller/dados_dashboard.php
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Database::conectar();

    // Total de vendas
    $stmtVendas = $pdo->query("SELECT COUNT(*) AS total FROM vendas");
    $totalVendas = $stmtVendas->fetch()['total'] ?? 0;

    // Receita total (soma do campo valor_total nas vendas)
    $stmtReceita = $pdo->query("SELECT SUM(valor_total) AS total FROM vendas");
    $receitaTotal = $stmtReceita->fetch()['total'] ?? 0;

    // Total de usuários
    $stmtUsuarios = $pdo->query("SELECT COUNT(*) AS total FROM usuarios");
    $totalUsuarios = $stmtUsuarios->fetch()['total'] ?? 0;

    echo json_encode([
        'total_vendas' => (int)$totalVendas,
        'receita_total' => (float)$receitaTotal,
        'total_usuarios' => (int)$totalUsuarios
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
