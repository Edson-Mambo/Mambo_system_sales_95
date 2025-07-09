<?php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

$cotacao_id = $_GET['cotacao_id'] ?? null;

if (!$cotacao_id) {
  die("Cotação não encontrada.");
}

// Busca dados da cotação (agora com dados do cliente!)
$sql = "SELECT * FROM cotacoes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cotacao_id]);
$cotacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotacao) {
  die("Cotação não encontrada.");
}

// Busca os itens da cotação
$sql_itens = "SELECT * FROM cotacao_itens WHERE cotacao_id = ?";
$stmt_itens = $pdo->prepare($sql_itens);
$stmt_itens->execute([$cotacao_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

// Dados fixos
$empresa_nome = "Mambo System Sales";
$empresa_contacto = "+258 84 854 1787";
$empresa_email = "info@mambosystem95.com";
$empresa_endereco = "Maputo, Moçambique";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Cotação #<?= $cotacao_id ?> - Imprimir</title>
  <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { font-size: 14px; }
    .invoice-box {
      max-width: 800px;
      margin: auto;
      padding: 30px;
      border: 1px solid #eee;
      box-shadow: 0 0 10px rgba(0,0,0,.15);
    }
    .invoice-box h1 { font-size: 28px; margin-bottom: 5px; }
    .invoice-box p { margin: 0; }
    .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
    .invoice-box table td { padding: 5px; vertical-align: top; }
    .invoice-box table tr.heading td { background: #f5f5f5; border-bottom: 1px solid #ddd; font-weight: bold; }
    .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
    .invoice-box table tr.total td:nth-child(4) { border-top: 2px solid #eee; font-weight: bold; }
  </style>
</head>

<body>
  <div class="invoice-box">

    <!-- Cabeçalho -->
    <table cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="4" class="text-center">
          <img src="/public/imagens/logo.png" alt="" style="max-height: 60px; margin-bottom: 10px;">
          <h1 class="mt-2"><?= $empresa_nome ?></h1>
          <p>
            <strong>Tel:</strong> <?= $empresa_contacto ?> |
            <strong>E-mail:</strong> <?= $empresa_email ?> |
            <strong>Local:</strong> <?= $empresa_endereco ?>
          </p>
        </td>
      </tr>

      <tr class="heading">
        <td colspan="4">Detalhes da Cotação</td>
      </tr>
      <tr>
        <td colspan="2">
          <strong>Cotação nº:</strong> <?= $cotacao_id ?><br>
          <strong>Data de Criação:</strong> <?= htmlspecialchars($cotacao['data_criacao']) ?>
        </td>
        <td colspan="2">
          <strong>Cliente:</strong> <?= htmlspecialchars($cotacao['empresa_nome']) ?><br>
          <strong>NUIT:</strong> <?= htmlspecialchars($cotacao['nuit']) ?><br>
          <strong>Morada:</strong> <?= htmlspecialchars($cotacao['morada']) ?><br>
          <strong>Contacto:</strong> <?= htmlspecialchars($cotacao['contacto']) ?><br>
          <strong>Email:</strong> <?= htmlspecialchars($cotacao['email']) ?>
        </td>
      </tr>
    </table>

    <!-- Itens -->
    <table cellpadding="0" cellspacing="0" class="mt-4">
      <tr class="heading">
        <td>Produto</td>
        <td>Qtd</td>
        <td>Preço Unitário</td>
        <td>Total</td>
      </tr>

      <?php $total = 0; ?>
      <?php foreach ($itens as $item): ?>
        <?php $subtotal = $item['quantidade'] * $item['preco_unitario']; ?>
        <?php $total += $subtotal; ?>
        <tr class="item">
          <td><?= htmlspecialchars($item['produto']) ?></td>
          <td><?= $item['quantidade'] ?></td>
          <td><?= number_format($item['preco_unitario'], 2) ?></td>
          <td><?= number_format($subtotal, 2) ?></td>
        </tr>
      <?php endforeach; ?>

      <tr class="total">
        <td colspan="2"></td>
        <td style="text-align: right;"><strong>Total:</strong></td>
        <td><strong><?= number_format($total, 2) ?> MZN</strong></td>
      </tr>
    </table>

    <p class="mt-4">
      <em>Obrigado pela sua preferência!<br>
      Esta cotação foi gerada pelo Mambo System Sales.</em>
    </p>

    <!-- Botões -->
    <button onclick="window.print()" class="btn btn-success mt-3">🖨️ Imprimir</button>
    <a href="../public/enviar_documento.php?tipo=cotacao&cotacao_id=<?= $cotacao_id ?>" class="btn btn-primary mt-3">✉️ Enviar por E-mail</a>
    <a href="../src/View/cotacao.view.php" class="btn btn-secondary mt-3">⬅️ Voltar</a>


  </div>
</body>
</html>
