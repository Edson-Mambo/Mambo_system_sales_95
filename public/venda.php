<?php
ob_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/configuracoes/logMiddleware.php';
require_once "../middleware/auth.php";

requireRole(['admin','gerente','caixa']);

use Controller\VendaController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';

/* ----------------------------
   FUNÇÕES
-----------------------------*/
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

/* ----------------------------
   INIT
-----------------------------*/
$pdo = Database::conectar();
$vendaController = new VendaController($pdo);

/* ----------------------------
   REMOVER PRODUTO
-----------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_produto'])) {

    $codigoRemover = $_POST['remover_produto'];

    if (isset($_SESSION['carrinho'][$codigoRemover])) {
        unset($_SESSION['carrinho'][$codigoRemover]);
        $_SESSION['mensagem'] = 'Produto removido com sucesso!';
    }

    header('Location: venda.php');
    exit;
}

/* ----------------------------
   DESCONTO
-----------------------------*/
$desconto_colaborador = !empty($_POST['desconto_colaborador']);

/* ----------------------------
   TOTAL
-----------------------------*/
$valor_total = calcularTotalCarrinho();

if ($desconto_colaborador) {
    $valor_total *= 0.9;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_enviar'])) {

    ob_clean();
    header('Content-Type: application/json');

    try {

        $email = filter_var($_POST['email_destino'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            echo json_encode([
                'success' => false,
                'mensagem' => 'Email inválido'
            ]);
            exit;
        }

        /* =========================
           GARANTIR CLIENTE NA VENDA
        ========================= */
        if (!empty($_POST['cliente_id'])) {
            $_SESSION['cliente_id'] = (int)$_POST['cliente_id'];
        }

        /* =========================
           VALORES
        ========================= */
        $valorPago = (float)($_POST['valor_pago_email'] ?? 0);
        $metodo = $_POST['metodo_pagamento_email'] ?? 'dinheiro';

        /* =========================
           FINALIZAR VENDA (CORRETO)
        ========================= */
        $resultado = $vendaController->finalizarVenda($valorPago, $metodo);

        if (!$resultado['success']) {
            echo json_encode($resultado);
            exit;
        }

        $venda_id = $resultado['venda_id'];

        /* =========================
           EMAIL RESPONSE BASE
        ========================= */
        $response = [
            'success' => true,
            'venda_id' => $venda_id,
            'pdf_url' => "gerar_recibo.php?venda_id=$venda_id"
        ];

        /* =========================
           ENVIAR EMAIL
        ========================= */
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '1234edclovesmambo@gmail.com';
        $mail->Password = 'rrkmatlydngtgype';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('1234edclovesmambo@gmail.com', 'Mambo System POS');
        $mail->addAddress($email);

        $mail->Subject = "Recibo Venda #$venda_id";
        $mail->Body = "Segue recibo da sua compra.";

        $pdfPath = __DIR__ . '/../public/pdf/recibo_' . $venda_id . '.pdf';

        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        $mail->send();

        $response['email'] = true;

        echo json_encode($response);
        exit;

    } catch (Exception $e) {

        echo json_encode([
            'success' => false,
            'mensagem' => $e->getMessage()
        ]);
        exit;
    }
}

/* ----------------------------
   OUTRAS AÇÕES (controller)
-----------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['finalizar_venda'])) {
        ob_clean();
        header('Content-Type: application/json');

        $vendaController->processarRequisicao();
        exit;
    }

    if (isset($_POST['adicionar']) || isset($_POST['remover_produto'])) {
        $vendaController->processarRequisicao();
        exit;
    }
}

/* ----------------------------
   VIEW
-----------------------------*/
$carrinho = $_SESSION['carrinho'] ?? [];
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$numero_recibo = $_SESSION['numero_recibo'] ?? 'N/A';

$total = calcularTotalCarrinho();

if ($desconto_colaborador) {
    $total *= 0.9;
}

require_once __DIR__ . '/../src/View/venda.view.php';