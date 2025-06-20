<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Controller\VendaController;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';

$pdo = Database::conectar();
$vendaController = new VendaController($pdo);

// Se for POST, processa requisição AJAX/form e sai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendaController->processarRequisicao();
    exit;
}

// Para GET, prepara variáveis e carrega view
$carrinho = $_SESSION['carrinho'] ?? [];
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$numero_recibo = $_SESSION['numero_recibo'] ?? 'N/A';

$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['quantidade'];
}

require_once __DIR__ . '/../src/View/venda.view.php';
