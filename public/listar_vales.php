<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

// Buscar vales não pagos
$stmt = $pdo->prepare("
  SELECT v.id, c.nome AS cliente_nome, v.valor_total, COALESCE(v.valor_pago,0) AS valor_pago, 
         COALESCE(v.saldo,0) AS saldo, v.status, v.data_registro AS data_criacao
  FROM vales v
  LEFT JOIN clientes c ON v.cliente_id = c.id
  WHERE v.status != 'pago'
  ORDER BY v.data_registro DESC
");
$stmt->execute();
$vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Vales Não Finalizados</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
<div class="container">
  <h2 class="mb-4">Vales Não Finalizados</h2>

  <?php if (empty($vales)): ?>
    <div class="alert alert-info">Nenhum vale não finalizado encontrado.</div>
  <?php else: ?>
    <form method="post" action="venda_vale.php">
      <input type="hidden" name="carregar_vale" value="1">
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
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vales as $vale): ?>
            <?php
              $cor = match ($vale['status']) {
                'nenhum' => 'text-danger fw-bold',
                'parcelado' => 'text-warning fw-bold',
                default => 'text-secondary fw-bold'
              };
            ?>
            <tr>
              <td class="text-center">
                <input type="radio" name="id_vale" value="<?= (int)$vale['id'] ?>" required>
              </td>
              <td><?= (int)$vale['id'] ?></td>
              <td><?= htmlspecialchars($vale['cliente_nome'] ?? 'Sem Cliente') ?></td>
              <td>MT <?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
              <td>MT <?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
              <td>MT <?= number_format($vale['saldo'], 2, ',', '.') ?></td>
              <td class="<?= $cor ?>"><?= htmlspecialchars($vale['status']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($vale['data_criacao'])) ?></td>
              <td>
                <a href="visualizar_vale.php?id=<?= (int)$vale['id'] ?>" class="btn btn-info btn-sm">
                  Visualizar Vale
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="d-grid col-md-4 mx-auto mt-3">
        <button type="submit" class="btn btn-primary btn-lg">Carregar Vale Selecionado</button>
      </div>
    </form>
  <?php endif; ?>
</div>
<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
