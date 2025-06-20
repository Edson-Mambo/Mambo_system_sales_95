<?php
require_once '../config/database.php';
$pdo = Database::conectar();

$termo = $_GET['busca'] ?? '';
$vales = [];

if ($termo !== '') {
    $stmt = $pdo->prepare("SELECT * FROM vales WHERE numero_vale LIKE ? OR cliente_nome LIKE ? OR cliente_telefone LIKE ? ORDER BY criado_em DESC");
    $like = "%$termo%";
    $stmt->execute([$like, $like, $like]);
    $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Buscar Vale</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2>🔍 Buscar Vales</h2>
        <form method="get" class="mb-4">
            <input type="text" name="busca" class="form-control" placeholder="Buscar por número, nome ou telefone" value="<?= htmlspecialchars($termo) ?>" />
        </form>

        <?php if (!empty($vales)): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nº Vale</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $vale): ?>
                        <tr>
                            <td><?= htmlspecialchars($vale['numero_vale']) ?></td>
                            <td><?= htmlspecialchars($vale['cliente_nome']) ?></td>
                            <td><?= htmlspecialchars($vale['cliente_telefone']) ?></td>
                            <td>MT <?= number_format($vale['total'], 2, ',', '.') ?></td>
                            <td><span class="badge bg-<?= $vale['status'] === 'finalizado' ? 'success' : 'warning' ?>"><?= $vale['status'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($vale['criado_em'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($termo !== ''): ?>
            <div class="alert alert-info">Nenhum vale encontrado.</div>
        <?php endif; ?>
    </div>
</body>
</html>
