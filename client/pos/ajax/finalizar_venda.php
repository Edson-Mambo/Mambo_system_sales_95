<?php

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

require_once '../../helpers/auth.php';
require_once '../../config/database.php';
require_once '../../services/VendaService.php';
require_once '../../services/CarrinhoService.php';

try {

    verificarCaixa();

    $pdo = Database::conectar();

    $carrinho = CarrinhoService::obterCarrinho();

    if (empty($carrinho)) {
        echo json_encode([
            'success' => false,
            'message' => 'Carrinho vazio'
        ]);
        exit;
    }

    $total = CarrinhoService::calcularTotal($carrinho);

    $dados = [
        'total' => $total,
        'metodo_pagamento' => $_POST['metodo_pagamento'] ?? 'dinheiro',
        'valor_pago' => $_POST['valor_pago'] ?? 0,
        'itens' => $carrinho
    ];

    $result = VendaService::finalizarVenda($pdo, $dados);

    if (!empty($result['success'])) {

        // limpar carrinho corretamente via service
        CarrinhoService::limpar();

        echo json_encode([
            'success' => true,
            'venda_id' => $result['venda_id'] ?? null,
            'total' => $total
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Erro ao finalizar venda'
        ]);
    }

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor',
        'debug' => $e->getMessage()
    ]);
}