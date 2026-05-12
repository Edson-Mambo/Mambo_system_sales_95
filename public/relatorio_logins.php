<?php
// =========================
// ERROS (DEV ONLY)
// =========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================
// DEPENDÊNCIAS (ERP SAFE)
// =========================
require_once __DIR__ . '/../config/database.php';

$helperPath = __DIR__ . '/../helpers/voltar_menu.php';
if (file_exists($helperPath)) {
    require_once $helperPath;
}

// =========================
// PDO
// =========================
$pdo = Database::conectar();

if (!$pdo) {
    die("Erro ao conectar ao banco de dados.");
}

// =========================
// QUERY LOGS
// =========================
try {
    $sql = "
        SELECT l.*, u.nome 
        FROM logs_login l 
        JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.login_time DESC
    ";

    $stmt = $pdo->query($sql);
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Logins</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-5">

    <h2 class="mb-4">📊 Relatório de Logins</h2>

    <!-- BOTÃO VOLTAR ERP (CORRIGIDO) -->
    <div class="mb-3">
        <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '../public/index.php') ?>"
           class="btn btn-secondary">
            ← Voltar ao Painel
        </a>
    </div>

    <!-- TABELA -->
    <?php if (!empty($logins)): ?>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Usuário</th>
                    <th>Login</th>
                    <th>Logout</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($logins as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['nome']) ?></td>
                        <td><?= htmlspecialchars($log['login_time']) ?></td>
                        <td>
                            <?= !empty($log['logout_time'])
                                ? htmlspecialchars($log['logout_time'])
                                : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div class="alert alert-warning">
            Nenhum registro de login encontrado.
        </div>
    <?php endif; ?>

</body>

<script>
function loadAlerts() {
    fetch('/api/alerts.php')
        .then(r => r.json())
        .then(data => {
            console.log(data);
        });
}

setInterval(loadAlerts, 10000);
loadAlerts();
</script>

</html>