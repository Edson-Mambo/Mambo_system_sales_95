<?php
require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nome, codigo_barra FROM produtos WHERE nome LIKE ? OR codigo_barra LIKE ? ORDER BY nome LIMIT 20");
$stmt->execute(["%$q%", "%$q%"]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($produtos);
