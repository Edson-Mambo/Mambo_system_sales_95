<?php
require_once __DIR__ . '/../../config/database.php';


$produto_id = $_POST['produto_id'] ?? null;
$quantidade = $_POST['quantidade'] ?? 0;
$unidade = $_POST['unidade'] ?? 'peca';
$observacao = $_POST['observacao'] ?? '';

if ($produto_id && $quantidade > 0) {
    $pdo = Database::conectar();
    

// Inserir registro de recepção
$stmt = $pdo->prepare("INSERT INTO recepcao_estoque (produto_id, quantidade_recebida, unidade, observacao)
                       VALUES (?, ?, ?, ?)");
$stmt->execute([$produto_id, $quantidade, $unidade, $observacao]);

// Atualizar o estoque corretamente
$stmt = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
$stmt->execute([$quantidade, $produto_id]);


    header("Location: sucesso_recepcao.php");
    exit;
}
?>
