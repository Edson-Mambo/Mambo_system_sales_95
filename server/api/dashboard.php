<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

$pdo = Database::conectar();

$total_vendas = $pdo->query("SELECT SUM(total) FROM vendas")->fetchColumn();

$total_produtos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();

$stock_total = $pdo->query("SELECT SUM(estoque) FROM produtos")->fetchColumn();

$caixa_hoje = $pdo->query("
    SELECT SUM(total_vendas) 
    FROM caixa_fechos 
    WHERE DATE(data_fecho) = DATE('now')
")->fetchColumn();

echo json_encode([
    "total_vendas" => $total_vendas,
    "total_produtos" => $total_produtos,
    "stock_total" => $stock_total,
    "caixa_hoje" => $caixa_hoje
]);

$vendas_hoje = $pdo->query("
SELECT COUNT(*) FROM vendas WHERE DATE(data) = CURDATE()
")->fetchColumn();