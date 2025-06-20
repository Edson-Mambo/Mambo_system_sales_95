<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // se você usa Composer

use Dompdf\Dompdf;

$pdo = Database::conectar();
$data_hoje = date('Y-m-d');

// Coletar dados do resumo do dia
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_vendas, SUM(total) AS total_valor,
           SUM(valor_pago) AS valor_pago, SUM(troco) AS troco_total
    FROM vendas WHERE DATE(data_venda) = ?
");
$stmt->execute([$data_hoje]);
$resumo = $stmt->fetch();

$stmtProd = $pdo->prepare("
    SELECT SUM(pv.quantidade) AS total_produtos
    FROM produtos_vendidos pv
    JOIN vendas v ON pv.venda_id = v.id
    WHERE DATE(v.data_venda) = ?
");
$stmtProd->execute([$data_hoje]);
$total_produtos = $stmtProd->fetchColumn();

$stmtPagamentos = $pdo->prepare("
    SELECT metodo_pagamento, COUNT(*) AS qtd, SUM(total) AS total
    FROM vendas WHERE DATE(data_venda) = ? GROUP BY metodo_pagamento
");
$stmtPagamentos->execute([$data_hoje]);
$pagamentos = $stmtPagamentos->fetchAll();

$html = "
<h2 style='text-align:center;'>Relatório de Fecho do Dia</h2>
<p><strong>Data:</strong> " . date('d/m/Y') . "</p>
<hr>
<p><strong>Total de Vendas:</strong> {$resumo['total_vendas']}</p>
<p><strong>Total de Produtos Vendidos:</strong> {$total_produtos}</p>
<p><strong>Total Valor de Vendas:</strong> " . number_format($resumo['total_valor'], 2, ',', '.') . " MZN</p>
<p><strong>Valor Pago:</strong> " . number_format($resumo['valor_pago'], 2, ',', '.') . " MZN</p>
<p><strong>Troco Total:</strong> " . number_format($resumo['troco_total'], 2, ',', '.') . " MZN</p>
<br>
<h4>Métodos de Pagamento:</h4>
<ul>";
foreach ($pagamentos as $p) {
    $html .= "<li><strong>" . strtoupper($p['metodo_pagamento']) . ":</strong> {$p['qtd']} vendas – " . number_format($p['total'], 2, ',', '.') . " MZN</li>";
}
$html .= "</ul>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Forçar download
$filename = "fecho_dia_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ['Attachment' => true]);
