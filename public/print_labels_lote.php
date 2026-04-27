<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

/* =========================
   RECEBER SKUS
========================= */
$skus = trim($_GET['skus'] ?? '');

if (empty($skus)) {
    die("Nenhum SKU informado.");
}

/* =========================
   PROCESSAR LISTA
========================= */
$lista = array_filter(array_map('trim', explode(',', $skus)));

if (empty($lista)) {
    die("Nenhum SKU válido.");
}

/* =========================
   CONTAGEM
========================= */
$contagem = array_count_values($lista);

$placeholders = implode(',', array_fill(0, count($contagem), '?'));

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.nome,
        p.preco,
        p.codigo_barra,
        c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.codigo_barra IN ($placeholders)
");

$stmt->execute(array_keys($contagem));
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$produtos) {
    die("Nenhum produto encontrado.");
}

/* =========================
   EXPANDIR LABELS
========================= */
$labels = [];

foreach ($produtos as $produto) {
    $qtd = $contagem[$produto['codigo_barra']] ?? 1;

    for ($i = 0; $i < $qtd; $i++) {
        $labels[] = $produto;
    }
}

$dataHoje = date('d/m/Y');
$horaAtual = date('H:i');
$semanaAno = date('W');
$totalLabels = count($labels);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Labels </title>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>

@page {
    size: A4;
    margin: 8mm;
}

body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#fff;
}

/* HEADER */
.header{
    text-align:center;
    margin-bottom:8mm;
    border-bottom:2px solid #111;
    padding-bottom:6px;
}

.header h2{
    margin:0;
    font-size:18px;
    letter-spacing:2px;
}

.header small{
    font-size:11px;
    color:#666;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns: repeat(3, 65mm);
    gap:6mm;
    justify-content:center;
}

/* LABEL */
.label{
    width:65mm;
    height:52mm;
    border:1px solid #ddd;
    border-radius:6px;
    padding:3.5mm;
    box-sizing:border-box;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    background:#fff;
}

/* TOP */
.top {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #eee;
    padding-bottom: 2px;
}

.company {
    font-size: 7pt;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #222;
}

.category {
    font-size: 7pt;
    color: #777;
}

/* PRODUCT */
.product-name{
    text-align:center;
    font-size:10pt;
    font-weight:bold;
    text-transform:uppercase;
    line-height:1.1;
    min-height:20px;
    margin:2px 0;
}

/* PRICE */
.price-box{
    text-align:center;
}

.currency{
    font-size:9pt;
    color:#777;
}

.price{
    font-size:22pt;
    font-weight:900;
    letter-spacing:1px;
}

/* BARCODE */
.barcode-section{
    text-align:center;
    margin-top:2px;
}

.barcode{
    width:100%;
    height:24px;
}

.sku{
    font-size:7pt;
    margin-top:1px;
}

/* FOOTER */
.footer-meta{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:6pt;
    color:#888;
    border-top:1px dashed #ddd;
    padding-top:3px;
}

/* PRINT */
@media print{
    .header{
        display:none;
    }

    .label{
        page-break-inside:avoid;
    }
}

</style>
</head>

<body>

<div class="header">
    <h2>MAMBO SYSTEM SALES 95</h2>
    <small>
        <?= $totalLabels ?> etiquetas • Semana <?= $semanaAno ?> • <?= $dataHoje ?> • <?= $horaAtual ?>
    </small>
</div>

<div class="grid">

<?php foreach ($labels as $produto): ?>

<div class="label">

    <!-- TOP -->
    <div class="top">
        <div class="company">MAMBO SYSTEM</div>
        <div class="category">
            <?= htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria') ?>
        </div>
    </div>

    <!-- NAME -->
    <div class="product-name">
        <?= htmlspecialchars($produto['nome']) ?>
    </div>

    <!-- PRICE -->
    <div class="price-box">
        <div class="currency">MZN</div>
        <div class="price">
            <?= number_format($produto['preco'],2,',','.') ?>
        </div>
    </div>

    <!-- BARCODE -->
    <div class="barcode-section">
        <svg class="barcode"
             jsbarcode-format="CODE128"
             jsbarcode-value="<?= htmlspecialchars($produto['codigo_barra']) ?>"
             jsbarcode-displayValue="false">
        </svg>

        <div class="sku">
            <?= htmlspecialchars($produto['codigo_barra']) ?>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-meta">
        <span>Semana <?= $semanaAno ?></span>
        <span><?= $dataHoje ?></span>
    </div>

</div>

<?php endforeach; ?>

</div>

<script>
window.onload = function () {
    JsBarcode(".barcode").init();

    setTimeout(() => {
        window.print();
    }, 400);
};
</script>
<script>
function loadAlerts() {
    fetch('/api/alerts.php')
        .then(r => r.json())
        .then(data => {
            console.log(data);

            // aqui atualizas UI (badge, modal, toast)
        });
}

// cada 10 segundos
setInterval(loadAlerts, 10000);

loadAlerts();
</script>
</body>
</html>