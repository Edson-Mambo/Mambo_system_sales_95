<?php
ob_start();
session_start();

/* =========================
   ERROS
========================= */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/* =========================
   REQUIRE FILES
========================= */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/empresa.php';
require_once __DIR__ . '/../services/ReciboImpressaoService.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';
require_once __DIR__ . '/../middleware/auth.php';

use Controller\VendaController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   LOGIN
========================= */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   CAIXA
========================= */
if (empty($_SESSION['abertura_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = Database::conectar();
if (!empty($_SESSION['print_last_sale'])) {

    $vendaId = $_SESSION['print_last_sale'];
    unset($_SESSION['print_last_sale']);

    try {

        require_once __DIR__ . '/../services/ReciboImpressaoService.php';

        $config = getConfigEmpresa($pdo);

        ReciboImpressaoService::imprimir(
            $vendaId,
            $pdo,
            $config
        );

    } catch (Throwable $e) {
        error_log("ERRO IMPRESSÃO FINAL: " . $e->getMessage());
    }
}

$stmt = $pdo->prepare("
    SELECT id
    FROM abertura_caixa
    WHERE id = ?
    AND status = 'aberto'
    LIMIT 1
");
$stmt->execute([$_SESSION['abertura_id']]);
$abertura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$abertura) {
    unset($_SESSION['abertura_id']);
    die("<h3>Caixa fechado</h3><a href='login.php'>Voltar ao login</a>");
}

/* =========================
   PERMISSÕES
========================= */
requireRole(['admin','gerente','caixa']);

/* =========================
   TOTAL CARRINHO
========================= */
function calcularTotalCarrinho(): float
{
    $carrinho = $_SESSION['carrinho'] ?? [];
    $total = 0;

    foreach ($carrinho as $item) {
        $preco = floatval($item['preco'] ?? 0);
        $qtd = intval($item['quantidade'] ?? 1);
        $total += $preco * $qtd;
    }

    return $total;
}

/* =========================
   CONTROLLER
========================= */
$vendaController = new VendaController($pdo);

/* =========================
   REMOVER PRODUTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_produto'])) {

    $id = $_POST['remover_produto'];

    if (isset($_SESSION['carrinho'][$id])) {
        unset($_SESSION['carrinho'][$id]);
        $_SESSION['mensagem'] = "Produto removido com sucesso!";
    }

    header("Location: venda.php");
    exit;
}

/* =========================
   FINALIZAR VENDA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venda'])) {

    ob_clean();
    header('Content-Type: application/json');

    try {

        $resultado = $vendaController->processarRequisicao();

        if (!empty($resultado['success']) && !empty($resultado['venda_id'])) {

            $venda_id = $resultado['venda_id'];

            $config = getConfigEmpresa($pdo);

            try {
                ReciboImpressaoService::imprimir(
                    $venda_id,
                    $pdo,
                    $config
                );
            } catch (Throwable $e) {
                error_log("ERRO IMPRESSÃO: " . $e->getMessage());
            }
        }

        echo json_encode($resultado);
        exit;

    } catch (Exception $e) {

        echo json_encode([
            'success' => false,
            'mensagem' => $e->getMessage()
        ]);
        exit;
    }
}

/* =========================
   FINALIZAR + EMAIL + IMPRESSÃO
========================= */
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

        if (!empty($_POST['cliente_id'])) {
            $_SESSION['cliente_id'] = (int)$_POST['cliente_id'];
        }

        $valorPago = (float)($_POST['valor_pago_email'] ?? 0);
        $metodo = $_POST['metodo_pagamento_email'] ?? 'dinheiro';

        $resultado = $vendaController->finalizarVenda($valorPago, $metodo);

        if (!$resultado['success']) {
            echo json_encode($resultado);
            exit;
        }

        $venda_id = $resultado['venda_id'];

        $config = getConfigEmpresa($pdo);

        try {
            ReciboImpressaoService::imprimir($venda_id, $pdo, $config);
        } catch (Throwable $e) {
            error_log("ERRO IMPRESSÃO: " . $e->getMessage());
        }

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '1234edclovesmambo@gmail.com';
        $mail->Password = 'rrkmatlydngtgype';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
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

        echo json_encode([
            'success' => true,
            'venda_id' => $venda_id,
            'email' => true
        ]);
        exit;

    } catch (Exception $e) {

        echo json_encode([
            'success' => false,
            'mensagem' => $e->getMessage()
        ]);
        exit;
    }
}

/* =========================
   OUTRAS AÇÕES
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['adicionar']) || isset($_POST['remover_produto'])) {
        $vendaController->processarRequisicao();
        exit;
    }
}

/* =========================
   VIEW
========================= */
$carrinho = $_SESSION['carrinho'] ?? [];
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$numero_recibo = $_SESSION['numero_recibo'] ?? 'N/A';
$total = calcularTotalCarrinho();

require_once __DIR__ . '/../src/View/venda.view.php';