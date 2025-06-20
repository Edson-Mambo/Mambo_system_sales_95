<?php
require_once '../../config/database.php';
$pdo = Database::conectar();
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY nome ASC");
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
    background-color: #f4f6f9;
  }

  .navbar {
    padding: 15px 25px;
  }

  .navbar-brand {
    font-weight: bold;
    font-size: 1.4rem;
  }

  .action-buttons .btn {
    margin: 5px;
  }

  .table-container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-top: 30px;
  }

  #searchInput {
    width: 500px;
    max-width: 90%;
  }

  @media (max-width: 768px) {
    #searchInput {
      width: 100%;
      margin-bottom: 10px;
    }

    .action-buttons {
      flex-direction: column;
      align-items: center;
    }

    .action-buttons .btn {
      width: 90%;
    }
  }
</style>

</head>
<body>

      <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid flex-column align-items-center text-center">

          <!-- Logo -->
          <a class="navbar-brand mb-2" href="#">
            <i class="bi bi-box-seam me-2"></i>Mambo Produtos
          </a>

          <!-- Campo de pesquisa -->
          <form class="d-flex justify-content-center mb-3 w-100" role="search">
            <input class="form-control form-control-lg mx-auto" id="searchInput" placeholder="🔍 Pesquisar por nome..." aria-label="Pesquisar" />
          </form>

          <!-- Botões -->
          <div class="action-buttons d-flex flex-wrap justify-content-center text-center">
            <a href="../../public/voltar.php" class="btn btn-warning me-2 mb-2">
              <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
            <button onclick="window.print()" class="btn btn-dark me-2 mb-2">
              <i class="bi bi-printer"></i> Imprimir
            </button>
            <button onclick="exportTableToExcel('productTable', 'produtos')" class="btn btn-success me-2 mb-2">
              <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </button>
            <button onclick="exportTableToWord('productTable', 'produtos')" class="btn btn-primary mb-2">
              <i class="bi bi-file-earmark-word"></i> Exportar Word
            </button>
          </div>

        </div>
      </nav>

      
    </div>
  </div>
</nav>



    </div>
  </div>
</nav>

<div class="container">
  <div class="table-container">
    <div class="table-responsive">
      <table class="table table-bordered table-hover" id="productTable">
        <thead class="table-dark text-center">
          <tr>
            <th>ID</th>
            <th>Código de Barras</th>
            <th>Nome</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($produtos)): ?>
            <tr><td colspan="6" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
          <?php else: ?>
            <?php foreach ($produtos as $produto): ?>
              <tr>
                <td><?= $produto['id'] ?></td>
                <td><?= htmlspecialchars($produto['codigo_barra']) ?></td>
                <td class="nome-produto"><?= htmlspecialchars($produto['nome']) ?></td>
                <td><?= number_format($produto['preco'], 2, ',', '.') ?> MZN</td>
                <td><?= $produto['quantidade'] ?></td>
                <td class="text-center">
                  <a href="../../public/editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-outline-primary">
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
  const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename + '.doc';
  a.click();
}

// Filtro por nome
document.getElementById("searchInput").addEventListener("keyup", function () {
  const termo = this.value.toLowerCase();
  const linhas = document.querySelectorAll("#productTable tbody tr");

  linhas.forEach(linha => {
    const nome = linha.querySelector(".nome-produto")?.textContent.toLowerCase();
    linha.style.display = nome.includes(termo) ? "" : "none";
  });
});
</script>

</body>
</html>
