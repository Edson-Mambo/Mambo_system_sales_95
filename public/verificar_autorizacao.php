<?php
session_start();
require_once '../config/database.php';

$senha = $_POST['senha'] ?? '';

// ATENÇÃO: Substitua por uso de password_hash() em produção
$stmt = $pdo->prepare("SELECT nivel_acesso FROM usuarios WHERE senha = ? LIMIT 1");
$stmt->execute([$senha]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && in_array($usuario['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    echo json_encode(['autorizado' => true]);
} else {
    echo json_encode(['autorizado' => false]);
}