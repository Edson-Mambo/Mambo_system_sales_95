<?php

require_once __DIR__ . '/../config/database.php';
$pdo = Database::conectar();

$skus = $_GET['skus'] ?? '';

/* =========================
   🔹 PROCESSAR INPUT
========================= */
$lista = array_filter(array_map('trim', explode(',', $skus)));

if (empty($lista)) {
    die("Nenhum SKU válido");
}

/* =========================
   🔹 CONTAR REPETIÇÕES
========================= */
$contagem = array_count_values($lista);

/* =========================
   🔹 BUSCAR PRODUTOS
========================= */
$placeholders = implode(',', array_fill(0, count($contagem), '?'));

$stmt = $pdo->prepare("
    SELECT p.*, c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.codigo_barra IN ($placeholders)
");

$stmt->execute(array_keys($contagem));
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   🔹 EXPANDIR LABELS (REPETIÇÃO)
========================= */
$labels = [];

foreach ($produtos as $p) {

    $qtd = $contagem[$p['codigo_barra']] ?? 1;

    for ($i = 0; $i < $qtd; $i++) {
        $labels[] = $p;
    }
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>

/* A4 BASE */
body {
    margin: 0;
    padding: 10px;
    font-family: Arial, sans-serif;
}

/* GRID A4 */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

/* LABEL */
.label {
    height: 180px;
    border: 1px solid #111;
    border-radius: 8px;
    padding: 10px;

    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}

/* TOPO */
.top {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
}

/* NOME */
.name {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}

/* CATEGORIA */
.category {
    font-size: 10px;
    color: #777;
}

/* PREÇO */
.price {
    font-size: 18px;
    font-weight: bold;
    text-align: right;
}

/* DIVISOR */
.divider {
    border-top: 1px dashed #ccc;
    margin: 8px 0;
}

/* BOTTOM */
.bottom {
    margin-top: auto;
    text-align: center;
}

/* BARCODE */
.barcode {
    width: 100%;
    height: 50px;
}

/* SKU */
.sku {
    font-size: 10px;
}

/* PRINT */
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

<div class="grid">

<?php foreach ($labels as $p): ?>

<div class="label">

    <div class="top">

        <div>
            <div class="name"><?= htmlspecialchars($p['nome']) ?></div>
            <div class="category"><?= htmlspecialchars($p['categoria_nome'] ?? '') ?></div>
        </div>

        <div class="price">
            <?= number_format($p['preco'], 2) ?> MZN
        </div>

    </div>

    <div class="divider"></div>

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

<?php endforeach; ?>

</div>

<script>
window.onload = function () {
    JsBarcode(".barcode").init();

    setTimeout(() => {
        window.print();
    }, 300);
};
</script>

</body>
</html>