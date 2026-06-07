<?php

ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/VendaService.php';
require_once __DIR__ . '/../../services/CarrinhoService.php';

try {

    verificarCaixa();

    $pdo = Database::conectarLocal();

    /* =========================
       CARRINHO
    ========================= */
    $carrinhoService = new CarrinhoService();
    $carrinho = $carrinhoService->listar();

    if (empty($carrinho)) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Carrinho vazio'
        ]);
        exit;
    }

    $total = $carrinhoService->total();

    /* =========================
       INPUT JSON
    ========================= */
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    /* =========================
       VALIDAÇÃO MÉTODO PAGAMENTO
    ========================= */
    $metodosPermitidos = ['dinheiro', 'm-pesa', 'e-mola', 'cartao'];

    $metodoPagamento = strtolower(trim($input['metodo_pagamento'] ?? 'dinheiro'));

    if (!in_array($metodoPagamento, $metodosPermitidos, true)) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Método de pagamento inválido',
            'permitidos' => $metodosPermitidos
        ]);
        exit;
    }

    /* =========================
       DADOS DA VENDA
    ========================= */
    $dados = [
        'total' => $total,
        'metodo_pagamento' => $metodoPagamento,
        'valor_pago' => $input['valor_pago'] ?? 0,
        'desconto' => $input['desconto'] ?? false,
        'itens' => $carrinho
    ];

    /* =========================
       FINALIZAR VENDA
    ========================= */
    $vendaService = new VendaService();

    $result = $vendaService->finalizar(
        $dados['itens'],
        $_SESSION['usuario_id']
    );

    ob_clean();

    if (!empty($result['success'])) {

        $carrinhoService->limpar();

        echo json_encode([
            'success' => true,
            'venda_id' => $result['venda_id'] ?? null,
            'total' => $total,
            'metodo_pagamento' => $metodoPagamento
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Erro ao finalizar venda'
        ]);
    }

} catch (Throwable $e) {

    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor',
        'debug' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}