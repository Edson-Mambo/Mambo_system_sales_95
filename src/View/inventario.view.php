<?php
require_once '../../config/database.php';
$pdo = Database::conectar();

// SQL para puxar produtos com quantidades vendidas até hoje
$sql = "SELECT
        p.id,
        p.nome,
        p.codigo_barra,
        p.preco,
        p.quantidade AS estoque_atual,
        COALESCE(SUM(pv.quantidade), 0) AS quantidade_vendida,
        (p.quantidade + COALESCE(SUM(pv.quantidade), 0)) AS total_inicial
    FROM produtos p
    LEFT JOIN produtos_vendidos pv ON pv.produto_id = p.id
    LEFT JOIN vendas v ON v.id = pv.venda_id AND v.data_venda <= CURDATE()
    GROUP BY p.id, p.nome, p.codigo_barra, p.preco, p.quantidade
    ORDER BY p.nome ASC";

$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>Inventário com Vendas até Hoje</title>
  <link rel="stylesheet" href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" />
  <style>
    body {
      background-color: #f4f6f9;
    }
    .container {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    .low-stock {
      background-color: #fff3cd !important;
    }
    .table thead th {
      background-color: #343a40;
      color: #fff;
    }
    .table td, .table th {
      vertical-align: middle;
      text-align: center;
    }
  </style>
</head>
<body class="p-4">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">📦 Inventário - Mambo System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarInventario" aria-controls="navbarInventario" aria-expanded="false" aria-label="Alternar navegação">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarInventario">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="inventario.view.php">Inventário</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../public/inventario_fisico.php">Lançar Inventário Físico</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../public/comparar_inventario.php">Comparar Inventário</a>
        </li>
        
      </ul>
      <div class="d-flex">
        <a href="../../public/logout.php" class="btn btn-danger btn-lg">Terminar Sessão</a>
      </div>
    </div>
  </div>
</nav>


<div class="container">
  <h2 class="mb-4"><span class="text-primary">📋</span> Inventário e Vendas até Hoje (<?= date('d/m/Y') ?>)</h2>

  <div class="table-responsive">
    <table class="table table-hover table-bordered">
      <thead class="table-dark">
        <tr>
          <th>Produto</th>
          <th>Código de Barras</th>
          <th>Preço (MT)</th>
          <th>Estoque Atual</th>
          <th>Quantidade Vendida</th>
          <th>Total Inicial</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($produtos)): ?>
          <?php foreach ($produtos as $produto): ?>
            <?php
              $classeEstoque = ($produto['estoque_atual'] <= 5) ? 'low-stock' : '';
            ?>
            <tr class="<?= $classeEstoque ?>">
              <td><?= htmlspecialchars($produto['nome']) ?></td>
              <td><?= htmlspecialchars($produto['codigo_barra']) ?></td>
              <td>MT <?= number_format($produto['preco'], 2, ',', '.') ?></td>
              <td><?= (int)$produto['estoque_atual'] ?></td>
              <td><?= (int)$produto['quantidade_vendida'] ?></td>
              <td><?= (int)$produto['total_inicial'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
