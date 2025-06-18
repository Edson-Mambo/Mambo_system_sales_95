<?php
// Aqui vai sua conexão normal com o banco de dados
require_once '../../config/database.php';
$pdo = Database::conectar();
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>Lista de Produtos</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../node_modules/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
    }
    .table-container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }
    .btn-group {
      margin-bottom: 20px;
      gap: 10px;
    }
    .no-results {
      text-align: center;
      font-style: italic;
      color: #6c757d;
      padding: 15px 0;
    }
  </style>
</head>
<body>

<div class="btn-group d-flex flex-wrap">
  <a href="../../public/index_admin.php" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
  <button onclick="window.print()" class="btn btn-dark">
    <i class="bi bi-printer"></i> Imprimir
  </button>
  <button onclick="exportTableToExcel('productTable', 'produtos')" class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
  </button>
  <button onclick="exportTableToWord('productTable', 'produtos')" class="btn btn-primary">
    <i class="bi bi-file-earmark-word"></i> Exportar Word
  </button>
</div>


  <div class="table-container">
    <div class="table-responsive">
      <table class="table table-bordered" id="productTable">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Código de Barras</th>
            <th>Nome</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th class="text-center">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($produtos)): ?>
            <tr>
              <td colspan="6" class="no-results">Nenhum produto encontrado.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($produtos as $produto): ?>
              <tr>
                <td><?= $produto['id'] ?></td>
                <td><?= htmlspecialchars($produto['codigo_barra']) ?></td>
                <td><?= htmlspecialchars($produto['nome']) ?></td>
                <td><?= number_format($produto['preco'], 2, ',', '.') ?> MZN</td>
                <td><?= $produto['quantidade'] ?></td>
                <td class="text-center">
                  <a href="../../public/editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-primary" title="Editar Produto">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
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

  const blob = new Blob(['\ufeff', html], {
    type: 'application/msword'
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename + '.doc';
  a.click();
}
</script>

</body>
</html>
