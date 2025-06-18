<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$carrinho = json_decode($_POST['carrinho'] ?? '[]', true);
$valor_pago = floatval($_POST['valor_pago'] ?? 0);

if (empty($carrinho)) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Carrinho vazio']);
    exit;
}

$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['qtd'];
}

if ($valor_pago < $total) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Pagamento insuficiente']);
    exit;
}

try {
    $pdo->beginTransaction();

    // INSERIR NA TABELA DE VENDAS_TAKEAWAY
    $sql = "INSERT INTO vendas_takeaway (data_venda, total, valor_pago, troco) VALUES (NOW(), ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $troco = $valor_pago - $total;
    $stmt->execute([$total, $valor_pago, $troco]);

    $id_venda = $pdo->lastInsertId();

    // INSERIR PRODUTOS NA TABELA produtos_vendidos_takeaway
    $sqlProduto = "INSERT INTO produtos_vendidos_takeaway (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
    $stmtProduto = $pdo->prepare($sqlProduto);

    foreach ($carrinho as $item) {
        $stmtProduto->execute([$id_venda, $item['id'], $item['qtd'], $item['preco']]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'success', 'id_venda' => $id_venda]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'mensagem' => $e->getMessage()]);
}
