<?php

session_start();

header('Content-Type: application/json');

require_once '../../services/CarrinhoService.php';

try {

    $carrinhoService = new CarrinhoService();

    $items = $carrinhoService->listar();

    // 🔥 REVALIDAÇÃO DE SEGURANÇA
    foreach ($items as $id => &$item) {

        if (!isset($item['preco']) || $item['preco'] <= 0) {

            // força recalcular preço da BD
            $item['preco'] = $carrinhoService->recalcularPreco($id);
        }

    }

    $total = 0;

    foreach ($items as $item) {
        $total += (float)$item['preco'] * (int)$item['quantidade'];
    }

    echo json_encode([
        'success' => true,
        'items' => array_values($items),
        'total' => $total
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage(),
        'linha' => $e->getLine(),
        'arquivo' => $e->getFile()
    ]);
}