<?php
// salvar_cliente_ajax.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
// resto do código...



require_once '../config/database.php';
$pdo = Database::conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

// Receber dados do POST (ajuste nomes conforme seu formulário)
$nome         = trim($_POST['nome_cliente'] ?? '');
$apelido      = trim($_POST['apelido_cliente'] ?? '');
$telefone     = trim($_POST['telefone_cliente'] ?? '');
$telefone_alt = trim($_POST['telefone_alt_cliente'] ?? '');
$email        = trim($_POST['email_cliente'] ?? '');
$morada       = trim($_POST['morada_cliente'] ?? '');

if (empty($nome) || empty($telefone)) {
    echo json_encode(['success' => false, 'mensagem' => 'Nome e telefone são obrigatórios.']);
    exit;
}

// Verificar se já existe cliente com mesmo telefone
$stmt_check = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
$stmt_check->execute([$telefone]);
if ($stmt_check->fetch()) {
    echo json_encode(['success' => false, 'mensagem' => 'Cliente com esse telefone já existe.']);
    exit;
}

// Inserir cliente
$stmt_insert = $pdo->prepare("INSERT INTO clientes (nome, apelido, telefone, telefone_alt, email, morada) VALUES (?, ?, ?, ?, ?, ?)");
$sucesso = $stmt_insert->execute([$nome, $apelido, $telefone, $telefone_alt, $email, $morada]);

if ($sucesso) {
    echo json_encode([
        'success' => true,
        'cliente' => [
            'id' => $pdo->lastInsertId(),
            'nome' => $nome,
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao salvar cliente.']);
}
