<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

// Captura os filtros da busca
$nome = trim($_GET['cliente_nome'] ?? '');
$telefone = trim($_GET['cliente_telefone'] ?? '');

// Monta a base da query
$sql = "SELECT * FROM vales WHERE 1=1";
$params = [];

if ($nome !== '') {
    $sql .= " AND cliente_nome LIKE ?";
    $params[] = "%$nome%";
}

if ($telefone !== '') {
    $sql .= " AND cliente_telefone LIKE ?";
    $params[] = "%$telefone%";
}

$sql .= " ORDER BY data_registro DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Listar Vales</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">Listagem de Vales</h2>

    <!-- Formulário de busca -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-5">
            <input type="text" name="cliente_nome" value="<?= htmlspecialchars($nome) ?>" class="form-control" placeholder="Nome do Cliente">
        </div>
        <div class="col-md-4">
            <input type="text" name="cliente_telefone" value="<?= htmlspecialchars($telefone) ?>" class="form-control" placeholder="Telefone do Cliente">
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <?php if (count($vales) === 0): ?>
        <div class="alert alert-info">Nenhum vale encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nº do Vale</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Total (MT)</th>
                        <th>Pago (MT)</th>
                        <th>Saldo</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $i => $vale): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($vale['numero_vale']) ?></td>
                            <td><?= htmlspecialchars($vale['cliente_nome']) ?></td>
                            <td><?= htmlspecialchars($vale['cliente_telefone']) ?></td>
                            <td><?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['saldo'], 2, ',', '.') ?></td>
                            <td><?= ucfirst($vale['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
