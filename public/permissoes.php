<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../helpers/voltar_menu.php';

/* =========================
   SEGURANÇA
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

requireRole(['admin']);

$pdo = Database::conectar();

/* =========================
   VOLTAR MENU
========================= */
$nivelUser = $_SESSION['nivel_acesso'] ?? '';
$voltar = voltarMenu($nivelUser);

/* =========================
   SALVAR PERMISSÕES
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nivel  = trim($_POST['nivel'] ?? '');
    $modulo = trim($_POST['modulo'] ?? '');

    $ver       = isset($_POST['ver']) ? 1 : 0;
    $criar     = isset($_POST['criar']) ? 1 : 0;
    $editar    = isset($_POST['editar']) ? 1 : 0;
    $eliminar  = isset($_POST['eliminar']) ? 1 : 0;

    if ($nivel !== '' && $modulo !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO permissoes (nivel, modulo, pode_ver, pode_criar, pode_editar, pode_eliminar)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                pode_ver = VALUES(pode_ver),
                pode_criar = VALUES(pode_criar),
                pode_editar = VALUES(pode_editar),
                pode_eliminar = VALUES(pode_eliminar)
        ");

        $stmt->execute([$nivel, $modulo, $ver, $criar, $editar, $eliminar]);
    }
}

/* =========================
   ELIMINAR PERMISSÃO
========================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("SELECT nivel FROM permissoes WHERE id = ?");
    $stmt->execute([$id]);
    $nivelAlvo = $stmt->fetchColumn();

    // proteção crítica
    if ($nivelAlvo === 'admin') {
        die("❌ Não é permitido eliminar permissões do ADMIN.");
    }

    $stmt = $pdo->prepare("DELETE FROM permissoes WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: permissoes.php");
    exit;
}

/* =========================
   LISTAR PERMISSÕES
========================= */
$permissoes = $pdo->query("
    SELECT * FROM permissoes ORDER BY nivel, modulo
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Permissões ERP</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background:#f4f6f9;
}

.card {
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.table td, .table th {
    vertical-align: middle;
}
</style>
</head>

<body class="container py-4">

<div class="card p-4">

<h2 class="mb-4">🔐 Gestão de Permissões ERP</h2>

<!-- FORMULÁRIO -->
<form method="POST" class="row g-2 mb-4">

    <div class="col-md-3">
        <input name="nivel" class="form-control" placeholder="Nível (admin, gerente...)" required>
    </div>

    <div class="col-md-3">
        <input name="modulo" class="form-control" placeholder="Módulo (vendas, stock...)" required>
    </div>

    <div class="col-md-2">
        <label><input type="checkbox" name="ver"> Ver</label>
    </div>

    <div class="col-md-2">
        <label><input type="checkbox" name="criar"> Criar</label>
    </div>

    <div class="col-md-2">
        <label><input type="checkbox" name="editar"> Editar</label>
    </div>

    <div class="col-md-2">
        <label><input type="checkbox" name="eliminar"> Eliminar</label>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary w-100">💾 Guardar Permissão</button>
    </div>

</form>

<!-- TABELA -->
<table class="table table-bordered table-striped">

<thead class="table-dark">
<tr>
    <th>Nível</th>
    <th>Módulo</th>
    <th>Ver</th>
    <th>Criar</th>
    <th>Editar</th>
    <th>Eliminar</th>
    <th>Ação</th>
</tr>
</thead>

<tbody>

<?php foreach ($permissoes as $p): ?>
<tr>
    <td><?= htmlspecialchars($p['nivel']) ?></td>
    <td><?= htmlspecialchars($p['modulo']) ?></td>

    <td><?= $p['pode_ver'] ? '✔' : '❌' ?></td>
    <td><?= $p['pode_criar'] ? '✔' : '❌' ?></td>
    <td><?= $p['pode_editar'] ? '✔' : '❌' ?></td>
    <td><?= $p['pode_eliminar'] ? '✔' : '❌' ?></td>

    <td>
        <a href="?delete=<?= $p['id'] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Eliminar esta permissão?')">
           🗑 Eliminar
        </a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<!-- VOLTAR -->
<div class="text-center mt-4">
    <a href="<?= $voltar ?>" class="btn btn-outline-secondary">
        ← Voltar ao Menu
    </a>
</div>

</div>

</body>
</html>