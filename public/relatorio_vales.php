<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$filtro_status = $_GET['status'] ?? '';

$sql = "SELECT * FROM vales WHERE 1";
$params = [];

if (!empty($filtro_status)) {
    $sql .= " AND status = ?";
    $params[] = $filtro_status;
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
    <title>Relatório de Vales</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">

    <!-- Botão Voltar -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>

    <h2 class="mb-4">📋 Relatório de Vales</h2>

    <form class="row g-3 mb-4" method="GET">
        <div class="col-auto">
            <label for="status" class="form-label">Filtrar por Status:</label>
            <select name="status" id="status" class="form-select">
                <option value="">-- Todos --</option>
                <option value="aberto" <?= $filtro_status === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                <option value="parcelado" <?= $filtro_status === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                <option value="pago" <?= $filtro_status === 'pago' ? 'selected' : '' ?>>Pago</option>
            </select>
        </div>
        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
        </div>
    </form>

    <?php if (count($vales) === 0): ?>
        <div class="alert alert-warning">Nenhum vale encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Número do Vale</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Total (MT)</th>
                        <th>Pago (MT)</th>
                        <th>Saldo (MT)</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th> <!-- Coluna para botão -->
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
                            <td>
                                <span class="badge bg-<?= $vale['status'] === 'pago' ? 'success' : ($vale['status'] === 'parcelado' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($vale['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></td>
                            <td>
                                <a href="imprimir_vale.php?id=<?= $vale['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    🖨️ Imprimir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
