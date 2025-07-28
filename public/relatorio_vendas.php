<?php
require_once '../config/database.php';
$pdo = Database::conectar();
include 'helpers/voltar_menu.php';

// Processa filtro de datas
$data_inicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : null;
$data_final = isset($_GET['data_final']) ? $_GET['data_final'] : null;

// Busca categorias
$sqlCategorias = "SELECT id, nome FROM categorias ORDER BY nome ASC";
$stmtCat = $pdo->query($sqlCategorias);
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$relatorio = [];

// Para cada categoria, buscar vendas filtradas
foreach ($categorias as $categoria) {
    $sql = "
    SELECT 
        DATE(v.data_venda) AS data,
        p.nome AS nome_produto,
        SUM(pv.quantidade) AS total_quantidade,
        SUM(pv.quantidade * pv.preco_unitario) AS total_valor
    FROM produtos_vendidos pv
    JOIN produtos p ON pv.produto_id = p.id
    JOIN vendas v ON pv.venda_id = v.id
    WHERE p.categoria_id = ?
    ";

    $params = [$categoria['id']];

    if ($data_inicial) {
        $sql .= " AND DATE(v.data_venda) >= ? ";
        $params[] = $data_inicial;
    }
    if ($data_final) {
        $sql .= " AND DATE(v.data_venda) <= ? ";
        $params[] = $data_final;
    }

    $sql .= " GROUP BY data, p.id ORDER BY data DESC, total_valor DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa por data
    $agrupado = [];
    foreach ($dados as $linha) {
        $data = $linha['data'];
        $agrupado[$data][] = $linha;
    }

    $relatorio[] = [
        'id' => $categoria['id'],
        'categoria' => $categoria['nome'],
        'dados' => $agrupado
    ];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Dashboard com Sidebar e Filtro de Data</title>
  <link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
  <style>
    body { overflow-x: hidden; }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 220px;
      background: #343a40;
      padding-top: 60px;
    }
    .sidebar a {
      color: #fff;
      display: block;
      padding: 10px 20px;
      text-decoration: none;
    }
    .sidebar a:hover {
      background: #495057;
    }
    .content {
      margin-left: 240px;
      padding: 20px;
    }
    .filtro-data {
      margin-bottom: 20px;
    }
  </style>
</head>
<body class="bg-light">

<!-- Sidebar -->
<div class="sidebar">
  <h5 class="text-center text-white">📁 Categorias</h5>
  <?php foreach ($relatorio as $cat): ?>
    <a href="#cat-<?= $cat['id'] ?>"><?= htmlspecialchars($cat['categoria']) ?></a>
  <?php endforeach; ?>
</div>

<!-- Conteúdo -->
<div class="content">
  <h2 class="mb-4">📊 Dashboard de Vendas</h2>

  <!-- Filtro por Data -->
  <form method="GET" class="row g-3 filtro-data">
    <div class="col-md-3">
      <label for="data_inicial" class="form-label">Data Inicial</label>
      <input type="date" name="data_inicial" id="data_inicial" value="<?= htmlspecialchars($data_inicial) ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label for="data_final" class="form-label">Data Final</label>
      <input type="date" name="data_final" id="data_final" value="<?= htmlspecialchars($data_final) ?>" class="form-control">
    </div>
    <div class="col-md-3 align-self-end">
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <a href="?" class="btn btn-secondary">Limpar</a>
    </div>
  </form>

  <?php foreach ($relatorio as $cat): ?>
    <div id="cat-<?= $cat['id'] ?>" class="mb-5">
      <h4 class="bg-primary text-white p-2 rounded"><?= htmlspecialchars($cat['categoria']) ?></h4>

      <?php if (!empty($cat['dados'])): ?>
        <?php foreach ($cat['dados'] as $data => $produtos): ?>
          <div class="card mb-3 shadow-sm">
            <div class="card-header bg-dark text-white">
              <h6 class="mb-0">Data: <?= date('d/m/Y', strtotime($data)) ?></h6>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Valor Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                    $total_dia = 0;
                    foreach ($produtos as $p):
                      $total_dia += $p['total_valor'];
                  ?>
                    <tr>
                      <td><?= htmlspecialchars($p['nome_produto']) ?></td>
                      <td><?= $p['total_quantidade'] ?></td>
                      <td><?= number_format($p['total_valor'], 2, ',', '.') ?> MZN</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr class="table-secondary">
                    <td colspan="2" class="text-end"><strong>Total do Dia:</strong></td>
                    <td><strong><?= number_format($total_dia, 2, ',', '.') ?> MZN</strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-warning">Sem vendas para este período.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>


  <!-- Botão Voltar ao Topo -->
<button id="btnTopo" class="btn btn-primary" style="
  position: fixed;
  bottom: 40px;
  right: 30px;
  display: none;
  z-index: 999;
">
  ↑ Topo
</button>

<script>
  // Mostrar ou esconder botão
  window.onscroll = function() {
    const btn = document.getElementById("btnTopo");
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      btn.style.display = "block";
    } else {
      btn.style.display = "none";
    }
  };

  // Ao clicar, rolar suavemente pro topo
  document.getElementById("btnTopo").onclick = function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };
</script>



  <div class="text-center">
    <a href="voltar.php" class="btn btn-secondary">← Voltar</a>
  </div>
</div>

</body>
</html>
