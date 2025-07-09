<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = Database::conectar();

// Inicia sessão de cotação
if (!isset($_SESSION['cotacao'])) {
  $_SESSION['cotacao'] = [];
}

// Inicia sessão de dados do cliente
if (!isset($_SESSION['cotacao_cliente'])) {
  $_SESSION['cotacao_cliente'] = [];
}

// Adiciona produto
if (isset($_POST['adicionar_produto'])) {
  $codigo = trim($_POST['codigo']);
  $quantidade = intval($_POST['quantidade']);

  $sql = "SELECT * FROM produtos WHERE codigo_barra = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$codigo]);
  $produto = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($produto) {
    $_SESSION['cotacao'][] = [
      'codigo' => $codigo,
      'nome' => $produto['nome'],
      'preco_unitario' => $produto['preco'],
      'quantidade' => $quantidade
    ];
  }
}

// Gera cotação e PDF
if (isset($_POST['gerar_cotacao'])) {
  // Salva dados do cliente na sessão
  $_SESSION['cotacao_cliente'] = [
    'empresa_nome' => trim($_POST['empresa_nome']),
    'nuit' => trim($_POST['nuit']),
    'morada' => trim($_POST['morada']),
    'contacto' => trim($_POST['contacto']),
    'email' => trim($_POST['email'])
  ];

  $itens = $_SESSION['cotacao'];
  if (count($itens) > 0) {
    // Inserir cotação no banco
    $sql = "INSERT INTO cotacoes (data_criacao, empresa_nome, nuit, morada, contacto, email) 
            VALUES (NOW(), ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      $_SESSION['cotacao_cliente']['empresa_nome'],
      $_SESSION['cotacao_cliente']['nuit'],
      $_SESSION['cotacao_cliente']['morada'],
      $_SESSION['cotacao_cliente']['contacto'],
      $_SESSION['cotacao_cliente']['email']
    ]);
    $cotacao_id = $pdo->lastInsertId();

    // Inserir itens da cotação
    foreach ($itens as $item) {
      $sql = "INSERT INTO cotacao_itens (cotacao_id, produto, quantidade, preco_unitario)
              VALUES (?, ?, ?, ?)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$cotacao_id, $item['nome'], $item['quantidade'], $item['preco_unitario']]);
    }

    // Gera PDF da cotação usando Dompdf
    $dompdf = new Dompdf();
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
      <meta charset="UTF-8">
      <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; color: #007bff; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        table, th, td { border: 1px solid #333; }
        th, td { padding: 6px; text-align: left; }
        .no-border { border: none; }
      </style>
    </head>
    <body>

    <img src="logo.png" alt="Logo" style="max-height: 50px;"><br><br>
    <h1>Mambo System Sales - Cotação</h1>
    <p><strong>Tel:</strong> +258 84 854 1787 |
       <strong>E-mail:</strong> info@mambosystem95.com |
       <strong>Local:</strong> Maputo, Moçambique</p>

    <table class="no-border">
      <tr>
        <td><strong>Cotação nº:</strong> <?= $cotacao_id ?><br>
            <strong>Data:</strong> <?= date('Y-m-d H:i:s') ?>
        </td>
        <td>
          <strong>Cliente:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['empresa_nome']) ?><br>
          <strong>NUIT:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['nuit']) ?><br>
          <strong>Morada:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['morada']) ?><br>
          <strong>Contacto:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['contacto']) ?><br>
          <strong>Email:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['email']) ?>
        </td>
      </tr>
    </table>

    <table>
      <thead>
        <tr>
          <th>Produto</th>
          <th>Qtd</th>
          <th>Preço Unitário (MZN)</th>
          <th>Total (MZN)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $subtotal = 0;
        foreach ($itens as $item):
          $linha_total = $item['quantidade'] * $item['preco_unitario'];
          $subtotal += $linha_total;
        ?>
        <tr>
          <td><?= htmlspecialchars($item['nome']) ?></td>
          <td><?= $item['quantidade'] ?></td>
          <td><?= number_format($item['preco_unitario'], 2) ?></td>
          <td><?= number_format($linha_total, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
          <td><?= number_format($subtotal, 2) ?></td>
        </tr>
      </tbody>
    </table>

    <p style="margin-top: 30px;">
      <em>Obrigado pela sua preferência!<br>
      Esta cotação foi gerada pelo Mambo System Sales.</em>
    </p>

    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfDir = __DIR__ . '/../../public/pdf/cotacoes/';
    if (!is_dir($pdfDir)) {
      mkdir($pdfDir, 0777, true);
    }
    $pdfPath = $pdfDir . "cotacao_{$cotacao_id}.pdf";
    file_put_contents($pdfPath, $dompdf->output());

    // Limpa sessões
    $_SESSION['cotacao'] = [];
    $_SESSION['cotacao_cliente'] = [];

    // Exibe mensagem de sucesso e botões para abrir PDF e enviar email
    echo "<div class='alert alert-success mt-3'>✅ Cotação gerada e PDF salvo com sucesso!</div>";
    echo "<a href='../../public/pdf/cotacoes/cotacao_{$cotacao_id}.pdf' target='_blank' class='btn btn-success mt-2'>🖨️ Abrir PDF da Cotação</a> ";
    echo "<a href='../../public/enviar_documento.php?tipo=cotacao&cotacao_id={$cotacao_id}' class='btn btn-secondary mt-2'>✉️ Enviar por E-mail</a>";
    exit;
  } else {
    echo "<div class='alert alert-warning mt-3'>⚠️ Não é possível gerar cotação sem produtos.</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Gerar Cotação - Mambo System Sales</title>
  <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

  <!-- Cabeçalho -->
  <div class="p-3 bg-white border rounded shadow-sm mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <h5 class="mb-0 text-primary">Mambo System Sales | Cotação</h5>
      <div class="text-muted small">
        <span><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></span>
      </div>
      <a href="../../public/venda.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
      <a href="../../public/logout.php" class="btn btn-sm btn-outline-danger">🔒 Terminar Sessão</a>
    </div>
  </div>

  <!-- Form adicionar produto -->
  <form method="POST" class="row g-2 mb-4">
    <div class="col-md-4">
      <input type="text" name="codigo" class="form-control" placeholder="Código de Barras" required>
    </div>
    <div class="col-md-2">
      <input type="number" name="quantidade" class="form-control" placeholder="Qtd" value="1" required>
    </div>
    <div class="col-md-2">
      <button type="submit" name="adicionar_produto" class="btn btn-primary">➕ Adicionar</button>
    </div>
  </form>

  <!-- Lista produtos -->
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Produto</th>
        <th>Qtd</th>
        <th>Preço Unitário</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $total = 0;
      foreach ($_SESSION['cotacao'] as $item):
        $subtotal = $item['quantidade'] * $item['preco_unitario'];
        $total += $subtotal;
      ?>
        <tr>
          <td><?= htmlspecialchars($item['nome']) ?></td>
          <td><?= $item['quantidade'] ?></td>
          <td><?= number_format($item['preco_unitario'], 2) ?></td>
          <td><?= number_format($subtotal, 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h5>Total: <?= number_format($total, 2) ?> MZN</h5>

  <!-- Dados cliente e botão gerar -->
  <form method="POST" class="row g-2">
    <div class="col-md-4">
      <input type="text" name="empresa_nome" class="form-control" placeholder="Nome da Empresa/Cliente" required>
    </div>
    <div class="col-md-2">
      <input type="text" name="nuit" class="form-control" placeholder="NUIT" required>
    </div>
    <div class="col-md-3">
      <input type="text" name="morada" class="form-control" placeholder="Morada" required>
    </div>
    <div class="col-md-2">
      <input type="text" name="contacto" class="form-control" placeholder="Contacto" required>
    </div>
    <div class="col-md-4 mt-2">
      <input type="email" name="email" class="form-control" placeholder="E-mail do Cliente" required>
    </div>

    <div class="col-md-2 mt-2">
      <button type="submit" name="gerar_cotacao" class="btn btn-success w-100">📝 Gerar Cotação</button>
    </div>
  </form>

</body>
</html>
