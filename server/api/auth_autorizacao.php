<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

error_reporting(0);
ini_set('display_errors', 0');

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

try {

    /* =========================
       RECEBER JSON
    ========================= */
    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input)) {
        response(false, 'JSON inválido');
    }

    $senha = trim($input['senha'] ?? '');

    if ($senha === '') {
        response(false, 'Senha obrigatória');
    }

    $usuarios = [];

    /* =========================
       BANCO LOCAL
    ========================= */
    try {

        $pdo = Database::conectarLocal();

        $stmt = $pdo->prepare("
            SELECT id, senha, nivel
            FROM usuarios
            WHERE nivel IN ('admin','gerente','supervisor')
        ");

        $stmt->execute();

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // ignora e tenta remoto
    }

    /* =========================
       BANCO REMOTO
    ========================= */
    if (empty($usuarios)) {

        try {

            $pdo = Database::conectarRemoto();

            $stmt = $pdo->prepare("
                SELECT id, senha, nivel
                FROM usuarios
                WHERE nivel IN ('admin','gerente','supervisor')
            ");

            $stmt->execute();

            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            response(false, 'Não foi possível conectar ao banco de dados');
        }
    }

    /* =========================
       VALIDAR SENHA
    ========================= */
    foreach ($usuarios as $usuario) {

        if (password_verify($senha, $usuario['senha'])) {

            response(true, 'Autorizado');
        }
    }

    response(false, 'Senha incorreta');

} catch (Throwable $e) {

    response(false, 'Erro interno: ' . $e->getMessage());
}