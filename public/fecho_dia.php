<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    echo "Acesso negado.";
    exit;
}

require_once '../config/database.php';
require_once '../vendor/autoload.php'; // DomPDF via Composer

use Dompdf\Dompdf;

$pdo = Database::conectar();
$data_hoje = date('Y-m-d');

// RESUMO DAS VENDAS DO DIA
$stmtResumo = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_vendas, 
        SUM(total) AS total_valor,
        SUM(valor_pago) AS valor_pago, 
        SUM(troco) AS troco_total
    FROM vendas 
    WHERE DATE(data_venda) = ?
");
$stmtResumo->execute([$data_hoje]);
$resumo = $stmtResumo->fetch();

// TOTAL DE PRODUTOS VENDIDOS
$stmtProdutos = $pdo->prepare("
    SELECT SUM(pv.quantidade) AS total_produtos
    FROM produtos_vendidos pv
    JOIN vendas v ON pv.venda_id = v.id
    WHERE DATE(v.data_venda) = ?
");
$stmtProdutos->execute([$data_hoje]);
$total_produtos = $stmtProdutos->fetchColumn();

// MÉTODOS DE PAGAMENTO
$stmtPagamentos = $pdo->prepare("
    SELECT metodo_pagamento, COUNT(*) AS qtd, SUM(total) AS total
    FROM vendas 
    WHERE DATE(data_venda) = ?
    GROUP BY metodo_pagamento
");
$stmtPagamentos->execute([$data_hoje]);
$pagamentos = $stmtPagamentos->fetchAll();

// INSERE NO BANCO (TABELA fechos)
$stmtFecho = $pdo->prepare("INSERT INTO fechos (usuario_id, total_vendas, total_valor) VALUES (?, ?, ?)");
$stmtFecho->execute([
    $_SESSION['usuario_id'],
    $resumo['total_vendas'],
    $resumo['total_valor']
]);

// GERAR CONTEÚDO HTML DO RELATÓRIO
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

// GERAR PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// FORÇAR DOWNLOAD
$filename = "fecho_dia_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ['Attachment' => true]);
exit;
