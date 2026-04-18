<?php

require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

$sku = $_GET['sku'] ?? '';

$stmt = $pdo->prepare("
    SELECT p.*, c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.codigo_barra = ?
");

$stmt->execute([$sku]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));