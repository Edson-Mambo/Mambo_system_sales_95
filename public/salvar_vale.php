<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

$carrinho = $_SESSION['carrinho'] ?? [];
if (empty($carrinho)) {
    die('Carrinho está vazio.');
}

$cliente_id = $_POST['cliente_id'] ?: null;
$cliente_nome = trim($_POST['cliente_nome']);
$cliente_telefone = trim($_POST['cliente_telefone']);
$total_vale = floatval($_POST['total_vale']);
$status = isset($_POST['salvar_vale']) ? 'pendente' : 'finalizado';
$numero_vale = 'VALE-' . date('Ymd-His') . '-' . rand(100, 999);

// Insere vale
$stmt = $pdo->prepare("INSERT INTO vales (numero_vale, cliente_id, cliente_nome, cliente_telefone, total, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute([
    $numero_vale,
    $cliente_id,
    $cliente_nome,
    $cliente_telefone,
    $total_vale,
    $status
]);

$id_vale = $pdo->lastInsertId();

// Insere itens do vale
foreach ($carrinho as $codigo => $item) {
    $stmtItem = $pdo->prepare("INSERT INTO itens_vale (vale_id, codigo_produto, nome_produto, preco, quantidade) VALUES (?, ?, ?, ?, ?)");
    $stmtItem->execute([
        $id_vale,
        $codigo,
        $item['nome'],
        $item['preco'],
        $item['quantidade']
    ]);
}

// Limpa carrinho
unset($_SESSION['carrinho']);

// Redireciona para confirmação
header("Location: vale_confirmado.php?numero=$numero_vale");
exit;
