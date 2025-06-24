<?php
require_once '../config/database.php';

$pdo = Database::conectar();

// Categorias disponíveis
$categorias = ['Todos', 'Bebidas', 'Food', 'Limpeza', 'Snacks', 'Congelados', 'Outros'];
$categoriaSelecionada = $_GET['categoria'] ?? 'Todos';
$soDiferenca = isset($_GET['so_diferenca']) && $_GET['so_diferenca'] == '1';

// Montar SQL
$sql = "
SELECT
    p.id,
    p.nome,
    p.codigo_barra,
    p.quantidade AS estoque_sistema,
    IFNULL(f.quantidade_fisica, 0) AS estoque_fisico,
    (IFNULL(f.quantidade_fisica, 0) - p.quantidade) AS diferenca,
    c.nome AS categoria
FROM produtos p
LEFT JOIN categorias c ON c.id = p.categoria_id
LEFT JOIN (
    SELECT produto_id, quantidade_fisica
    FROM inventario_fisico
    WHERE (produto_id, data_registro) IN (
        SELECT produto_id, MAX(data_registro)
        FROM inventario_fisico
        GROUP BY produto_id
    )
) f ON f.produto_id = p.id
";

$where = [];
$params = [];

if ($categoriaSelecionada !== 'Todos') {
    $where[] = 'c.nome = :categoria';
    $params[':categoria'] = $categoriaSelecionada;
}

if ($soDiferenca) {
    $where[] = '(IFNULL(f.quantidade_fisica, 0) - p.quantidade) != 0';
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY c.nome ASC, p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>📊 Comparar Inventário - Mambo System</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css" />
  <link href="../node_modules/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f4f6f9;
      margin: 0;
    }
    .wrapper {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 220px;
      background-color: #343a40;
      color: #fff;
      padding: 20px;
    }
    .sidebar h3 {
      text-align: center;
      margin-bottom: 20px;
      color: #0d6efd;
    }
    .sidebar nav a {
      display: block;
      color: #adb5bd;
      text-decoration: none;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 6px;
      font-weight: 600;
    }
    .sidebar nav a.active, .sidebar nav a:hover {
      background-color: #0d6efd;
      color: #fff;
    }
    .content {
      flex-grow: 1;
      padding: 30px;
      background: #fff;
    }
    h1 {
      color: #0d6efd;
      margin-bottom: 20px;
    }
    .btn-voltar {
      margin-top: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    table thead th {
      background-color: #343a40;
      color: #fff;
      text-align: center;
      padding: 12px;
    }
    table tbody td {
      text-align: center;
      padding: 10px;
      border: 1px solid #dee2e6;
    }
    tbody tr.diferenca-positiva {
      background-color: #d1e7dd;
    }
    tbody tr.diferenca-negativa {
      background-color: #f8d7da;
    }
    tbody tr.diferenca-zero {
      background-color: #fff;
    }
    @media (max-width: 768px) {
      .wrapper {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        display: flex;
        overflow-x: auto;
      }
      .sidebar nav {
        display: flex;
        flex-direction: row;
        gap: 10px;
      }
      .sidebar nav a {
        flex: 1;
        padding: 10px;
        text-align: center;
      }
      .content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <aside class="sidebar">
      <h3>Categorias</h3>
      <nav>
        <?php foreach ($categorias as $categoria): ?>
          <a href="?categoria=<?= urlencode($categoria) ?>&so_diferenca=<?= $soDiferenca ? '1' : '0' ?>"
             class="<?= $categoria === $categoriaSelecionada ? 'active' : '' ?>">
            <?= htmlspecialchars($categoria) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <section class="content">
      <a href="../src/View/inventario.view.php" class="btn btn-outline-secondary mb-3">← Voltar para Visualizar Inventário</a>

      <h1>📊 Comparar Inventário</h1>

      <div class="d-flex gap-2 flex-wrap mb-3">
        <a href="?categoria=<?= urlencode($categoriaSelecionada) ?>&so_diferenca=<?= $soDiferenca ? '0' : '1' ?>"
           class="btn btn-<?= $soDiferenca ? 'secondary' : 'warning' ?>">
          <?= $soDiferenca ? '👁️ Ver Todos' : '❗ Mostrar com Diferença' ?>
        </a>

        <button onclick="exportTableToExcel('tabelaInventario', 'comparativo_inventario')" class="btn btn-success">
          <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </button>
        <button onclick="exportTableToWord('tabelaInventario', 'comparativo_inventario')" class="btn btn-primary">
          <i class="bi bi-file-earmark-word"></i> Exportar Word
        </button>
      </div>

      <table id="tabelaInventario">
        <thead>
          <tr>
            <th>Categoria</th>
            <th>Nome</th>
            <th>Código</th>
            <th>Estoque Sistema</th>
            <th>Estoque Físico</th>
            <th>Diferença</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($produtos)): ?>
            <?php foreach ($produtos as $p):
              $classe = 'diferenca-zero';
              if ($p['diferenca'] > 0) $classe = 'diferenca-positiva';
              elseif ($p['diferenca'] < 0) $classe = 'diferenca-negativa';
            ?>
              <tr class="<?= $classe ?>">
                <td><?= htmlspecialchars($p['categoria'] ?? 'Sem Categoria') ?></td>
                <td><?= htmlspecialchars($p['nome']) ?></td>
                <td><?= htmlspecialchars($p['codigo_barra']) ?></td>
                <td><?= (int)$p['estoque_sistema'] ?></td>
                <td><?= (int)$p['estoque_fisico'] ?></td>
                <td><?= $p['diferenca'] ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <a href="index_admin.php" class="btn btn-outline-secondary btn-voltar">← Voltar ao Painel</a>
    </section>

  </div>

  <script>
  function exportTableToExcel(tableID, filename = '') {
    const table = document.getElementById(tableID);
    const html = table.outerHTML.replace(/ /g, '%20');
    const a = document.createElement('a');
    a.href = 'data:application/vnd.ms-excel,' + html;
    a.download = filename + '.xls';
    a.click();
  }

  function exportTableToWord(tableID, filename = '') {
    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' " +
                   "xmlns:w='urn:schemas-microsoft-com:office:word' " +
                   "xmlns='http://www.w3.org/TR/REC-html40'>" +
                   "<head><meta charset='utf-8'><title>Exportação</title></head><body>";
    const footer = "</body></html>";
    const table = document.getElementById(tableID).outerHTML;
    const html = header + table + footer;
    const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.doc';
    a.click();
  }
  </script>
</body>
</html>
