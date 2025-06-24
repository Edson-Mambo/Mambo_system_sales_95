<?php
session_start();

require_once '../../config/database.php';

$pdo = Database::conectar();

// Verificar permissão
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'gerente')) {
    header("Location: ../login.php");
    exit();
}

// Buscar todos os logs ordenados por data_hora desc (limite 1000 para evitar sobrecarga)
$sql = "SELECT * FROM logs_sistema ORDER BY data_hora DESC LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Relatório de Logs do Sistema</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">📋 Relatório Completo de Logs do Sistema</h2>

    <?php if (count($logs) === 0): ?>
        <div class="alert alert-warning">Nenhum log encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Data e Hora</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                            <td><?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></td>
                            <td><?= htmlspecialchars($log['tipo_log']) ?></td>
                            <td><?= htmlspecialchars($log['descricao']) ?></td>
                            <td><?= htmlspecialchars($log['ip_usuario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <a href="configuracoes.php" class="btn btn-secondary mb-3">← Voltar</a>

</div>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
