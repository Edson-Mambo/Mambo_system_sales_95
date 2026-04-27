<?php
session_start();
require_once "../config/database.php";
require_once "../middleware/auth.php";

requireRole(['admin','gerente','caixa']);

$pdo = Database::conectar();

$usuario_id = $_SESSION['usuario_id'];
$caixa_id = $_SESSION['caixa_id'] ?? null;
$abertura_id = $_SESSION['abertura_id'] ?? null;
$valor_informado = $_POST['valor_informado'] ?? 0;

if (!$caixa_id || !$abertura_id) {
    die("❌ Nenhuma sessão de caixa ativa.");
}

/*
🔵 1. BUSCAR RESUMO DAS VENDAS
*/
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as numero_vendas,
        SUM(total) as total_vendas,

        SUM(CASE WHEN metodo_pagamento='cash' THEN total ELSE 0 END) as cash,
        SUM(CASE WHEN metodo_pagamento='mpesa' THEN total ELSE 0 END) as mpesa,
        SUM(CASE WHEN metodo_pagamento='emola' THEN total ELSE 0 END) as emola,
        SUM(CASE WHEN metodo_pagamento='cartao' THEN total ELSE 0 END) as cartao

    FROM vendas
    WHERE abertura_id = ?
    AND status = 'concluida'
");

$stmt->execute([$abertura_id]);
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

/*
🟡 2. VALORES
*/
$total_vendas = $resumo['total_vendas'] ?? 0;
$total_cash = $resumo['cash'] ?? 0;

$diferenca = $valor_informado - $total_cash;

/*
🟣 3. REGISTAR FECHO
*/
$stmt = $pdo->prepare("
    INSERT INTO fechamento_caixa (
        abertura_id,
        numero_vendas,
        total_vendas,
        total_dinheiro,
        total_mpesa,
        total_emola,
        total_cartao,
        valor_informado_operador,
        valor_esperado_caixa,
        diferenca_caixa,
        data_fechamento
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
    )
");

$stmt->execute([
    $abertura_id,
    $resumo['numero_vendas'],
    $total_vendas,
    $total_cash,
    $resumo['mpesa'],
    $resumo['emola'],
    $resumo['cartao'],
    $valor_informado,
    $total_cash,
    $diferenca
]);

/*
🔴 4. FECHAR CAIXA
*/
$update = $pdo->prepare("
    UPDATE abertura_caixa
    SET status='fechado'
    WHERE id=?
");
$update->execute([$abertura_id]);

/*
⚫ 5. LIMPAR SESSÃO
*/
unset($_SESSION['caixa_id']);
unset($_SESSION['abertura_id']);

/*
🟢 6. REDIRECIONAR
*/
header("Location: ../public/fecho_sucesso.php");
exit;
?>