<?php

session_start();

header('Content-Type: application/json');

require_once '../../services/CarrinhoService.php';
require_once '../../services/ProdutoService.php';

try {

    $json = json_decode(file_get_contents("php://input"), true);

    $produtoId = (int)($json['produto_id'] ?? 0);
    $quantidade = (int)($json['quantidade'] ?? 1);

    if ($produtoId <= 0) {
        throw new Exception("Produto inválido");
    }

    $produtoService = new ProdutoService();
    $produto = $produtoService->buscarPorId($produtoId);

    if (!$produto) {
        throw new Exception("Produto não encontrado");
    }

    // 🔥 NÃO normalizar preço aqui!
    $produtoNormalizado = [
        'id'     => (int)$produto['id'],
        'nome'   => $produto['nome'] ?? 'Produto',
        'codigo' => $produto['codigo_barra'] ?? ''
    ];

    $carrinho = new CarrinhoService();
    $carrinho->adicionar($produtoNormalizado, $quantidade);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}