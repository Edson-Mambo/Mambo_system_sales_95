<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

$pdo = Database::conectar();

/* =========================
   SEGURANÇA ERP
========================= */
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

/* =========================
   USUÁRIOS
========================= */
$stmt = $pdo->query("SELECT id, nome, email, nivel FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsuarios = count($usuarios);

/* =========================
   VOLTAR ERP
========================= */
$rotas = [
    'admin' => '../../public/index_admin.php',
    'gerente' => '../../public/index_gerente.php',
    'supervisor' => '../../public/index_supervisor.php'
];

$voltar = $rotas[$nivel] ?? '../../public/index.php';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Usuários</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #eef2f7;
}

/* HEADER ERP */
.header {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* CARD */
.card-erp {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* SEARCH */
#search {
    max-width: 300px;
}

/* TABLE */
.table thead {
    background: #1f2937;
    color: #fff;
}
</style>
</head>

<body>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="header">

        <div>
            <h4 class="mb-0">👥 Gestão de Usuários ERP</h4>
            <small><?= $totalUsuarios ?> usuários cadastrados</small>
        </div>

        <input type="text" id="search" class="form-control" placeholder="🔎 pesquisar usuário...">

    </div>

    <!-- TABELA -->
    <div class="card card-erp">

        <table class="table table-hover mb-0" id="tableUsers">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Nível</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($u['nivel']) ?>
                        </span>
                    </td>
                    <td>

                        <a href="../../public/editar_usuario.php?id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-primary">
                            ✏️ Editar
                        </a>

                        <a href="../../public/deletar_usuario.php?id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Eliminar este usuário?')">
                            🗑 Deletar
                        </a>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>

    </div>

    <!-- VOLTAR -->
    <div class="text-center mt-4">
        <a href="<?= $voltar ?>" class="btn btn-outline-secondary">
            ← Voltar ao Painel
        </a>
    </div>

</div>

<!-- SEARCH SCRIPT -->
<script>
document.getElementById("search").addEventListener("input", function () {

    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#tableUsers tbody tr");

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });

});
</script>

</body>
</html>