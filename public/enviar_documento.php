<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload do PHPMailer e outras libs
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = Database::conectar();

$tipo = $_GET['tipo'] ?? null;

// Validação do tipo e ID
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

// Busca dados do documento e itens
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
    $sql = "SELECT f.*, v.* FROM facturas f JOIN vendas v ON f.venda_id = v.id WHERE f.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlItens = "SELECT pv.*, p.nome AS nome_produto FROM produtos_vendidos pv 
                 JOIN produtos p ON pv.produto_id = p.id WHERE pv.venda_id = ?";
    $stmtItens = $pdo->prepare($sqlItens);
    $stmtItens->execute([$doc['venda_id']]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    $email = $doc['email'] ?? null;

    $pdfPathRelativo = "pdf/facturas/factura_{$id}.pdf";

} else {
    die("Tipo de documento inválido.");
}

if (!$doc) {
    die("Documento não encontrado.");
}

if (!$email) {
    die("E-mail do cliente não encontrado.");
}

// Verifica o PDF
$pdfPathAbsoluto = realpath(__DIR__ . '/../public/' . $pdfPathRelativo);

if (!$pdfPathAbsoluto || !file_exists($pdfPathAbsoluto)) {
    die("PDF do documento não encontrado em: " . htmlspecialchars($pdfPathRelativo));
}

// Corpo do e-mail
$mensagem = "Olá! Segue em anexo o documento {$tipo} #{$id} emitido pelo Mambo System Sales.";

// Define o link de retorno correto:
if ($tipo === 'factura') {
    $voltar = '../public/venda.php';  // página de vendas
} elseif ($tipo === 'cotacao') {
    $voltar = '../src/View/cotacao.view.php'; // página de cotações
} else {
    $voltar = '../public/index.php'; // fallback seguro
}

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

    $mail->Subject = ucfirst($tipo) . " #{$id} - Mambo System Sales";
    $mail->Body    = $mensagem;
    $mail->addAttachment($pdfPathAbsoluto);

    $mail->send();

    echo "<div class='alert alert-success mt-3'>✅ " . ucfirst($tipo) . " enviada para <strong>{$email}</strong> com sucesso!</div>";
    echo "<a href='../public/imprimir_{$tipo}.php?{$tipo}_id={$id}' class='btn btn-success mt-2'>🖨️ Imprimir</a> ";
    echo "<a href='{$voltar}' class='btn btn-secondary mt-2'>⬅️ Voltar</a>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger mt-3'>❌ Erro ao enviar o e-mail: {$mail->ErrorInfo}</div>";
    echo "<a href='{$voltar}' class='btn btn-secondary mt-2'>⬅️ Voltar</a>";
}
