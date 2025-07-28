<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

require_once '../config/database.php';

$pdo = Database::conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método inválido'
    ]);
    exit;
}

$produto_busca = trim($_POST['produto_busca'] ?? '');
$quantidade = intval($_POST['quantidade'] ?? 1);

if ($produto_busca === '' || $quantidade < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Produto ou quantidade inválida.'
    ]);
    exit;
}

$sql = "SELECT id, nome, codigo_barra, preco, estoque FROM produtos WHERE codigo_barra = ? OR nome LIKE ? LIMIT 1";


$stmt = $pdo->prepare($sql);
$stmt->execute([$produto_busca, "%$produto_busca%"]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    // Produto não encontrado → cria produto fictício com ID negativo
    $produtoId = -time();
    $produto = [
        'id' => $produtoId,
        'nome' => $produto_busca,
        'codigo_barra' => null,
        'preco' => 0.00,
        'estoque' => 0
    ];
} else {
    $produtoId = intval($produto['id']);
}

if (!isset($_SESSION['vale_carrinho'])) {
    $_SESSION['vale_carrinho'] = [];
}

$carrinho = &$_SESSION['vale_carrinho'];

if (isset($carrinho[$produtoId])) {
    $carrinho[$produtoId]['quantidade'] += $quantidade;
} else {
    $carrinho[$produtoId] = [
        'id' => $produtoId,
        'nome' => $produto['nome'],
        'codigo_barra' => $produto['codigo_barra'],
        'preco' => floatval($produto['preco']),
        'quantidade' => $quantidade
    ];
}

echo json_encode([
    'success' => true,
    'produto' => $carrinho[$produtoId]
]);
exit;
