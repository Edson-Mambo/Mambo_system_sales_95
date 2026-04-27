<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$pdo = Database::conectar();

/* SEGURANÇA */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

$nivel = $_SESSION['nivel_acesso'] ?? '';

$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos)) {
    header("Location: ../../public/index.php");
    exit;
}

/* QUERY */
$sql = "
SELECT
    p.id,
    p.nome,
    p.codigo_barra,
    p.preco,
    p.estoque AS estoque_atual,
    COALESCE(SUM(pv.quantidade), 0) AS quantidade_vendida,
    (p.estoque + COALESCE(SUM(pv.quantidade), 0)) AS total_inicial
FROM produtos p
LEFT JOIN produtos_vendidos pv ON pv.produto_id = p.id
LEFT JOIN vendas v ON v.id = pv.venda_id
    AND v.data_venda <= CURDATE()
GROUP BY p.id, p.nome, p.codigo_barra, p.preco, p.estoque
ORDER BY p.nome ASC
";

$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$voltar = [
    'admin' => '../../public/index_admin.php',
    'gerente' => '../../public/index_gerente.php',
    'supervisor' => '../../public/index_supervisor.php'
][$nivel] ?? '../../public/index.php';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Inventário com Vendas</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #eef2f7;
}

/* HEADER */
.page-header {
    background: #fff;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* CARD */
.card-erp {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

/* TABLE */
.table thead th {
    position: sticky;
    top: 0;
    background: #1f2937;
    color: #fff;
    z-index: 2;
}

.table tbody tr:hover {
    background: #f1f5f9;
}

/* LOW STOCK */
.low-stock {
    background-color: #fff3cd !important;
}

/* SEARCH */
#searchInput {
    border-radius: 10px;
}

/* PAGINATION */
.pagination-info {
    font-size: 0.9rem;
}
</style>
</head>

<body>

<div class="container-fluid py-3">

<!-- HEADER -->
<div class="page-header d-flex justify-content-between align-items-center mb-3">

    <div>
        <h4 class="mb-0">📦 Inventário com Vendas</h4>
        <small class="text-muted">Até <?= date('d/m/Y') ?></small>
    </div>

    <div class="d-flex gap-2">

        <a href="<?= $voltar ?>" class="btn btn-outline-secondary btn-sm">← Voltar</a>

        <a href="../../public/inventario_fisico.php" class="btn btn-outline-primary btn-sm">
            Físico
        </a>

        <a href="../../public/comparar_inventario.php" class="btn btn-outline-primary btn-sm">
            Comparar
        </a>

    </div>
</div>

<!-- SEARCH -->
<div class="card card-erp mb-3">
    <div class="card-body">
        <input type="text" id="searchInput" class="form-control"
               placeholder="🔎 Pesquisar produto ou código...">
    </div>
</div>

<!-- TABLE -->
<div class="card card-erp">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0" id="tabela">

<thead>
<tr>
    <th>Produto</th>
    <th>Código</th>
    <th>Preço</th>
    <th class="text-center">Estoque</th>
    <th class="text-center">Vendida</th>
    <th class="text-center">Inicial</th>
</tr>
</thead>

<tbody>

<?php foreach ($produtos as $produto): ?>

<?php $classe = ($produto['estoque_atual'] <= 5) ? 'low-stock' : ''; ?>

<tr class="<?= $classe ?>">

    <td><strong><?= htmlspecialchars($produto['nome']) ?></strong></td>

    <td><code><?= htmlspecialchars($produto['codigo_barra']) ?></code></td>

    <td>MT <?= number_format($produto['preco'], 2, ',', '.') ?></td>

    <td class="text-center"><?= (int)$produto['estoque_atual'] ?></td>
    <td class="text-center"><?= (int)$produto['quantidade_vendida'] ?></td>
    <td class="text-center"><?= (int)$produto['total_inicial'] ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<!-- PAGINATION -->
<div class="d-flex justify-content-between mt-3">

    <button class="btn btn-outline-primary btn-sm" onclick="prevPage()">◀ Anterior</button>

    <span id="pageInfo" class="text-muted pagination-info"></span>

    <button class="btn btn-outline-primary btn-sm" onclick="nextPage()">Próximo ▶</button>

</div>

</div>

<!-- SCRIPTS -->
<script>

/* SEARCH (melhorado sem quebra futura) */
document.getElementById("searchInput").addEventListener("keyup", function () {

    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#tabela tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });

});

/* PAGINATION */
let currentPage = 1;
let rowsPerPage = 10;

function paginate() {

    let rows = document.querySelectorAll("#tabela tbody tr");
    let visibleRows = Array.from(rows).filter(r => r.style.display !== "none");

    let totalPages = Math.ceil(visibleRows.length / rowsPerPage);

    rows.forEach((row, index) => {

        if (row.style.display === "none") return;

        let visibleIndex = visibleRows.indexOf(row);

        row.style.display =
            (visibleIndex >= (currentPage - 1) * rowsPerPage &&
             visibleIndex < currentPage * rowsPerPage)
            ? "" : "none";
    });

    document.getElementById("pageInfo").innerText =
        `Página ${currentPage} de ${totalPages || 1}`;
}

function nextPage() {
    currentPage++;
    paginate();
}

function prevPage() {
    if (currentPage > 1) currentPage--;
    paginate();
}

window.onload = paginate;

</script>

</body>
</html>