<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

// Filtro de status (opcional)
$filtro_status = $_GET['status'] ?? '';

// Consulta base com JOIN para buscar todos dados do cliente
$sql = "
    SELECT 
        v.id,
        v.numero_vale,
        v.valor_total,
        v.valor_pago,
        v.saldo,
        v.status,
        v.data_registro,
        c.nome,
        c.apelido,
        c.telefone,
        c.telefone_alt,
        c.email,
        c.morada
    FROM vales v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE 1
";

$params = [];

if (!empty($filtro_status)) {
    $sql .= " AND v.status = ?";
    $params[] = $filtro_status;
}

$sql .= " ORDER BY v.data_registro DESC";

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
<body class="bg-light p-4">

<div class="container">
    <div class="mb-3">
        <a href="vales.php" class="btn btn-secondary">← Novo Vale</a>
    </div>

    <h2 class="mb-4">📑 Relatório de Vales</h2>

    <form class="row g-3 mb-4" method="GET">
        <div class="col-auto">
            <label for="status" class="form-label">Filtrar Status:</label>
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
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Tel. Alternativo</th>
                        <th>Email</th>
                        <th>Morada</th>
                        <th>Total (MT)</th>
                        <th>Pago (MT)</th>
                        <th>Saldo (MT)</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $i => $vale): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($vale['numero_vale']) ?></td>
                            <td><?= htmlspecialchars($vale['nome']) ?> <?= htmlspecialchars($vale['apelido']) ?></td>
                            <td><?= htmlspecialchars($vale['telefone']) ?></td>
                            <td><?= htmlspecialchars($vale['telefone_alt']) ?></td>
                            <td><?= htmlspecialchars($vale['email']) ?></td>
                            <td><?= htmlspecialchars($vale['morada']) ?></td>
                            <td><?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['saldo'], 2, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $vale['status'] === 'pago' ? 'success' :
                                    ($vale['status'] === 'parcelado' ? 'warning' : 'danger') 
                                ?>">
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
