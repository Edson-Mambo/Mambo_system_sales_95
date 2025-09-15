<?php
// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 


// Inicia sessão e verifica se o usuário é admin (opcional)


require_once '../config/database.php';
include 'helpers/voltar_menu.php'; 

// Criar a conexão PDO
$pdo = Database::conectar();

// Verifica se a conexão foi bem-sucedida
if (!$pdo) {
    die("Erro ao conectar ao banco de dados.");
}

// Executar a consulta com JOIN
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
    <h2 class="mb-4">Relatório de Logins</h2>

    <div class="text-center mt-4">
            <a href="voltar.php" class="btn btn-secondary">← Voltar ao Painel</a>
        </div>
<br>

    <?php if (count($logins) > 0): ?>
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
                <td><?= $log['logout_time'] ? htmlspecialchars($log['logout_time']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-warning">Nenhum registro de login encontrado.</div>
    <?php endif; ?>

    <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
