<?php
require_once '../config/database.php';

$codigo = $_POST['codigo'] ?? '';

$stmt = $pdo->prepare("SELECT nome, preco FROM produtos WHERE codigo_barra = ? OR nome = ?");
$stmt->execute([$codigo, $codigo]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if ($produto) {
    echo json_encode(['success' => true, 'produto' => $produto]);
} else {
    echo json_encode(['success' => false, 'erro' => 'Produto não encontrado']);
}
