<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

/* SEGURANÇA */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

/* FILTROS */
$categoriaSelecionada = $_GET['categoria'] ?? 'Todos';
$soDiferenca = isset($_GET['so_diferenca']) && $_GET['so_diferenca'] == '1';

/* QUERY */
$sql = "
SELECT
    p.id,
    p.nome,
    p.codigo_barra,
    p.estoque AS estoque_sistema,
    IFNULL(f.quantidade_fisica, 0) AS estoque_fisico,
    (IFNULL(f.quantidade_fisica, 0) - p.estoque) AS diferenca,
    c.nome AS categoria
FROM produtos p
LEFT JOIN categorias c ON c.id = p.categoria_id
LEFT JOIN inventario_fisico f 
ON f.id = (
    SELECT id 
    FROM inventario_fisico 
    WHERE produto_id = p.id 
    ORDER BY data_registro DESC 
    LIMIT 1
)
";

$where = [];
$params = [];

if ($categoriaSelecionada !== 'Todos') {
    $where[] = 'c.nome = :categoria';
    $params[':categoria'] = $categoriaSelecionada;
}

if ($soDiferenca) {
    $where[] = '(IFNULL(f.quantidade_fisica, 0) - p.estoque) != 0';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY c.nome ASC, p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$voltar = "../src/View/inventario.view.php";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Comparar Inventário</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #eef2f7;
}

.page-header {
    background: #fff;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card-erp {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

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

.badge-pos { background: #16a34a; }
.badge-neg { background: #dc2626; }
.badge-zero { background: #6b7280; }

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
</head>

<body>

<div class="container-fluid py-3">

<!-- HEADER -->
<div class="page-header d-flex justify-content-between align-items-center mb-3 no-print">

    <div>
        <h4 class="mb-0">📊 Comparação de Inventário</h4>
        <small class="text-muted">Stock sistema vs físico</small>
    </div>

    <div class="d-flex gap-2">

        <a href="<?= $voltar ?>" class="btn btn-outline-secondary btn-sm">← Voltar</a>

        <a href="ajustar_estoque.php?categoria=<?= urlencode($categoriaSelecionada) ?>&so_diferenca=<?= $soDiferenca ? 1 : 0 ?>"
           class="btn btn-danger btn-sm">
            ⚙️ Ajustar Stock
        </a>

        <button class="btn btn-success btn-sm" onclick="exportExcel()">📊 Excel</button>
        <button class="btn btn-primary btn-sm" onclick="exportPDF()">📄 PDF</button>

    </div>
</div>

<!-- SEARCH -->
<div class="card card-erp mb-3 no-print">
    <div class="card-body">
        <input type="text" id="searchInput" class="form-control"
               placeholder="🔎 Pesquisar produto, código ou categoria...">
    </div>
</div>

<!-- TABLE -->
<div class="card card-erp">
    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0" id="tabela">

            <thead>
            <tr>
                <th>Categoria</th>
                <th>Produto</th>
                <th>Código</th>
                <th class="text-center">Sistema</th>
                <th class="text-center">Físico</th>
                <th class="text-center">Diferença</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($produtos as $p):
                $diff = (int)$p['diferenca'];

                if ($diff > 0) $badge = "badge-pos";
                elseif ($diff < 0) $badge = "badge-neg";
                else $badge = "badge-zero";
            ?>
            <tr>
                <td><?= htmlspecialchars($p['categoria']) ?></td>
                <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                <td><code><?= htmlspecialchars($p['codigo_barra']) ?></code></td>
                <td class="text-center"><?= (int)$p['estoque_sistema'] ?></td>
                <td class="text-center"><?= (int)$p['estoque_fisico'] ?></td>
                <td class="text-center">
                    <span class="badge <?= $badge ?> px-3 py-2">
                        <?= $diff ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>

        </table>

    </div>
</div>

<!-- PAGINATION -->
<div class="d-flex justify-content-between mt-3 no-print">

    <button class="btn btn-outline-primary btn-sm" onclick="prevPage()">◀ Anterior</button>

    <span id="pageInfo" class="text-muted small"></span>

    <button class="btn btn-outline-primary btn-sm" onclick="nextPage()">Próximo ▶</button>

</div>

</div>

<!-- SCRIPTS -->
<script>
/* =========================
   SEARCH LIVE
========================= */
document.getElementById("searchInput").addEventListener("keyup", function () {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#tabela tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});

/* =========================
   PAGINATION
========================= */
let currentPage = 1;
let rowsPerPage = 10;

function paginate() {
    let rows = document.querySelectorAll("#tabela tbody tr");
    let totalPages = Math.ceil(rows.length / rowsPerPage);

    rows.forEach((row, index) => {
        row.style.display =
            (index >= (currentPage - 1) * rowsPerPage &&
             index < currentPage * rowsPerPage)
            ? "" : "none";
    });

    document.getElementById("pageInfo").innerText =
        `Página ${currentPage} de ${totalPages}`;
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

/* =========================
   EXPORT EXCEL
========================= */
function exportExcel() {
    let table = document.getElementById("tabela").outerHTML;
    let blob = new Blob([table], { type: "application/vnd.ms-excel" });
    let url = URL.createObjectURL(blob);

    let a = document.createElement("a");
    a.href = url;
    a.download = "inventario.xls";
    a.click();
}

/* =========================
   EXPORT PDF (PRINT)
========================= */
function exportPDF() {
    window.print();
}
</script>

</body>
</html>