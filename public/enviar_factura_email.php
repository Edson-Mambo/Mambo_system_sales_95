<?php
require_once '../config/database.php';
// Inclua PHPMailer ou outra biblioteca aqui

$factura_id = $_GET['factura_id'] ?? null;

if (!$factura_id) {
  die("Fatura não encontrada.");
}

$pdo = Database::conectar();

$sql = "SELECT f.*, v.* FROM facturas f JOIN vendas v ON f.venda_id = v.id WHERE f.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$factura_id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
  die("Fatura não encontrada.");
}

// Aqui você geraria um PDF (dompdf, mPDF, etc) ou HTML
// E enviaria com PHPMailer usando $factura['email']
// Exemplo:
echo "📧 Simulação: enviando fatura para " . htmlspecialchars($factura['email']);
