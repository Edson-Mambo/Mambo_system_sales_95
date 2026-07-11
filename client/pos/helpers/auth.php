<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

function response($success, $message)
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

/* =========================
   LOGIN
========================= */
if (empty($_SESSION['usuario_id'])) {
    response(false, 'Não autenticado');
}

/* =========================
   DADOS
========================= */
$data = json_decode(file_get_contents("php://input"), true);

$senha = trim($data['senha'] ?? '');

if ($senha === '') {
    response(false, 'Senha obrigatória');
}

/* =========================
   PERMISSÃO
========================= */
$nivel = strtolower(trim((string)($_SESSION['nivel'] ?? '')));

$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos, true)) {
    response(false, 'Sem permissão');
}

/* =========================
   SENHA (TESTE)
========================= */
$senha_correta = '1234';

if (!hash_equals($senha_correta, $senha)) {
    response(false, 'Senha incorreta');
}

/* =========================
   OK
========================= */
response(true, 'Autorizado');