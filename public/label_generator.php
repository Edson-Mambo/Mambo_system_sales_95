<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin', 'gerente', 'supervisor']);

/* =========================
   VOLTAR ERP
========================= */
$nivel = $_SESSION['nivel_acesso'] ?? '';

$rotas = [
    'admin' => 'index_admin.php',
    'gerente' => 'index_gerente.php',
    'supervisor' => 'index_supervisor.php'
];

$voltar = $rotas[$nivel] ?? 'index.php';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gerador de Labels</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#eef2f7;
}

/* HEADER */
.header-box{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
    margin-bottom:20px;
}

/* CARD */
.card-erp{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
    margin-bottom:20px;
}

/* RESULT */
.result-box{
    background:#f8f9fa;
    padding:15px;
    border-radius:10px;
    border-left:4px solid #0d6efd;
    margin-top:15px;
}

/* TEXTAREA */
textarea{
    resize:none;
}
</style>
</head>

<body>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="header-box d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">🏷️ Gerador de Labels </h3>
            <small>Impressão de etiquetas de produtos</small>
        </div>

        <a href="<?= $voltar ?>" class="btn btn-outline-secondary">
            ← Voltar
        </a>
    </div>

    <!-- BUSCA INDIVIDUAL -->
    <div class="card-erp">
        <h5>🔎 Buscar Produto</h5>

        <div class="row">
            <div class="col-md-9">
                <input 
                    type="text" 
                    id="sku" 
                    class="form-control"
                    placeholder="Digite SKU ou código de barras">
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100" onclick="buscarProduto()">
                    Pesquisar
                </button>
            </div>
        </div>

        <div id="produtoInfo" class="result-box" style="display:none;"></div>
    </div>

    <!-- LABEL ÚNICA -->
    <div class="card-erp">
        <h5>🖨️ Gerar Label Individual</h5>

        <button class="btn btn-success w-100" onclick="gerarLabel(1)">
            Gerar 1 Label
        </button>
    </div>

    <!-- LABEL MÚLTIPLA -->
    <div class="card-erp">
        <h5>📦 Gerar Labels em Lote</h5>

        <label class="mb-2">
            Informe múltiplos códigos separados por vírgula:
        </label>

        <textarea 
            id="skus"
            class="form-control"
            rows="4"
            placeholder="Ex: 12345,67890,11122">
        </textarea>

        <button class="btn btn-primary mt-3 w-100" onclick="gerarMultiplosSKUs()">
            Gerar Labels A4
        </button>
    </div>

</div>

<script>
let produtoAtual = null;

/* =========================
   BUSCAR PRODUTO
========================= */
async function buscarProduto() {

    const sku = document.getElementById('sku').value.trim();

    if (!sku) {
        alert("Digite um SKU ou código.");
        return;
    }

    const res = await fetch(
        'api_get_product_by_sku.php?sku=' + encodeURIComponent(sku)
    );

    const data = await res.json();

    if (!data.id) {
        alert("Produto não encontrado.");
        return;
    }

    produtoAtual = data;

    document.getElementById('produtoInfo').style.display = 'block';
    document.getElementById('produtoInfo').innerHTML = `
        <strong>${data.nome}</strong><br>
        💰 Preço: ${data.preco} MT<br>
        📦 Categoria: ${data.categoria_nome}<br>
        🔢 Código: ${data.codigo_barra}
    `;
}

/* =========================
   LABEL ÚNICA
========================= */
function gerarLabel(qtd) {

    if (!produtoAtual) {
        alert("Pesquise um produto primeiro.");
        return;
    }

    window.open(
        `print_labels.php?id=${produtoAtual.id}&qtd=${qtd}`,
        '_blank'
    );
}

/* =========================
   LABEL EM LOTE
========================= */
function gerarMultiplosSKUs() {

    const skus = document.getElementById('skus').value.trim();

    if (!skus) {
        alert("Digite pelo menos um código.");
        return;
    }

    window.open(
        `print_labels_lote.php?skus=${encodeURIComponent(skus)}`,
        '_blank'
    );
}
</script>

</body>
</html>