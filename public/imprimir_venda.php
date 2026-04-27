<?php
session_start();
require_once '../config/database.php';

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
   VENDA COMPLETA
========================= */
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        c.nome AS cliente_nome,
        c.apelido AS cliente_apelido,
        c.telefone,
        c.email,
        c.morada,
        c.nuit,
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
$cliente = trim(($venda['cliente_nome'] ?? '') . ' ' . ($venda['cliente_apelido'] ?? ''));
if ($cliente === '') $cliente = 'Cliente Geral';

/* =========================
   TOTAL REAL
========================= */
$total = 0;
foreach ($itens as $i) {
    $total += $i['quantidade'] * $i['preco_unitario'];
}

$valorPago = (float)($venda['valor_pago'] ?? 0);
$troco = (float)($venda['troco'] ?? ($valorPago - $total));

/* =========================
   LOGO
========================= */
$logo = '';
if (!empty($config['logo'])) {
    $path = __DIR__ . '/../public/uploads/' . $config['logo'];
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = base64_encode(file_get_contents($path));
        $logo = "<img src='data:image/$type;base64,$data' style='max-height:80px;'>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Recibo de Venda</title>

<style>
body {
    font-family: Arial;
    font-size: 13px;
    color: #000;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: auto;
}

/* HEADER IGUAL PDF */
.header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.empresa {
    font-size: 18px;
    font-weight: bold;
}

.sub {
    font-size: 11px;
    color: #333;
}

/* CARD */
.card {
    border: 1px solid #000;
    padding: 10px;
    margin-top: 10px;
}

/* FLEX INFO */
.flex {
    display: flex;
    justify-content: space-between;
}

/* TABELA IGUAL PDF */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th {
    background: #000;
    color: #fff;
    padding: 8px;
}

td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

/* TOTAIS */
.total {
    text-align: right;
    font-weight: bold;
    margin-top: 10px;
}

/* FOOTER */
.footer {
    text-align: center;
    font-size: 11px;
    margin-top: 20px;
    border-top: 1px dashed #000;
    padding-top: 10px;
}

/* PRINT */
@media print {
    button { display:none; }
}
</style>
</head>

<body onload="window.print()">

<div class="container">

    <!-- HEADER IGUAL PDF -->
    <div class="header">
        <?= $logo ?>
        <div class="empresa"><?= $config['nome_empresa'] ?></div>
        <div class="sub"><?= $config['endereco'] ?></div>
        <div class="sub">
            <?= $config['rua_avenida'] ?>, 
            <?= $config['bairro'] ?>, 
            <?= $config['cidade'] ?>, 
            <?= $config['provincia'] ?>
        </div>
        <div class="sub">
            Tel: <?= $config['telefone'] ?> | <?= $config['email_empresa'] ?>
        </div>
        <div class="sub">
            NUIT: <?= $config['nuit_empresa'] ?>
        </div>
    </div>

    <!-- INFO VENDA -->
    <div class="card">
        <div class="flex">
            <div><b>Recibo:</b> #<?= $venda_id ?></div>
            <div><b>Data:</b> <?= $venda['data_venda'] ?></div>
        </div>

        <div class="flex">
            <div><b>Operador:</b> <?= $venda['operador_nome'] ?></div>
            <div><b>Pagamento:</b> <?= $venda['metodo_pagamento'] ?></div>
        </div>
    </div>

    <!-- CLIENTE -->
    <div class="card">
        <b>Cliente:</b> <?= $cliente ?><br>
        Tel: <?= $venda['telefone'] ?><br>
        Email: <?= $venda['email'] ?><br>
        Morada: <?= $venda['morada'] ?><br>
        NUIT: <?= $venda['nuit'] ?>
    </div>

    <!-- ITENS -->
    <table>
        <tr>
            <th>Produto</th>
            <th>Qtd</th>
            <th>Preço</th>
            <th>Subtotal</th>
        </tr>

        <?php foreach ($itens as $i): 
            $sub = $i['quantidade'] * $i['preco_unitario'];
        ?>
        <tr>
            <td><?= $i['nome'] ?></td>
            <td><?= $i['quantidade'] ?></td>
            <td><?= number_format($i['preco_unitario'],2) ?></td>
            <td><?= number_format($sub,2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- TOTAIS -->
    <div class="total">
        TOTAL: MT <?= number_format($total,2) ?><br>
        PAGO: MT <?= number_format($valorPago,2) ?><br>
        TROCO: MT <?= number_format($troco,2) ?>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <?= $config['mensagem_rodape'] ?>
    </div>

</div>

</body>
</html>