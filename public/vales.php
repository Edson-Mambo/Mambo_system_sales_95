<?php
session_start();
require_once '../config/database.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = $_POST['cliente_nome'];
    $produtos = $_POST['produto'];
    $quantidades = $_POST['quantidade'];
    $precos = $_POST['preco'];

    $total = 0;
    foreach ($quantidades as $i => $qtd) {
        $total += $qtd * $precos[$i];
    }

    $stmt = $pdo->prepare("INSERT INTO vales (cliente_nome, valor_total, usuario_id) VALUES (?, ?, ?)");
    $stmt->execute([$cliente, $total, $_SESSION['usuario_id']]);
    $vale_id = $pdo->lastInsertId();

    foreach ($produtos as $i => $produto_id) {
        $stmt = $pdo->prepare("INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$vale_id, $produto_id, $quantidades[$i], $precos[$i]]);
    }

    $mensagem = 'Vale registrado com sucesso!';
}

$produtos = $pdo->query("SELECT * FROM produtos")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Venda por Vale</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
  <h2>Venda por Vale</h2>
  <?php if ($mensagem): ?><div class="alert alert-success"><?= $mensagem ?></div><?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label>Nome do Cliente</label>
      <input type="text" name="cliente_nome" class="form-control" required>
    </div>

    <table class="table">
      <thead>
        <tr><th>Produto</th><th>Quantidade</th><th>Preço</th></tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $p): ?>
        <tr>
          <td>
            <?= $p['nome'] ?>
            <input type="hidden" name="produto[]" value="<?= $p['id'] ?>">
          </td>
          <td><input type="number" name="quantidade[]" class="form-control" value="1" min="1"></td>
          <td>
            <?= number_format($p['preco'], 2, ',', '.') ?>
            <input type="hidden" name="preco[]" value="<?= $p['preco'] ?>">
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <button type="submit" class="btn btn-primary">Salvar Vale</button>
    <a href="venda.php" class="btn btn-secondary">Voltar</a>
  </form>
</body>
</html>

// ===============================
// 3. Scripts para venda.php (HTML/JS)
// ===============================

<!-- Adicione no navbar ou onde quiser o botão -->
<button class="btn btn-warning" onclick="pedirAutorizacao()">Venda por Vale</button>

<!-- Modal de Autorização -->
<div class="modal fade" id="modalAutorizacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" onsubmit="return verificarAutorizacao(event)">
      <div class="modal-header">
        <h5 class="modal-title">Autorização Requerida</h5>
      </div>
      <div class="modal-body">
        <input type="password" class="form-control" id="senhaAutorizacao" placeholder="Digite a senha do supervisor/gerente/admin" required>
        <div id="erroAutorizacao" class="text-danger mt-2 d-none">Senha incorreta ou sem permissão!</div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Confirmar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function pedirAutorizacao() {
    document.getElementById('senhaAutorizacao').value = '';
    document.getElementById('erroAutorizacao').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalAutorizacao')).show();
}

function verificarAutorizacao(e) {
    e.preventDefault();
    const senha = document.getElementById('senhaAutorizacao').value;

    fetch('verificar_autorizacao.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'senha=' + encodeURIComponent(senha)
    })
    .then(response => response.json())
    .then(data => {
        if (data.autorizado) {
            window.location.href = 'vales.php';
        } else {
            document.getElementById('erroAutorizacao').classList.remove('d-none');
        }
    });

    return false;
}
</script>

<!-- Certifique-se de que Bootstrap e JS estejam carregados -->
<script src="../assets/bootstrap.bundle.min.js"></script>
