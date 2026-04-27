<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

$usuario_logado = $_SESSION['usuario_id'];
$valor_inicial = $_POST['valor_inicial'] ?? 0;

if (!$usuario_logado) {
    die("Acesso negado");
}

/* já existe caixa aberto? */
$stmt = $pdo->prepare("
    SELECT id 
    FROM abertura_caixa 
    WHERE usuario_id = ? AND status = 'aberto'
    LIMIT 1
");

$stmt->execute([$usuario_logado]);
$abertura = $stmt->fetch(PDO::FETCH_ASSOC);

/* se já existe */
if ($abertura) {

    $_SESSION['abertura_id'] = $abertura['id'];

    header("Location: /Mambo_system_sales_95/public/venda.php");
    exit;
}

/* abrir novo caixa */
$stmt = $pdo->prepare("
    INSERT INTO abertura_caixa
    (usuario_id, valor_inicial, status)
    VALUES (?, ?, 'aberto')
");

$stmt->execute([$usuario_logado, $valor_inicial]);

$_SESSION['abertura_id'] = $pdo->lastInsertId();

/* 🔥 NÃO TOCA EM usuario_id */
header("Location: /Mambo_system_sales_95/public/venda.php");
exit;