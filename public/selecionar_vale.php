<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id_vale']) || empty($_POST['id_vale'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "ID do vale não informado."]);
        exit;
    }

    $id_vale = intval($_POST['id_vale']);
    $pdo = Database::conectar();

    // Buscar dados do vale
    $stmt = $pdo->prepare("
        SELECT v.id, v.valor_total, COALESCE(v.valor_pago,0) as valor_pago, 
               (v.valor_total - COALESCE(v.valor_pago,0)) as saldo,
               c.nome as cliente_nome
        FROM vales v
        JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ?
    ");
    $stmt->execute([$id_vale]);
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vale) {
        echo json_encode(["sucesso" => false, "mensagem" => "Vale não encontrado."]);
        exit;
    }

    // Guardar na sessão
    $_SESSION['vale_selecionado'] = $vale;

    echo json_encode(["sucesso" => true, "mensagem" => "Vale carregado com sucesso!", "vale" => $vale]);

} catch (Exception $e) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro: " . $e->getMessage()]);
}
