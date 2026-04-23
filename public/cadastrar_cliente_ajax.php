<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$pdo = Database::conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$nome = trim($_POST['nome_cliente'] ?? '');
$apelido = trim($_POST['apelido_cliente'] ?? '');
$telefone = trim($_POST['telefone_cliente'] ?? '');
$telefone_alt = trim($_POST['telefone_alt_cliente'] ?? '');
$email = trim($_POST['email_cliente'] ?? '');
$morada = trim($_POST['morada_cliente'] ?? '');

if ($nome === '' || $telefone === '') {
    echo json_encode(['success' => false, 'message' => 'Nome e telefone obrigatórios']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO clientes (nome, apelido, telefone, telefone_alt, email, morada)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([$nome, $apelido, $telefone, $telefone_alt, $email, $morada]);

$id = $pdo->lastInsertId();

/* selecionar automaticamente */
$_SESSION['cliente_id'] = $id;
$_SESSION['cliente_nome'] = $nome . ' ' . $apelido;

echo json_encode([
    'success' => true,
    'cliente' => [
        'id' => $id,
        'nome' => $nome . ' ' . $apelido
    ]
]);