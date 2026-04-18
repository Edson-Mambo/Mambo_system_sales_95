<?php

require_once __DIR__ . '/../config/database.php';
$pdo = Database::conectar();

$id = $_GET['id'] ?? 0;
$qtd = min(20, intval($_GET['qtd'] ?? 1));

$stmt = $pdo->prepare("
    SELECT p.*, c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.id = ?
");

$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    die("Produto não encontrado");
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>

body {
    margin: 0;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: #fff;
}

/* LABEL COM BORDA DUPLA MINIMALISTA */
.label {
    width: 320px;
    height: 195px;

    position: relative;

    border: 1px solid #111;
    border-radius: 8px;

    padding: 12px;

    display: flex;
    flex-direction: column;

    box-sizing: border-box;
    margin: 10px;

    background: #fff;
}

/* BORDA INTERNA (EFEITO DUPLO MINIMALISTA) */
.label::before {
    content: "";
    position: absolute;
    top: 4px;
    left: 4px;
    right: 4px;
    bottom: 4px;

    border: 1px solid #ddd;
    border-radius: 6px;

    pointer-events: none;
}

/* TOPO */
.top {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    z-index: 2;
}

/* NOME (DESTAQUE PRINCIPAL) */
.name {
    font-size: 17px;
    font-weight: 900;
    text-transform: uppercase;
    line-height: 1.15;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* CATEGORIA */
.category {
    font-size: 10px;
    color: #777;
    margin-top: 4px;
}

/* PREÇO */
.price-box {
    text-align: right;
    min-width: 100px;
}

.price {
    font-size: 23px;
    font-weight: 900;
    color: #000;
}

/* SEPARADOR MUITO SUAVE */
.divider {
    border-top: 1px dashed #e0e0e0;
    margin: 10px 0;
}

/* BOTTOM FIXO */
.bottom {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    z-index: 2;
}

/* BARCODE */
.barcode {
    width: 100%;
    height: 52px;
}

/* SKU */
.sku {
    font-size: 10px;
    color: #444;
    letter-spacing: 0.8px;
}

/* PRINT LIMPO */
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

<?php for ($i = 0; $i < $qtd; $i++): ?>

<div class="label">

    <!-- TOPO -->
    <div class="top">

        <div>
            <div class="name">
                <?= htmlspecialchars($p['nome']) ?>
            </div>

            <div class="category">
                <?= htmlspecialchars($p['categoria_nome'] ?? 'Sem categoria') ?>
            </div>
        </div>

        <div class="price-box">
            <div class="price">
                <?= number_format($p['preco'], 2) ?> MZN
            </div>
        </div>

    </div>

    <div class="divider"></div>

    <!-- BARCODE -->
    <div class="bottom">

        <svg class="barcode"
             jsbarcode-format="CODE128"
             jsbarcode-value="<?= htmlspecialchars($p['codigo_barra']) ?>"
             jsbarcode-displayValue="false">
        </svg>

        <div class="sku">
            <?= htmlspecialchars($p['codigo_barra']) ?>
        </div>

    </div>

</div>

<?php endfor; ?>

<script>
window.onload = function () {
    JsBarcode(".barcode").init();

    setTimeout(() => {
        window.print();
    }, 200);
};
</script>

</body>
</html>