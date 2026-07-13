<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

/* =========================
   RESPOSTA
========================= */
function response(bool $success, string $message): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   UTILIZADOR LOGADO
========================= */
$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    response(false, 'Não autenticado');
}
/* =========================
   RECEBER JSON
========================= */
$raw = file_get_contents('php://input');

$data = json_decode($raw, true);

// DEBUG (remova depois de testar)
file_put_contents(
    __DIR__ . '/debug_autorizacao.log',
    date('Y-m-d H:i:s') . PHP_EOL .
    "RAW: " . $raw . PHP_EOL .
    "JSON: " . print_r($data, true) . PHP_EOL .
    str_repeat('-', 50) . PHP_EOL,
    FILE_APPEND
);

if (!is_array($data)) {
    response(false, 'Dados inválidos');
}

$senha = trim($data['senha'] ?? '');

if ($senha === '') {
    response(false, 'Senha obrigatória');
}
/* =========================
   BUSCAR UTILIZADOR
========================= */
$user = null;

/* Banco Local */
try {

    $pdo = Database::conectarLocal();

    $stmt = $pdo->prepare("
        SELECT id, senha, nivel
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$usuario_id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // ignora e tenta remoto
}

/* Banco Remoto */
if (!$user) {

    try {

        $pdo = Database::conectarRemoto();

        $stmt = $pdo->prepare("
            SELECT id, senha, nivel
            FROM usuarios
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$usuario_id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // ignora
    }
}

if (!$user) {
    response(false, 'Usuário inválido');
}

/* =========================
   PERMISSÕES
========================= */
$nivel = strtolower(trim($user['nivel'] ?? ''));

$permitidos = [
    'admin',
    'gerente',
    'supervisor'
];

if (!in_array($nivel, $permitidos, true)) {
    response(false, 'Sem permissão');
}

/* =========================
   VALIDAR SENHA
========================= */
if (!password_verify($senha, $user['senha'])) {
    response(false, 'Senha incorreta');
}

/* =========================
   SUCESSO
========================= */
response(true, 'Autorizado');