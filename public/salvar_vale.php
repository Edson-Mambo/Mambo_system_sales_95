<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

header('Content-Type: application/json');

$cliente_id = $_SESSION['cliente_id'] ?? null;
$status_pagamento = $_POST['status_pagamento'] ?? 'pendente';
$carrinho = $_SESSION['carrinho'] ?? [];

if (!$cliente_id || empty($carrinho)) {
    echo json_encode(['success' => false, 'mensagem' => 'Cliente ou carrinho inválido.']);
    exit;
}

try {
    $numero_vale = 'VALE-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Calcula o total do vale
    $total_vale = 0;
    foreach ($carrinho as $item) {
        if (!isset($item['id'])) {
            throw new Exception("Chave 'id' não existe no item do carrinho!");
        }
        $total_vale += $item['preco'] * $item['quantidade'];
    }

    // Insere o vale com o valor total
    $stmt = $pdo->prepare("INSERT INTO vales (numero_vale, cliente_id, status_pagamento, valor_total, data_criacao) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$numero_vale, $cliente_id, $status_pagamento, $total_vale]);

    $id_vale = $pdo->lastInsertId();

    // Insere os itens do vale
    $stmtItem = $pdo->prepare("INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
    foreach ($carrinho as $item) {
        $stmtItem->execute([$id_vale, $item['id'], $item['quantidade'], $item['preco']]);
    }

    unset($_SESSION['carrinho']);

    echo json_encode([
        'success' => true,
        'mensagem' => 'Vale salvo com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => $e->getMessage()]);
}
