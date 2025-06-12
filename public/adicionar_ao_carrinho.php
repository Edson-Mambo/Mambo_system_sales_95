<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$id = $_POST['id'] ?? null;
$nome = $_POST['nome'] ?? null;
$preco = $_POST['preco'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? 1; // Exemplo: usar sessão real

if ($id && $nome && $preco && $usuario_id) {
    $stmt = $pdo->prepare("INSERT INTO carrinho_temp (usuario_id, produto_id, nome, preco, quantidade) 
                           VALUES (?, ?, ?, ?, 1)
                           ON DUPLICATE KEY UPDATE quantidade = quantidade + 1");
    $stmt->execute([$usuario_id, $id, $nome, $preco]);
    echo "ok";
} else {
    http_response_code(400);
    echo "Dados inválidos";
}
