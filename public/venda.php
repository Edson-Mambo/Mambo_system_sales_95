<?php

session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}



// Mensagem de sucesso após finalizar a venda
$mensagem = '';
$reciboPath = '';
if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
    $mensagem = $_SESSION['mensagem'] ?? 'Venda finalizada com sucesso.';
    $reciboPath = $_SESSION['pdf_recibo'] ?? '';
    
    // Limpa as mensagens da sessão após exibir
    unset($_SESSION['mensagem'], $_SESSION['pdf_recibo']);
}

// Carrega as dependências
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';

use Controller\VendaController;

// Conecta ao banco de dados
$pdo = Database::conectar();
if (!$pdo instanceof PDO) {
    die('Erro ao conectar ao banco de dados.');
}

try {
    // Inicializa o controller
    $vendaController = new VendaController($pdo);

    // Processa ações (adicionar ao carrinho, finalizar venda)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $vendaController->processarRequisicao();
    }

    // Obtém dados para a view
    $carrinho = $vendaController->getCarrinho();
    if (empty($mensagem)) {
        $mensagem = $vendaController->mensagem;
    }

    // Renderiza a view
    include __DIR__ . '/../src/View/venda.view.php';

    // Exibe mensagem (após a view)
    if ($mensagem) {
        echo "<div class='alert alert-info text-center m-3'>{$mensagem}</div>";
    }

    // Link para o recibo (se existir)
    if (!empty($reciboPath)) {
        echo "<div class='text-center'>
                <a href='{$reciboPath}' class='btn btn-success' download>Baixar Recibo (PDF)</a>
              </div>";
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erro ao processar requisição: " . htmlspecialchars($e->getMessage()) . "</div>";
}

