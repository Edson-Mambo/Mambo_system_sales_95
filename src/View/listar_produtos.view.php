<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';


/* =========================
   VERIFICA LOGIN
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

/* =========================
   NÍVEL CORRETO (AJUSTADO AO TEU SISTEMA REAL)
========================= */
$nivel = $_SESSION['nivel_acesso'] ?? '';

$permitidos = ['admin', 'gerente', 'supervisor'];

$pdo = Database::conectar();

/* CATEGORIAS */
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome")
                  ->fetchAll(PDO::FETCH_ASSOC);

$categoriaSelecionada = $_GET['categoria'] ?? 'todos';

/* PRODUTOS */
if ($categoriaSelecionada === 'todos') {

    $stmt = $pdo->query("
        SELECT p.*, c.nome AS categoria_nome
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.nome ASC
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS categoria_nome
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE c.nome = :cat
        ORDER BY p.nome ASC
    ");

    $stmt->execute(['cat' => $categoriaSelecionada]);
}

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rotas = require __DIR__ . '/../../config/routes.php';

$voltar = $rotas[$_SESSION['nivel_acesso']] ?? '/public/index.php';

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
<title>ListarProdutos</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#eef2f7; }

.sidebar {
    width:220px;
    position:fixed;
    height:100vh;
    background:#111827;
    color:#fff;
    padding:15px;
}

.sidebar a {
    display:block;
    padding:10px;
    color:#cbd5e1;
    text-decoration:none;
    border-radius:6px;
}

.sidebar a:hover,
.sidebar a.active {
    background:#2563eb;
    color:#fff;
}

.main {
    margin-left:220px;
    padding:20px;
}

.header {
    background:#fff;
    padding:15px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.table-box {
    margin-top:15px;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    max-height:70vh;
    overflow-y:auto;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

thead th {
    position:sticky;
    top:0;
    background:#1f2937 !important;
    color:#fff;
}

.low { color:#dc2626; font-weight:bold; }
.ok { color:#16a34a; font-weight:bold; }

mark { background:#fde047; padding:0; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h5>Categorias</h5>

    <a href="?categoria=todos"
       class="<?= $categoriaSelecionada === 'todos' ? 'active' : '' ?>">
       Todos
    </a>

    <?php foreach ($categorias as $c): ?>
        <a href="?categoria=<?= urlencode($c['nome']) ?>"
           class="<?= $categoriaSelecionada === $c['nome'] ? 'active' : '' ?>">
            <?= htmlspecialchars($c['nome']) ?>
        </a>
    <?php endforeach; ?>

    <a href="<?= $voltar ?>" style="margin-top:20px;">← Voltar</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="header">

    <div>
        <h4 class="mb-0">📦 Produtos ERP</h4>
        <small id="counter"><?= count($produtos) ?> produtos</small>
    </div>

    <div class="d-flex gap-2">
        <input type="text" id="search" class="form-control" placeholder="🔎 Pesquisar...">
        <button class="btn btn-success btn-sm" onclick="exportExcel()">Excel</button>
        <button class="btn btn-danger btn-sm" onclick="window.print()">PDF</button>
    </div>

</div>

<div class="table-box">

<table class="table table-hover mb-0" id="table">

<thead>
<tr>
    <th>ID</th>
    <th>Categoria</th>
    <th>Código</th>
    <th>Nome</th>
    <th>Preço</th>
    <th>Stock</th>
</tr>
</thead>

<tbody>

<?php foreach ($produtos as $p): ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['categoria_nome'] ?? '-') ?></td>
    <td><?= htmlspecialchars($p['codigo_barra']) ?></td>
    <td><?= htmlspecialchars($p['nome']) ?></td>
    <td>MT <?= number_format($p['preco'],2,',','.') ?></td>
    <td class="<?= $p['estoque'] <= 5 ? 'low' : 'ok' ?>">
        <?= $p['estoque'] ?>
    </td>
</tr>
<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<script>
/* SEARCH LIMPO (SEM BUGS DE HTML) */
document.getElementById("search").addEventListener("input", function () {

    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#table tbody tr");

    let visible = 0;

    rows.forEach(row => {
        const match = row.innerText.toLowerCase().includes(value);
        row.style.display = match ? "" : "none";
        if (match) visible++;
    });

    document.getElementById("counter").innerText = visible + " produtos";
});

/* EXPORT SIMPLES */
function exportExcel() {
    let table = document.getElementById("table").outerHTML;
    let blob = new Blob([table], {type:'application/vnd.ms-excel'});
    let url = URL.createObjectURL(blob);

    let a = document.createElement('a');
    a.href = url;
    a.download = 'produtos.xls';
    a.click();
}
</script>

</body>
</html>