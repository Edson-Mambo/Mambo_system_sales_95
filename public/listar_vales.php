<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

// Buscar vales com status diferente de 'pago' (não finalizados)
$stmt = $pdo->prepare("
    SELECT 
      v.id, 
      c.nome AS cliente_nome, 
      v.valor_total, 
      v.valor_pago, 
      v.saldo, 
      v.status, 
      v.data_criacao
    FROM vales v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.status != 'pago'
    ORDER BY v.data_criacao DESC
");
$stmt->execute();
$vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Vales Não Finalizados</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">Vales Não Finalizados</h2>

    <?php if (empty($vales)): ?>
        <div class="alert alert-info">Nenhum vale não finalizado encontrado.</div>
    <?php else: ?>
    <form method="post" action="vales.php">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Selecionar</th>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Valor Total</th>
                        <th>Valor Pago</th>
                        <th>Saldo</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $vale): ?>
                        <?php
                            $corStatus = '';
                            if ($vale['status'] === 'nenhum') {
                                $corStatus = 'text-danger fw-bold';
                            } elseif ($vale['status'] === 'parcelado') {
                                $corStatus = 'text-warning fw-bold';
                            } else {
                                $corStatus = 'text-secondary fw-bold';
                            }
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="radio" name="id_vale" value="<?= $vale['id'] ?>" required>
                            </td>
                            <td><?= $vale['id'] ?></td>
                            <td><?= htmlspecialchars($vale['cliente_nome'] ?? 'Sem Cliente') ?></td>
                            <td>MT <?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
                            <td>MT <?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
                            <td>MT <?= number_format($vale['saldo'], 2, ',', '.') ?></td>
                            <td class="<?= $corStatus ?> text-capitalize"><?= htmlspecialchars($vale['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($vale['data_criacao'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-grid col-md-4 mx-auto">
            <button type="submit" class="btn btn-primary">Carregar Vale Selecionado</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
