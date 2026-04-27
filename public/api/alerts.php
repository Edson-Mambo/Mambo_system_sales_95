<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

/* =========================
   SEGURANÇA
========================= */
if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

/* =========================
   ALERTAS DO SISTEMA
========================= */
$alerts = [];

/* 🔴 STOCK BAIXO */
$stmt = $pdo->query("
    SELECT id, nome, estoque 
    FROM produtos 
    WHERE estoque <= 5
    LIMIT 10
");

$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($lowStock) {
    $alerts[] = [
        "type" => "warning",
        "title" => "Stock baixo",
        "count" => count($lowStock),
        "items" => $lowStock
    ];
}

/* 🔴 VENDAS HOJE */
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM vendas 
    WHERE DATE(data_venda) = CURDATE()
");

$vendasHoje = $stmt->fetchColumn();

$alerts[] = [
    "type" => "info",
    "title" => "Vendas hoje",
    "count" => $vendasHoje
];

/* 🔴 LOGINS RECENTES SUSPEITOS */
$stmt = $pdo->query("
    SELECT usuario_id, ip_address, created_at
    FROM logs_login
    ORDER BY created_at DESC
    LIMIT 5
");

$alerts[] = [
    "type" => "security",
    "title" => "Logins recentes",
    "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)
];

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "success" => true,
    "alerts" => $alerts,
    "timestamp" => date('Y-m-d H:i:s')
]);