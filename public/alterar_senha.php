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
$nivelUser = $_SESSION['nivel'] ?? '';
$voltar = voltarMenu($nivelUser);

/* =========================
   MENSAGEM
========================= */
$mensagem = '';

/* =========================
   RESET SENHA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = intval($_POST['user_id'] ?? 0);
    $nova_senha = trim($_POST['nova_senha'] ?? '');

    if ($user_id > 0 && strlen($nova_senha) >= 4) {

        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET senha = ?, must_change_password = 1 
            WHERE id = ?
        ");

        $stmt->execute([$hash, $user_id]);

        $mensagem = "✔ Senha alterada com sucesso!";
    } else {
        $mensagem = "❌ Preencha corretamente os dados.";
    }
}

/* =========================
   LISTAR UTILIZADORES
========================= */
$usuarios = $pdo->query("
    SELECT id, nome, email, nivel 
    FROM usuarios 
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Alterar Senhas</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background:#f4f6f9;
}

.card {
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}
</style>
</head>

<body class="container py-4">

<div class="card p-4">

<h2>🔐 Reset de Senhas (Admin)</h2>

<!-- ALERTA -->
<?php if ($mensagem): ?>
    <div class="alert alert-info mt-3">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<!-- FORM -->
<form method="POST" class="row g-3 mt-3">

    <!-- USUÁRIO -->
    <div class="col-md-6">
        <label>Selecionar Utilizador</label>
        <select name="user_id" class="form-control" required>
            <option value="">-- escolher --</option>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id'] ?>">
                    <?= htmlspecialchars($u['nome']) ?> (<?= $u['nivel'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- NOVA SENHA -->
    <div class="col-md-6">
        <label>Nova Senha</label>
        <input type="text" name="nova_senha" class="form-control" required placeholder="Nova senha">
    </div>

    <div class="col-12">
        <button class="btn btn-danger w-100">
            🔑 Resetar Senha
        </button>
    </div>

</form>

<hr>

<!-- LISTA UTILIZADORES -->
<h5>👤 Utilizadores do Sistema</h5>

<table class="table table-bordered table-striped mt-2">

<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Nome</th>
    <th>Email</th>
    <th>Nível</th>
</tr>
</thead>

<tbody>
<?php foreach ($usuarios as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['nome']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= $u['nivel'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>

</table>

<!-- VOLTAR -->
<div class="text-center mt-3">
    <a href="<?= $voltar ?>" class="btn btn-outline-secondary">
        ← Voltar ao Menu
    </a>
</div>

</div>

</body>
</html>