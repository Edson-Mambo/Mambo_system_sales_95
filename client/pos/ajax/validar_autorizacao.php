<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

/* =========================
   RESPOSTA
========================= */
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
$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    response(false, 'Não autenticado');
}

/* =========================
   DADOS
========================= */
/*$data = json_decode(file_get_contents("php://input"), true);
$senha = trim($data['senha'] ?? '');

if ($senha === '') {
    response(false, 'Senha obrigatória');
}

/* =========================
   BUSCAR USUÁRIO NA DB
========================= */
$pdo = Database::conectarLocal();

$stmt = $pdo->prepare("SELECT id, senha, nivel FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    response(false, 'Usuário inválido');
}
$pdo = Database::conectarRemoto();

$stmt = $pdo->prepare("SELECT id, senha, nivel FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    response(false, 'Usuário inválido');
}

$nivel = strtolower(trim($user['nivel']));
$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos, true)) {
    response(false, 'Sem permissão');
}

if (!password_verify($senha, $user['senha'])) {
    response(false, 'Senha incorreta');
}

response(true, 'Autorizado');