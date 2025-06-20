<?php
require '../vendor/autoload.php'; // Caminho para o autoload do Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['venda_id'])) {
    exit('Venda não especificada');
}

$venda_id = intval($_GET['venda_id']);

require_once '../config/database.php';
$pdo = Database::conectar();

// Buscar venda
$stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
$stmt->execute([$venda_id]);
$venda = $stmt->fetch();

if (!$venda) {
    exit('Venda não encontrada.');
}

// Buscar itens vendidos
$stmt = $pdo->prepare("SELECT pv.*, p.nome FROM produtos_vendidos pv 
JOIN produtos p ON pv.produto_id = p.id 
WHERE pv.venda_id = ?");
$stmt->execute([$venda_id]);
$produtos = $stmt->fetchAll();

// Criar HTML do recibo
$html = "
    <h2 style='text-align:center;'>Recibo de Venda</h2>
    <p><strong>Recibo nº:</strong> {$venda['id']}</p>
    <p><strong>Data:</strong> " . date('d/m/Y H:i', strtotime($venda['data_venda'])) . "</p>
    <p><strong>Método:</strong> " . ucfirst($venda['metodo_pagamento']) . "</p>
    <table width='100%' border='1' cellspacing='0' cellpadding='5'>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Preço Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>";

foreach ($produtos as $item) {
    $total = $item['quantidade'] * $item['preco_unitario'];
    $html .= "
        <tr>
            <td>{$item['nome']}</td>
            <td>{$item['quantidade']}</td>
            <td>MT " . number_format($item['preco_unitario'], 2, ',', '.') . "</td>
            <td>MT " . number_format($total, 2, ',', '.') . "</td>
        </tr>";
}

$html .= "
        </tbody>
    </table>
    <p><strong>Total Pago:</strong> MT " . number_format($venda['valor_pago'], 2, ',', '.') . "</p>
    <p><strong>Troco:</strong> MT " . number_format($venda['troco'], 2, ',', '.') . "</p>
    <p style='text-align:center;'>Obrigado pela preferência!</p>
";

// Configurar Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A6', 'portrait'); // Tamanho típico de recibo
$dompdf->render();

// Forçar download
$dompdf->stream("recibo_venda_{$venda_id}.pdf", ["Attachment" => true]); // <- true força download
exit;
