<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Model/Venda.php';
require_once __DIR__ . '/../src/Model/Produto.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valorRecebido = floatval($_POST['valor_recebido'] ?? 0);

    if ($valorRecebido <= 0 || !isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Carrinho vazio ou valor inválido.']);
        exit;
    }

    $produtosVendidos = $_SESSION['carrinho'];
    $usuario_id = $_SESSION['usuario']['id'] ?? 0;
    $numero_recibo = 'R' . time(); // número único

    $totalVenda = 0;

    foreach ($produtosVendidos as $produto) {
        $totalVenda += $produto['quantidade'] * $produto['preco'];
    }

    if ($valorRecebido < $totalVenda) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Valor recebido é menor que o total da venda.']);
        exit;
    }

    $troco = $valorRecebido - $totalVenda;
    $dataHora = date('Y-m-d H:i:s');

    // Grava a venda
    $stmt = $pdo->prepare("INSERT INTO vendas (usuario_id, numero_recibo, total, valor_recebido, troco, data_hora) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $numero_recibo, $totalVenda, $valorRecebido, $troco, $dataHora]);
    $venda_id = $pdo->lastInsertId();

    // Grava os produtos vendidos e atualiza estoque
    foreach ($produtosVendidos as $produto) {
        $produto_id = $produto['id'];
        $quantidade = $produto['quantidade'];
        $preco = $produto['preco'];
        $subtotal = $quantidade * $preco;

        // Inserir na tabela produtos_vendidos
        $stmtItem = $pdo->prepare("INSERT INTO produtos_vendidos (venda_id, produto_id, quantidade, preco_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)");
        $stmtItem->execute([$venda_id, $produto_id, $quantidade, $preco, $subtotal]);

        // Atualizar estoque
        $stmtEstoque = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmtEstoque->execute([$quantidade, $produto_id]);

        // Registra no movimento_estoque
        $stmtMov = $pdo->prepare("INSERT INTO movimento_estoque (produto_id, tipo, quantidade, data_hora, usuario_id, observacao)
            VALUES (?, 'saida', ?, ?, ?, ?)");
        $stmtMov->execute([$produto_id, $quantidade, $dataHora, $usuario_id, "Venda Nº $numero_recibo"]);
    }

    // Limpa carrinho
    unset($_SESSION['carrinho']);

    // Retorno em JSON para JavaScript
    echo json_encode([
        'status' => 'ok',
        'redirect' => "recibo.php?venda_id=" . $venda_id
    ]);
    exit;
}
?>
