<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['carrinho'])) {
    $pdo = Database::conectar();
    $carrinho = json_decode($_POST['carrinho'], true);

    if (!$carrinho || count($carrinho) === 0) {
        http_response_code(400);
        echo "Carrinho vazio!";
        exit;
    }

    // Calcular total
    $total = 0;
    foreach ($carrinho as $item) {
        $total += $item['preco'] * $item['qtd'];
    }

    // Iniciar transação
    try {
        $pdo->beginTransaction();

        // Inserir na tabela vendas_takeaway
        $stmt = $pdo->prepare("INSERT INTO vendas_takeaway (total) VALUES (:total)");
        $stmt->execute([':total' => $total]);
        $venda_id = $pdo->lastInsertId();

        // Inserir cada item do carrinho
        $stmtItem = $pdo->prepare("INSERT INTO produtos_vendidos_takeaway (venda_id, produto_id, nome, preco, quantidade) VALUES (:venda_id, :produto_id, :nome, :preco, :quantidade)");

        foreach ($carrinho as $item) {
            $stmtItem->execute([
                ':venda_id'   => $venda_id,
                ':produto_id' => $item['id'],
                ':nome'       => $item['nome'],
                ':preco'      => $item['preco'],
                ':quantidade' => $item['qtd']
            ]);
        }

        $pdo->commit();
        echo "Venda registrada com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "Erro ao finalizar venda: " . $e->getMessage();
    }
} else {
    http_response_code(400);
    echo "Requisição inválida.";
}
