<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Se instalaste via Composer
require_once __DIR__ . '/../TCPDF/tcpdf.php'; // Se instalaste manualmente

require_once __DIR__ . '/../config/database.php';
$pdo = Database::conectar();

$venda_id = $_GET['venda_id'] ?? null;
if (!$venda_id) {
    exit('Venda não especificada');
}

// Buscar venda
$stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
$stmt->execute([$venda_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    exit('Venda não encontrada');
}

// Buscar itens vendidos
$stmt = $pdo->prepare("
    SELECT pv.*, p.nome, p.preco
    FROM produtos_vendidos pv
    JOIN produtos p ON pv.produto_id = p.id
    WHERE pv.venda_id = ?
");
$stmt->execute([$venda_id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================= TCPDF =========================
$numItens = count($produtos);
$alturaBase = 80;
$alturaTotal = $alturaBase + ($numItens * 10); // altura dinâmica conforme nº de itens

$pdf = new TCPDF('P', 'mm', [80, $alturaTotal], true, 'UTF-8', false);
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(true, 0);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

// Cabeçalho
$pdf->Cell(0, 5, "MamboSystem95", 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, "Tel: +258 84 854 1787", 0, 1, 'C');
$pdf->Cell(0, 5, "Email: info@mambosystem95.com", 0, 1, 'C');
$pdf->Cell(0, 5, "Local: Maputo, Moçambique", 0, 1, 'C');
$pdf->Ln(2);

$pdf->Cell(0, 0, str_repeat('-', 48), 0, 1, 'C');
$pdf->Ln(2);

// Info venda
$pdf->Cell(0, 5, "Recibo Nº: " . $venda['id'], 0, 1);
$pdf->Cell(0, 5, "Data: " . date('d/m/Y H:i', strtotime($venda['data_venda'])), 0, 1);
$pdf->Cell(0, 5, "Método: " . ucfirst($venda['metodo_pagamento']), 0, 1);
$pdf->Ln(2);

$pdf->Cell(0, 0, str_repeat('-', 48), 0, 1, 'C');
$pdf->Ln(2);

// Produtos
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(30, 5, "Produto", 0, 0);
$pdf->Cell(10, 5, "Qtd", 0, 0, 'C');
$pdf->Cell(15, 5, "Preço", 0, 0, 'R');
$pdf->Cell(20, 5, "Subtotal", 0, 1, 'R');

$pdf->SetFont('helvetica', '', 8);

$total = 0;
foreach ($produtos as $item) {
    $subtotal = $item['quantidade'] * $item['preco'];
    $total += $subtotal;

    $pdf->Cell(30, 5, $item['nome'], 0, 0);
    $pdf->Cell(10, 5, $item['quantidade'], 0, 0, 'C');
    $pdf->Cell(15, 5, number_format($item['preco'], 2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(20, 5, number_format($subtotal, 2, ',', '.'), 0, 1, 'R');
}

// Totais
$pdf->Ln(2);
$pdf->Cell(0, 0, str_repeat('-', 48), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, "Total: MT " . number_format($total, 2, ',', '.'), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 6, "Pago: MT " . number_format($venda['valor_pago'], 2, ',', '.'), 0, 1, 'R');
$pdf->Cell(0, 6, "Troco: MT " . number_format($venda['troco'], 2, ',', '.'), 0, 1, 'R');

// Mensagem final
$pdf->Ln(4);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, "Obrigado pela preferência!", 0, 1, 'C');

// Saída
$pdf->Output("recibo_venda_{$venda_id}.pdf", 'I'); 
