<?php
require_once '../config/database.php';
session_start();

$pdo = Database::conectar();

// Categorias disponíveis
$categorias = ['Todos', 'Bebidas', 'Food', 'Limpeza', 'Snacks', 'Congelados', 'Outros'];
$categoriaSelecionada = $_GET['categoria'] ?? 'Todos';

// Consulta produtos por categoria
if ($categoriaSelecionada !== 'Todos') {
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome, p.codigo_barra, p.quantidade, p.preco, c.nome AS categoria
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE c.nome = ?
        ORDER BY p.nome
    ");
    $stmt->execute([$categoriaSelecionada]);
} else {
    $stmt = $pdo->query("
        SELECT p.id, p.nome, p.codigo_barra, p.quantidade, p.preco, c.nome AS categoria
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        ORDER BY c.nome, p.nome
    ");
}
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mensagem
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>🧮 Inventário Físico - Mambo System</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css" />
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
      margin-bottom: 25px;
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
    .input-small {
      max-width: 100px;
      margin: auto;
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
          <a href="?categoria=<?= urlencode($categoria) ?>"
             class="<?= $categoria === $categoriaSelecionada ? 'active' : '' ?>">
            <?= htmlspecialchars($categoria) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <section class="content">
     

      <!-- Voltar -->
      <a href="../src/View/inventario.view.php" class="btn btn-outline-secondary mb-3">← Voltar para Visualizar Inventário</a>

      <h1>📝 Inventário Físico - <?= htmlspecialchars($categoriaSelecionada) ?></h1>

      <?php if (!empty($mensagem)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <form method="post" action="salvar_inventario.php" novalidate>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Nome</th>
                <th>Código</th>
                <th>Estoque Sistema</th>
                <th>Quantidade Física</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($produtos)): ?>
                <?php foreach ($produtos as $produto): ?>
                  <tr>
                    <td><?= htmlspecialchars($produto['categoria']) ?></td>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td><?= htmlspecialchars($produto['codigo_barra']) ?></td>
                    <td><?= (int)$produto['quantidade'] ?></td>
                    <td>
                      <input type="number"
                             name="produtos[<?= $produto['id'] ?>][quantidade_fisica]"
                             class="form-control input-small"
                             min="0"
                             placeholder="0"
                             required />
                      <input type="hidden" name="produtos[<?= $produto['id'] ?>][id]" value="<?= $produto['id'] ?>" />
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado nesta categoria.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="text-end mt-3">
          <a href="index_admin.php" class="btn btn-outline-secondary me-2">← Voltar ao Painel</a>
          <button type="submit" class="btn btn-success">💾 Salvar Inventário</button>
        </div>
      </form>
    </section>
  </div>

<script src="../assets/bootstrap.bundle.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
</body>
</html>
