<?php

require '../vendor/autoload.php';
require '../config/database.php';

use Dompdf\Dompdf;

$pdo = Database::conectar();

$venda_id = $_GET['venda_id'] ?? null;

if (!$venda_id) {
    die("Venda inválida");
}

/* =========================
   CONFIG EMPRESA
========================= */
$config = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1")
              ->fetch(PDO::FETCH_ASSOC);

/* =========================
   VENDA + CLIENTE + OPERADOR
========================= */
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        c.nome AS cliente_nome,
        c.apelido AS cliente_apelido,
        c.telefone AS cliente_telefone,
        c.email AS cliente_email,
        c.morada AS cliente_morada,
        c.nuit AS cliente_nuit,
        u.nome AS operador_nome
    FROM vendas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.id = ?
");

$stmt->execute([$venda_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    die("Venda não encontrada");
}

/* =========================
   ITENS
========================= */
$stmt = $pdo->prepare("
    SELECT pv.*, p.nome
    FROM produtos_vendidos pv
    JOIN produtos p ON p.id = pv.produto_id
    WHERE pv.venda_id = ?
");

$stmt->execute([$venda_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CLIENTE
========================= */
$clienteNome = trim(($venda['cliente_nome'] ?? '') . ' ' . ($venda['cliente_apelido'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = 'Cliente Geral';
}

/* =========================
   🔥 RECONSTRUIR TOTAL (MAIS SEGURO)
========================= */
$totalCalculado = 0;

foreach ($itens as $i) {
    $totalCalculado += (float)$i['quantidade'] * (float)$i['preco_unitario'];
}

/* =========================
   VALORES FINANCEIROS (ROBUSTO)
========================= */
$total = (float)($venda['total'] ?? $totalCalculado);
$valorPago = (float)($venda['valor_pago'] ?? 0);
$troco = (float)($venda['troco'] ?? ($valorPago - $total));

/* =========================
   LOGO
========================= */
$logo = '';

if (!empty($config['logo'])) {

    $logoPath = __DIR__ . '/../public/uploads/' . $config['logo'];

    if (file_exists($logoPath)) {

        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        $base64 = base64_encode($data);

        $logo = "<img src='data:image/{$type};base64,{$base64}' class='logo'>";
    }
}

/* =========================
   HTML
========================= */
$html = "
<style>
body{font-family:Arial;font-size:12px;color:#333;}
.container{padding:20px;}
.header{text-align:center;border-bottom:2px solid #222;padding-bottom:10px;}
.logo{max-height:70px;margin-bottom:8px;}
.empresa{font-size:18px;font-weight:bold;}
.sub{font-size:11px;color:#666;}
.card{border:1px solid #ddd;padding:10px;margin-top:10px;}
.flex{display:flex;justify-content:space-between;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th{background:#111;color:#fff;padding:8px;}
td{padding:8px;border-bottom:1px solid #eee;}
.right{text-align:right;}
.total{text-align:right;font-size:15px;font-weight:bold;margin-top:10px;}
.footer{text-align:center;font-size:10px;margin-top:20px;}
</style>

<div class='container'>

<div class='header'>
    $logo
    <div class='empresa'>{$config['nome_empresa']}</div>
    <div class='sub'>{$config['endereco']}</div>
    <div class='sub'>
        {$config['rua_avenida']}, {$config['bairro']}, {$config['cidade']}, {$config['provincia']}
    </div>
    <div class='sub'>
        Tel: {$config['telefone']} | {$config['email_empresa']}
    </div>
    <div class='sub'>
        NUIT: {$config['nuit_empresa']}
    </div>
</div>

<div class='card'>
    <div class='flex'>
        <div><strong>Recibo:</strong> #$venda_id</div>
        <div><strong>Data:</strong> {$venda['data_venda']}</div>
    </div>
    <div class='flex'>
        <div><strong>Operador:</strong> {$venda['operador_nome']}</div>
        <div><strong>Pagamento:</strong> {$venda['metodo_pagamento']}</div>
    </div>
</div>

<div class='card'>
    <strong>Cliente:</strong> $clienteNome<br>
    Tel: {$venda['cliente_telefone']}<br>
    Email: {$venda['cliente_email']}<br>
    Morada: {$venda['cliente_morada']}<br>
    NUIT: {$venda['cliente_nuit']}<br>
</div>

<table>
<tr>
    <th>Produto</th>
    <th class='right'>Qtd</th>
    <th class='right'>Preço</th>
    <th class='right'>Subtotal</th>
</tr>
";

foreach ($itens as $i) {

    $sub = (float)$i['quantidade'] * (float)$i['preco_unitario'];

    $html .= "
    <tr>
        <td>{$i['nome']}</td>
        <td class='right'>{$i['quantidade']}</td>
        <td class='right'>" . number_format($i['preco_unitario'], 2) . "</td>
        <td class='right'>" . number_format($sub, 2) . "</td>
    </tr>
    ";
}

$html .= "
</table>

<div class='total'>
    TOTAL: MT " . number_format($total, 2) . "<br>
    PAGO: MT " . number_format($valorPago, 2) . "<br>
    TROCO: MT " . number_format($troco, 2) . "
</div>

<div class='footer'>
    {$config['mensagem_rodape']}
</div>

</div>
";

/* =========================
   DOWNLOAD DIRETO
========================= */
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("recibo_$venda_id.pdf", [
    "Attachment" => true
]);