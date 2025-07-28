<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$pdo = Database::conectar();

$cliente_id = intval($_POST['cliente_id'] ?? 0);
$status = $_POST['status'] ?? 'aberto';
$numero_vale = intval($_POST['numero_vale'] ?? 0);
$carrinho = $_SESSION['carrinho'] ?? [];

if (!$cliente_id || !$carrinho) {
    echo json_encode(['success' => false, 'mensagem' => 'Dados inválidos']);
    exit;
}

$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['quantidade'];
}

$pdo->beginTransaction();

// Salvar vale
$stmt = $pdo->prepare("
    INSERT INTO vales (id, cliente_id, total, status, criado_em)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE total = VALUES(total), status = VALUES(status)
");
$stmt->execute([$numero_vale, $cliente_id, $total, $status]);

// Limpa itens antigos
$pdo->prepare("DELETE FROM itens_vale WHERE vale_id = ?")->execute([$numero_vale]);

foreach ($carrinho as $pid => $item) {
    $stmtItem = $pdo->prepare("
        INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario)
        VALUES (?, ?, ?, ?)
    ");
    $stmtItem->execute([$numero_vale, $pid, $item['quantidade'], $item['preco']]);
}

$pdo->commit();
echo json_encode(['success' => true]);
