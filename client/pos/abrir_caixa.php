<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

/* =========================
   AUTENTICAÇÃO
========================= */

if (empty($_SESSION['usuario_id'])) {
    header("Location: /Mambo_system_sales_95/client/auth/login.php");
    exit;
}

$nivel = strtolower(trim($_SESSION['nivel'] ?? ''));

$permissoes = [
    'admin',
    'administrador',
    'gerente',
    'supervisor',
    'caixa'
];

if (!in_array($nivel, $permissoes)) {
    header("Location: /Mambo_system_sales_95/client/auth/login.php?erro=acesso");
    exit;
}

$pdo = Database::conectar();

$usuario_id = $_SESSION['usuario_id'];
$valor_inicial = (float)($_POST['valor_inicial'] ?? 0);

/* =========================
   VERIFICAR CAIXA JÁ ABERTO
========================= */

$stmt = $pdo->prepare("
    SELECT id
    FROM abertura_caixa
    WHERE usuario_id = ?
      AND status = 'aberto'
    LIMIT 1
");

$stmt->execute([$usuario_id]);
$abertura = $stmt->fetch(PDO::FETCH_ASSOC);

if ($abertura) {

    $_SESSION['abertura_id'] = $abertura['id'];

    header("Location: /Mambo_system_sales_95/client/pos/index.php");
    exit;
}

/* =========================
   ABRIR NOVO CAIXA
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        INSERT INTO abertura_caixa
        (usuario_id, valor_inicial, status)
        VALUES (?, ?, 'aberto')
    ");

    $stmt->execute([
        $usuario_id,
        $valor_inicial
    ]);

    $_SESSION['abertura_id'] = $pdo->lastInsertId();

    header("Location: /Mambo_system_sales_95/client/pos/index.php");
    exit;
}