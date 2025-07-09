<?php
session_start();
require_once __DIR__ . '/configuracoes/logMiddleware.php';



// Mostrar erros para desenvolvimento (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Controller\VendaController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';

// Função para calcular o total do carrinho (soma preço x quantidade)
function calcularTotalCarrinho(): float {
    $carrinho = $_SESSION['carrinho'] ?? [];
    $total = 0.0;
    foreach ($carrinho as $item) {
        $preco = floatval($item['preco'] ?? 0);
        $quantidade = intval($item['quantidade'] ?? 1);
        $total += $preco * $quantidade;
    }
    return $total;
}

$pdo = Database::conectar();
$vendaController = new VendaController($pdo);

// --- REMOVER PRODUTO DO CARRINHO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_produto'])) {
    $codigoRemover = $_POST['remover_produto'];

    if (isset($_SESSION['carrinho'][$codigoRemover])) {
        unset($_SESSION['carrinho'][$codigoRemover]);
        $_SESSION['mensagem'] = 'Produto removido com sucesso!';
    }
    header('Location: venda.php');
    exit;
}

// --- VERIFICA SE HOUVE SOLICITAÇÃO DE DESCONTO PARA COLABORADOR ---
$desconto_colaborador = !empty($_POST['desconto_colaborador']);

// --- CALCULA TOTAL DO CARRINHO ---
$valor_total = calcularTotalCarrinho();

// --- APLICA DESCONTO 10% SE SOLICITADO ---
if ($desconto_colaborador) {
    $valor_total *= 0.9;
}

// --- FINALIZAR VENDA E ENVIAR POR EMAIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_enviar'])) {
    header('Content-Type: application/json');

    $email = filter_var($_POST['email_destino'] ?? '', FILTER_VALIDATE_EMAIL);
    $mensagem = trim($_POST['mensagem_email'] ?? '');
    $valorPago = floatval($_POST['valor_pago_email'] ?? 0);
    $metodoPagamento = trim($_POST['metodo_pagamento_email'] ?? '');

    if (!$email) {
        echo json_encode([
            'success' => false,
            'mensagem' => 'E-mail de destino inválido.'
        ]);
        exit;
    }

    // Aqui você pode passar o desconto, se necessário, para o controller se quiser que ele também aplique
    $resultado = $vendaController->finalizarVenda($valorPago, $metodoPagamento);

    if (!$resultado['success']) {
        echo json_encode([
            'success' => false,
            'mensagem' => 'Erro ao finalizar a venda antes de enviar o e-mail.'
        ]);
        exit;
    }

    $venda_id = $resultado['venda_id'];
    $pdfPathRelativo = $resultado['pdfPath'] ?? '';

    $pdfPathAbsoluto = realpath(__DIR__ . '/' . $pdfPathRelativo);

    if (!$pdfPathAbsoluto || !file_exists($pdfPathAbsoluto)) {
        echo json_encode([
            'success' => false,
            'mensagem' => 'Erro ao gerar o recibo em PDF. Arquivo não encontrado: ' . (__DIR__ . '/' . $pdfPathRelativo)
        ]);
        exit;
    }

    
    //Logica de enviar o email
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '1234edclovesmambo@gmail.com';
        $mail->Password   = 'rrkmatlydngtgype';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('1234edclovesmambo@gmail.com', 'Mambo System Sales');
        $mail->addAddress($email);
        $mail->Subject = "Recibo da Venda #{$venda_id}";
        $mail->Body    = $mensagem !== '' ? $mensagem : "Segue em anexo o recibo da sua compra. Obrigado!";
        $mail->addAttachment($pdfPathAbsoluto);

        $mail->send();

        echo json_encode([
            'success' => true,
            'venda_id' => $venda_id,
            'pdfPath' => $pdfPathRelativo,
            'mensagem' => 'Venda finalizada e recibo enviado por e-mail.'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'mensagem' => "Erro ao enviar o e-mail: " . $mail->ErrorInfo
        ]);
    }
    exit;
}

// --- FINALIZAR VENDA NORMAL (SEM EMAIL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venda'])) {
    header('Content-Type: application/json');

    $valorPago = floatval($_POST['valor_pago'] ?? 0);
    $metodoPagamento = trim($_POST['metodo_pagamento'] ?? '');

    $resultado = $vendaController->finalizarVenda($valorPago, $metodoPagamento);

    if ($resultado['success'] && isset($resultado['pdfPath']) && $resultado['pdfPath']) {
        $_SESSION['numero_recibo'] = $resultado['venda_id'];
        unset($_SESSION['carrinho']);
    }

    echo json_encode($resultado);
    exit;
}

// --- PROCESSAR OUTRAS AÇÕES (ADICIONAR PRODUTO, ETC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendaController->processarRequisicao();
    exit;
}

// --- PREPARAR DADOS PARA EXIBIÇÃO DA PÁGINA ---
$carrinho = $_SESSION['carrinho'] ?? [];
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$numero_recibo = $_SESSION['numero_recibo'] ?? 'N/A';

// Total final para a view (já com desconto se houver)
$total = calcularTotalCarrinho();
if ($desconto_colaborador) {
    $total *= 0.9;
}

// Passar para a view o estado do desconto para marcar o checkbox corretamente
require_once __DIR__ . '/../src/View/venda.view.php';
