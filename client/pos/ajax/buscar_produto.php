<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

$pdo = Database::conectar();

$termo = trim($_GET['termo'] ?? '');

if (!$termo) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, codigo_barra, preco, estoque
    FROM produtos
    WHERE codigo_barra = ?
       OR nome LIKE ?
    LIMIT 20
");

$stmt->execute([
    $termo,
    "%{$termo}%"
]);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($produtos);