<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = Database::conectar();

// Verifica se o ID foi passado corretamente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido.";
    exit;
}

$id = (int) $_GET['id'];

// Buscar o produto para verificar se existe
$stmt = $pdo->prepare("SELECT * FROM produtos_takeaway WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}

// Excluir o produto
$stmt = $pdo->prepare("DELETE FROM produtos_takeaway WHERE id = ?");
$stmt->execute([$id]);

// Redirecionar de volta para a listagem
header("Location: listar_takeaway.php");
exit;
?>
