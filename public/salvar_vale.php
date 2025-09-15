<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

header('Content-Type: application/json');

// Recebe o cliente_id e status do POST enviado pelo modal
$cliente_id = $_POST['cliente_id'] ?? null;
$status_pagamento = $_POST['status_vale'] ?? 'Aberto';
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

    // Insere o vale com o valor total e status
    $stmt = $pdo->prepare("INSERT INTO vales (numero_vale, cliente_id, status_pagamento, valor_total, data_criacao) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$numero_vale, $cliente_id, $status_pagamento, $total_vale]);

    $id_vale = $pdo->lastInsertId();

    // Insere os itens do vale
    $stmtItem = $pdo->prepare("INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
    foreach ($carrinho as $item) {
        $stmtItem->execute([$id_vale, $item['id'], $item['quantidade'], $item['preco']]);
    }

    // Limpa o carrinho após salvar o vale
    unset($_SESSION['carrinho']);

    echo json_encode([
        'success' => true,
        'mensagem' => 'Vale salvo com sucesso!',
        'numero_vale' => $numero_vale,
        'status' => $status_pagamento
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => $e->getMessage()]);
}
