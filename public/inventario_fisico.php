<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

$pdo = Database::conectar();

/* SEGURANÇA */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$nivel = $_SESSION['nivel_acesso'] ?? '';
$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos)) {
    header("Location: ../public/index.php");
    exit;
}

/* CATEGORIAS */
$categorias = ['Todos', 'Bebidas', 'Food', 'Limpeza', 'Snacks', 'Congelados', 'Outros'];
$categoriaSelecionada = $_GET['categoria'] ?? 'Todos';

/* PRODUTOS */
if ($categoriaSelecionada !== 'Todos') {
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome, p.codigo_barra, p.estoque, c.nome AS categoria
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE c.nome = ?
        ORDER BY p.nome
    ");
    $stmt->execute([$categoriaSelecionada]);
} else {
    $stmt = $pdo->query("
        SELECT p.id, p.nome, p.codigo_barra, p.estoque, c.nome AS categoria
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        ORDER BY c.nome, p.nome
    ");
}

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$voltar = "../src/View/inventario.view.php";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Inventário Físico</title>

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

input[type="number"] {
    width: 120px;
}
</style>
</head>

<body>

<div class="container-fluid py-3">

<!-- HEADER -->
<div class="page-header d-flex justify-content-between align-items-center mb-3">

    <div>
        <h4 class="mb-0">🧮 Inventário Físico</h4>
        <small class="text-muted">Contagem de stock em tempo real</small>
    </div>

    <a href="<?= $voltar ?>" class="btn btn-outline-secondary btn-sm">
        ← Voltar
    </a>

</div>

<!-- FILTRO -->
<div class="card card-erp mb-3">
    <div class="card-body">

        <form method="GET" class="d-flex gap-2 align-items-center">

            <label class="fw-bold">Categoria:</label>

            <select name="categoria" class="form-select form-select-sm" style="width:200px;">
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat ?>" <?= $categoriaSelecionada == $cat ? 'selected' : '' ?>>
                        <?= $cat ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-primary btn-sm">
                🔎 Filtrar
            </button>

        </form>

    </div>
</div>

<!-- TABELA -->
<div class="card card-erp">

<form method="post" action="salvar_inventario.php">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead>
<tr>
    <th>Categoria</th>
    <th>Produto</th>
    <th>Código</th>
    <th class="text-center">Sistema</th>
    <th class="text-center">Físico</th>
</tr>
</thead>

<tbody>

<?php foreach ($produtos as $p): ?>
<tr>

    <td><?= htmlspecialchars($p['categoria']) ?></td>

    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>

    <td><code><?= htmlspecialchars($p['codigo_barra']) ?></code></td>

    <td class="text-center"><?= (int)$p['estoque'] ?></td>

    <td class="text-center">
        <input type="number"
               name="produtos[<?= $p['id'] ?>][quantidade_fisica]"
               class="form-control form-control-sm"
               min="0"
               placeholder="0">

        <input type="hidden"
               name="produtos[<?= $p['id'] ?>][id]"
               value="<?= $p['id'] ?>">
    </td>

</tr>
<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="p-3 d-flex justify-content-end">

    <button class="btn btn-success">
        💾 Salvar Inventário
    </button>

</div>

</form>

</div>

</div>

</body>
</html>