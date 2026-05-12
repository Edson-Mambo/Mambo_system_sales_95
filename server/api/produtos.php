<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $pdo = Database::conectar();

    $stmt = $pdo->query("
        SELECT 
            id,
            nome,
            codigo_barra,
            preco,
            categoria_id,
            criado_em,
            estoque,
            imagem
        FROM produtos
    ");

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "produtos" => $produtos
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "mensagem" => $e->getMessage()
    ]);
}