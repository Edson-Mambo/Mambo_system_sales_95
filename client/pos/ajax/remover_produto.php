<?php
/* =========================
   AJAX — REMOVER PRODUTO DO CARRINHO
   Caminho: client/pos/ajax/remover_produto.php
========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Apenas POST é aceite
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Autenticação básica
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

// Lê o corpo JSON
$body  = file_get_contents('php://input');
$dados = json_decode($body, true);

// Suporta também form POST normal (fallback)
if (empty($dados)) {
    $dados = $_POST;
}

$codigo = isset($dados['codigo']) ? (string)$dados['codigo'] : null;

if ($codigo === null || $codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Código do produto não informado.']);
    exit;
}

if (!isset($_SESSION['carrinho'][$codigo])) {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado no carrinho.']);
    exit;
}

unset($_SESSION['carrinho'][$codigo]);

echo json_encode(['success' => true, 'message' => 'Produto removido com sucesso.']);