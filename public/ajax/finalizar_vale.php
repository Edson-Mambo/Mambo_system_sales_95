<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$pdo = Database::conectar();

$numero_vale = intval($_POST['numero_vale'] ?? 0);

if (!$numero_vale) {
    echo json_encode(['success' => false, 'mensagem' => 'Vale inválido']);
    exit;
}

// Atualiza status para finalizado
$stmt = $pdo->prepare("UPDATE vales SET status = 'finalizado' WHERE id = ?");
$stmt->execute([$numero_vale]);

echo json_encode(['success' => true]);
