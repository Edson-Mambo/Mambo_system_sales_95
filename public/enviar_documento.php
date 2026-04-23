<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = Database::conectar();

/* -------------------------
   BUSCAR CONFIGURAÇÕES
--------------------------*/
$stmtConfig = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    die("Configurações da empresa não encontradas.");
}

/* -------------------------
   TIPO DOCUMENTO
--------------------------*/
$tipo = $_GET['tipo'] ?? null;

if ($tipo === 'cotacao') {
    $id = $_GET['cotacao_id'] ?? null;
} elseif ($tipo === 'factura') {
    $id = $_GET['factura_id'] ?? null;
} else {
    die("Parâmetros inválidos.");
}

if (!$id) {
    die("ID do documento não especificado.");
}

/* -------------------------
   BUSCAR DOCUMENTO
--------------------------*/
if ($tipo === 'cotacao') {

    $sql = "SELECT * FROM cotacoes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlItens = "SELECT * FROM cotacao_itens WHERE cotacao_id = ?";
    $stmtItens = $pdo->prepare($sqlItens);
    $stmtItens->execute([$id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    $email = $doc['email'] ?? null;
    $pdfPathRelativo = "pdf/cotacoes/cotacao_{$id}.pdf";

} elseif ($tipo === 'factura') {

    $sql = "
        SELECT f.*, v.*
        FROM facturas f
        JOIN vendas v ON f.venda_id = v.id
        WHERE f.id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlItens = "
        SELECT pv.*, p.nome AS nome_produto
        FROM produtos_vendidos pv
        JOIN produtos p ON pv.produto_id = p.id
        WHERE pv.venda_id = ?
    ";

    $stmtItens = $pdo->prepare($sqlItens);
    $stmtItens->execute([$doc['venda_id']]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    $email = $doc['email'] ?? null;
    $pdfPathRelativo = "pdf/facturas/factura_{$id}.pdf";
}

if (!$doc) {
    die("Documento não encontrado.");
}

if (!$email) {
    die("E-mail do cliente não encontrado.");
}

/* -------------------------
   VERIFICAR PDF
--------------------------*/
$pdfPathAbsoluto = realpath(
    __DIR__ . '/../public/' . $pdfPathRelativo
);

if (!$pdfPathAbsoluto || !file_exists($pdfPathAbsoluto)) {
    die("PDF não encontrado.");
}

/* -------------------------
   MENSAGEM EMAIL
--------------------------*/
$mensagem = !empty($config['mensagem_email'])
    ? $config['mensagem_email']
    : "Olá! Segue em anexo o documento {$tipo} #{$id} emitido por {$config['nome_empresa']}.";

/* -------------------------
   LINK VOLTAR
--------------------------*/
if ($tipo === 'factura') {
    $voltar = '../public/venda.php';
} elseif ($tipo === 'cotacao') {
    $voltar = '../src/View/cotacao.view.php';
} else {
    $voltar = '../public/index.php';
}

/* -------------------------
   ENVIAR EMAIL
--------------------------*/
try {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_email'];
    $mail->Password = $config['smtp_senha'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['smtp_port'];

    $mail->setFrom(
        $config['smtp_email'],
        $config['nome_empresa']
    );

    $mail->addAddress($email);

    $mail->Subject = ucfirst($tipo)
        . " #{$id} - "
        . $config['nome_empresa'];

    $mail->Body = $mensagem;

    $mail->addAttachment($pdfPathAbsoluto);

    $mail->send();

    echo "
    <div class='alert alert-success mt-3'>
        ✅ " . ucfirst($tipo) . " enviada para 
        <strong>{$email}</strong> com sucesso!
    </div>
    ";

    echo "
    <a href='../public/imprimir_{$tipo}.php?{$tipo}_id={$id}' 
       class='btn btn-success mt-2'>
       🖨️ Imprimir
    </a>
    ";

    echo "
    <a href='{$voltar}' 
       class='btn btn-secondary mt-2'>
       ⬅️ Voltar
    </a>
    ";

} catch (Exception $e) {

    echo "
    <div class='alert alert-danger mt-3'>
        ❌ Erro ao enviar o e-mail: {$mail->ErrorInfo}
    </div>
    ";

    echo "
    <a href='{$voltar}' class='btn btn-secondary mt-2'>
        ⬅️ Voltar
    </a>
    ";
}