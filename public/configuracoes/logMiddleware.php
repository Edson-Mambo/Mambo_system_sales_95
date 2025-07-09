<?php

require_once __DIR__ . '/../../config/database.php';

require_once 'log.php';

$pdo = Database::conectar();

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Visitante';
$rota = $_SERVER['REQUEST_URI'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Evita que o próprio registro do log registre infinitamente (ex: página de logs)
if (stripos($rota, 'relatorio_logs.php') === false) {
    registrarLog(
        $pdo,
        'INFO',
        "Acesso à página $rota",
        $usuario_id,
        $usuario_nome
    );
}
