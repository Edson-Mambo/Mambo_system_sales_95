<?php
require_once '../../config/database.php';
$pdo = Database::conectar();

// Buscar todas categorias
$stmtCategorias = $pdo->query("SELECT * FROM categorias ORDER BY nome");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Capturar categoria selecionada pelo GET (padrão: todos)
$categoriaSelecionada = $_GET['categoria'] ?? 'todos';

// Buscar produtos conforme categoria selecionada
if ($categoriaSelecionada === 'todos') {
    $stmtProdutos = $pdo->query("SELECT p.*, c.nome AS categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        ORDER BY c.nome ASC, p.nome ASC");
} else {
    // Evitar SQL Injection com prepared statement
    $stmtProdutos = $pdo->prepare("SELECT p.*, c.nome AS categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE c.nome = :categoria
        ORDER BY p.nome ASC");
    $stmtProdutos->execute(['categoria' => $categoriaSelecionada]);
}
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>Lista de Produtos | Mambo System</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../node_modules/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    /* Sidebar */
    .sidebar {
      width: 220px;
      background: #212529;
      color: #fff;
      min-height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 1.5rem;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .sidebar h3 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-weight: 700;
      letter-spacing: 1px;
      font-size: 1.3rem;
    }
    .sidebar a {
      padding: 12px 20px;
      color: #adb5bd;
      text-decoration: none;
      font-weight: 500;
      border-left: 4px solid transparent;
      transition: background-color 0.3s, border-color 0.3s, color 0.3s;
    }
    .sidebar a:hover {
      background-color: #343a40;
      color: #fff;
      border-left-color: #0d6efd;
    }
    .sidebar a.active {
      background-color: #0d6efd;
      color: #fff;
      border-left-color: #0d6efd;
      font-weight: 700;
    }

    /* Conteúdo principal */
    .main-content {
      margin-left: 220px;
      padding: 2rem 3rem;
      flex-grow: 1;
      min-height: 100vh;
    }
    .main-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 10px;
    }
    .main-header h1 {
      font-weight: 700;
      color: #0d6efd;
    }
    .search-box {
      width: 300px;
      max-width: 100%;
    }
    .search-box input {
      width: 100%;
      padding: 8px 12px;
      border-radius: 5px;
      border: 1px solid #ced4da;
      transition: border-color 0.3s;
      font-size: 1rem;
    }
    .search-box input:focus {
      outline: none;
      border-color: #0d6efd;
      box-shadow: 0 0 5px rgba(13,110,253,0.5);
    }
    /* Tabela */
    .table-container {
      background: #fff;
      padding: 20px 25px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow-x: auto;
    }
    table.table {
      min-width: 720px;
      border-collapse: separate;
      border-spacing: 0 0.75rem;
    }
    table.table thead th {
      background-color: #343a40;
      color: #fff;
      font-weight: 600;
      padding: 12px 15px;
      border-radius: 10px 10px 0 0;
      text-align: center;
    }
    table.table tbody tr {
      background-color: #fefefe;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      border-radius: 10px;
      transition: background-color 0.3s;
    }
    table.table tbody tr:hover {
      background-color: #e9f0ff;
    }
    table.table tbody td {
      vertical-align: middle;
      padding: 14px 15px;
      text-align: center;
      font-weight: 500;
      color: #495057;
    }
    table.table tbody td.nome-produto {
      text-align: left;
      font-weight: 600;
      color: #212529;
    }
    /* Botões de ação */
    .btn-action {
      font-size: 1rem;
      padding: 6px 12px;
      border-radius: 6px;
      transition: background-color 0.3s;
    }
    .btn-action i {
      vertical-align: middle;
    }
    .btn-action-edit {
      color: #0d6efd;
      border: 1px solid #0d6efd;
      background-color: transparent;
    }
    .btn-action-edit:hover {
      background-color: #0d6efd;
      color: #fff;
    }
    /* Responsividade */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 1rem 1rem;
      }
      table.table {
        min-width: 100%;
      }
      .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
        flex-direction: row;
        overflow-x: auto;
        padding: 0.5rem 0;
      }
      .sidebar h3 {
        display: none;
      }
      .sidebar a {
        flex: 1 0 auto;
        border-left: none !important;
        border-bottom: 2px solid transparent;
        padding: 10px 15px;
        text-align: center;
        font-size: 0.9rem;
      }
      .sidebar a.active {
        border-left: none !important;
        border-bottom: 2px solid #0d6efd;
      }
    }
  </style>
</head>
<body>
  <nav class="sidebar" role="navigation" aria-label="Categorias do Inventário">
    <h3>Categorias</h3>
    <a href="?categoria=todos" class="<?= $categoriaSelecionada === 'todos' ? 'active' : '' ?>">Todos</a>
    <?php foreach($categorias as $cat): ?>
      <a href="?categoria=<?= urlencode($cat['nome']) ?>" class="<?= $categoriaSelecionada === $cat['nome'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['nome']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <main class="main-content">
    <div class="main-header">
      <h1>📦 Inventário de Produtos</h1>
      <div class="search-box">
        <input type="search" id="searchInput" placeholder="🔍 Pesquisar produto por nome..." aria-label="Pesquisar produtos">
      </div>
    </div>

    <section class="table-container" aria-live="polite" aria-relevant="all">
      <table class="table" id="productTable" aria-describedby="tabela-produtos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Categoria</th>
            <th>Código</th>
            <th>Nome</th>
            <th>Preço (MZN)</th>
            <th>Quantidade</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($produtos)): ?>
            <tr><td colspan="7" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
          <?php else: ?>
            <?php foreach ($produtos as $produto): ?>
              <tr>
                <td><?= $produto['id'] ?></td>
                <td><?= htmlspecialchars($produto['categoria_nome'] ?? '—') ?></td>
                <td><?= htmlspecialchars($produto['codigo_barra']) ?></td>
                <td class="nome-produto"><?= htmlspecialchars($produto['nome']) ?></td>
                <td><?= number_format($produto['preco'], 2, ',', '.') ?></td>
                <td><?= $produto['quantidade'] ?></td>
                <td>
                  <a href="../../public/editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-action btn-action-edit" title="Editar Produto">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
  <script>
    // Pesquisa filtro por nome
    document.getElementById('searchInput').addEventListener('input', function() {
      const termo = this.value.toLowerCase();
      const linhas = document.querySelectorAll('#productTable tbody tr');

      linhas.forEach(linha => {
        const nome = linha.querySelector('.nome-produto').textContent.toLowerCase();
        linha.style.display = nome.includes(termo) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
