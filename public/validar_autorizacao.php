<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
$pdo = Database::conectar();

$data = json_decode(file_get_contents('php://input'), true);

$senhaRecebida = $data['senha'] ?? '';
$codigoProduto = $data['codigo'] ?? '';

// Busca usuários com nível admin, gerente e supervisor
$usuariosPermitidos = ['admin', 'gerente', 'supervisor'];

$sql = "SELECT email, senha, nivel FROM usuarios WHERE nivel IN ('admin', 'gerente', 'supervisor')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$autorizado = false;

// Verifica se alguma senha bate
foreach ($usuarios as $usuario) {
    if (password_verify($senhaRecebida, $usuario['senha'])) {
        $autorizado = true;
        break;
    }
}

// Retorna resultado
echo json_encode(['autorizado' => $autorizado]);
