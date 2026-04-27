<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/empresa.php';
require_once __DIR__ . '/../services/ResumoCaixaImpressaoService.php';

$pdo = Database::conectar();

$usuario_id = $_SESSION['usuario_id'] ?? null;
$abertura_id = $_SESSION['abertura_id'] ?? null;

/* =========================
   IMPRIMIR RESUMO
========================= */
if ($usuario_id && $abertura_id) {

    try {
        $config = getConfigEmpresa($pdo);

        ResumoCaixaImpressaoService::imprimir(
            $abertura_id,
            $usuario_id,
            $pdo,
            $config
        );

    } catch (Throwable $e) {
        error_log("ERRO RESUMO LOGOUT: " . $e->getMessage());
    }
}

/* =========================
   FECHAR ABERTURA CAIXA
========================= */
if ($abertura_id) {

    try {
        $stmt = $pdo->prepare("
            UPDATE abertura_caixa
            SET status = 'fechado'
            WHERE id = ?
        ");
        $stmt->execute([$abertura_id]);

    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

/* =========================
   REGISTAR LOGOUT
========================= */
if ($usuario_id) {

    try {
        $stmt = $pdo->prepare("
            UPDATE logs_login 
            SET logout_time = NOW() 
            WHERE usuario_id = ? 
            ORDER BY login_time DESC 
            LIMIT 1
        ");
        $stmt->execute([$usuario_id]);

    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

/* =========================
   LIMPAR SESSÃO
========================= */
$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;