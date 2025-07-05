<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
$pdo = Database::conectar();

// Dados vindos via POST normal
$senhaRecebida = $_POST['senha_autorizacao'] ?? '';

// Busca usuários com nível admin, gerente ou supervisor
$sql = "SELECT senha FROM usuarios WHERE nivel IN ('admin', 'gerente', 'supervisor')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$autorizado = false;

// Verifica a senha
foreach ($usuarios as $usuario) {
    if (password_verify($senhaRecebida, $usuario['senha'])) {
        $autorizado = true;
        break;
    }
}

// Responde SÓ isso!
echo json_encode(['autorizado' => $autorizado]);
exit;
