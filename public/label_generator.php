<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin', 'gerente', 'supervisor']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Gerador de Labels</title>

<style>
body { font-family: Arial; background:#f5f6fa; padding:20px; }

.container {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

input, button {
    padding: 10px;
    margin: 5px;
}

.result {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 10px;
}

.btn {
    background: #0d6efd;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 6px;
}

.btn:hover {
    background: #084298;
}
</style>
</head>

<body>

<div class="container">

<h2>🏷️ Gerador de Labels de Produtos</h2>


<!-- SKU SEARCH -->
<input type="text" id="sku" placeholder="Digite SKU / Código de barras">
<button class="btn" onclick="buscarProduto()">Pesquisar</button>

<div id="produtoInfo" class="result" style="display:none;"></div>

<hr>

<!-- SINGLE LABEL -->
<h3>Gerar 1 Label</h3>
<button class="btn" onclick="gerarLabel(1)">Gerar Label</button>

<hr>

<!-- MULTI LABEL -->
<h3>Gerar Múltiplas Labels (1–20)</h3>
<textarea id="skus" placeholder="Ex: 12345, 67890, 11122" 
style="width:100%; height:80px;"></textarea>

<br>

<button class="btn" onclick="gerarMultiplosSKUs()">Gerar Labels A4</button>
<br>
<br>

 <?php
            $nivel = $_SESSION['usuario_nivel'] ?? '';

            $voltar = match($nivel) {
                'admin' => 'index_admin.php',
                'supervisor' => 'index_supervisor.php',
                'gerente' => 'index_gerente.php',
                default => 'index.php'
            };
            ?>

            <a href="<?= $voltar ?>" class="btn btn-outline-secondary me-2">
                ← Voltar
            </a>
</div>


<script>

let produtoAtual = null;

async function buscarProduto() {

    const sku = document.getElementById('sku').value;

    const res = await fetch('api_get_product_by_sku.php?sku=' + sku);
    const data = await res.json();

    if (!data.id) {
        alert("Produto não encontrado");
        return;
    }

    produtoAtual = data;

    document.getElementById('produtoInfo').style.display = 'block';
    document.getElementById('produtoInfo').innerHTML = `
        <b>${data.nome}</b><br>
        💰 ${data.preco} Kz<br>
        📦 ${data.categoria_nome}<br>
        🔢 ${data.codigo_barra}
    `;
}

function gerarLabel(qtd) {

    if (!produtoAtual) {
        alert("Pesquisa um produto primeiro");
        return;
    }

    window.open(
        `print_labels.php?id=${produtoAtual.id}&qtd=${qtd}`,
        '_blank'
    );
}


function gerarMultiplosSKUs() {

    const skus = document.getElementById('skus').value;

    if (!skus.trim()) {
        alert("Digite pelo menos um SKU");
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