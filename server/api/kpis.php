<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/database.php';

try {

    $pdo = Database::conectar();

    $stmt = $pdo->query("
        SELECT
            (SELECT IFNULL(SUM(total),0) FROM vendas WHERE DATE(data_venda)=CURDATE()) AS vendasHoje,
            (SELECT IFNULL(SUM(total),0) FROM vendas) AS faturacaoTotal,
            (SELECT COUNT(*) FROM produtos) AS totalProdutos,
            (SELECT COUNT(*) FROM produtos WHERE stock <= 5) AS stockBaixo,
            (SELECT COUNT(*) FROM usuarios) AS totalUsuarios
    ");

    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($dados);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "erro" => true,
        "mensagem" => $e->getMessage()
    ]);
}