<?php

require_once __DIR__ . '/../config/database.php';
$pdo = Database::conectar();

$id = $_GET['id'] ?? 0;
$qtd = min(40, intval($_GET['qtd'] ?? 1));

$stmt = $pdo->prepare("
    SELECT p.*, c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.id = ?
");

$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    die("Produto não encontrado");
}

$dataHoje = date('d/m/Y');
$semanaAno = date('W');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Labels </title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>

/* =========================
   BASE A4 ERP
========================= */
@page {
    size: A4;
    margin: 8mm;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background: #fff;
}

/* GRID ERP */
.wrap {
    display: grid;
    grid-template-columns: repeat(3, 65mm);
    gap: 6mm;
    justify-content: center;
}

/* =========================
   LABEL PRINCIPAL
========================= */
.label {
    width: 65mm;
    height: 52mm;

    border: 1px solid #e0e0e0;
    border-radius: 6px;

    padding: 3.5mm;
    box-sizing: border-box;

    display: flex;
    flex-direction: column;
    justify-content: space-between;

    background: #fff;
}

/* =========================
   TOP
========================= */
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

/* =========================
   NAME
========================= */
.product-name {
    font-size: 10pt;
    font-weight: 700;
    text-transform: uppercase;
    line-height: 1.1;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;

    margin-top: 2px;
}

/* =========================
   PRICE
========================= */
.price-box {
    text-align: center;
    margin-top: 2px;
}

.currency {
    font-size: 7pt;
    color: #777;
}

.price {
    font-size: 20pt;
    font-weight: 900;
    color: #000;
    line-height: 1;
}

/* =========================
   BARCODE
========================= */
.barcode-section {
    text-align: center;
    margin-top: auto;
}

.barcode {
    width: 100%;
    height: 24px;
}

.sku {
    font-size: 7pt;
    color: #444;
    margin-top: 1px;
    letter-spacing: 0.5px;
}

/* =========================
   FOOTER (SEMANA + DATA DENTRO)
========================= */
.footer-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;

    font-size: 6.5pt;
    color: #888;

    border-top: 1px dashed #ddd;
    padding-top: 2px;
    margin-top: 2px;
}

/* =========================
   PRINT
========================= */
@media print {
    body {
        padding: 0;
    }

    .label {
        page-break-inside: avoid;
    }
}

</style>
</head>

<body>

<div class="wrap">

<?php for ($i = 0; $i < $qtd; $i++): ?>

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
            <?= number_format($produto['preco'], 2, ',', '.') ?>
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

    <!-- FOOTER CORRIGIDO -->
    <div class="footer-meta">
        <span>Semana <?= $semanaAno ?></span>
        <span><?= $dataHoje ?></span>
    </div>

</div>

<?php endfor; ?>

</div>

<script>
window.onload = function () {
    JsBarcode(".barcode").init();

    setTimeout(() => {
        window.print();
    }, 300);
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