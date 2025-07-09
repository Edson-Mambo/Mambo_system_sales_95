<?php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

$factura_id = $_GET['factura_id'] ?? null;

if (!$factura_id) {
  die("Fatura não encontrada.");
}

// Busca fatura e venda
$sql = "SELECT f.*, v.* 
        FROM facturas f 
        JOIN vendas v ON f.venda_id = v.id 
        WHERE f.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$factura_id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
  die("Fatura não encontrada.");
}

// Produtos vendidos
$sql_prod = "
  SELECT pv.*, p.nome AS nome_produto 
  FROM produtos_vendidos pv
  JOIN produtos p ON pv.produto_id = p.id
  WHERE pv.venda_id = ?
";
$stmt_prod = $pdo->prepare($sql_prod);
$stmt_prod->execute([$factura['venda_id']]);
$produtos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Imprimir <?= ucfirst($factura['tipo']) ?> #<?= $factura_id ?></title>
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

    <!-- Cabeçalho da empresa -->
    <table cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="4" class="text-center">
          <img src="/public/imagens/logo.png" alt=" " style="max-height: 60px; margin-bottom: 10px;">
          <h1 class="mt-2"><?= $empresa_nome ?></h1>
          <p>
            <strong>Tel:</strong> <?= $empresa_contacto ?> |
            <strong>E-mail:</strong> <?= $empresa_email ?> |
            <strong>Local:</strong> <?= $empresa_endereco ?>
          </p>
        </td>
      </tr>

      <tr class="heading">
        <td colspan="4">Detalhes da Fatura</td>
      </tr>
      <tr>
        <td colspan="2">
          <strong>Fatura nº:</strong> <?= $factura_id ?><br>
          <strong>Tipo:</strong> <?= ucfirst($factura['tipo']) ?><br>
          <strong>Data da Venda:</strong> <?= htmlspecialchars($factura['data_venda']) ?>
        </td>
        <td colspan="2">
          <strong>Cliente:</strong> <?= htmlspecialchars($factura['empresa_nome']) ?><br>
          <strong>NUIT:</strong> <?= htmlspecialchars($factura['nuit']) ?><br>
          <strong>Morada:</strong> <?= htmlspecialchars($factura['morada']) ?><br>
          <strong>Contacto:</strong> <?= htmlspecialchars($factura['contacto']) ?><br>
          <strong>Email:</strong> <?= htmlspecialchars($factura['email']) ?>
        </td>
      </tr>
    </table>

    <!-- Produtos -->
    <table cellpadding="0" cellspacing="0" class="mt-4">
      <tr class="heading">
        <td>Produto</td>
        <td>Qtd</td>
        <td>Preço Unitário</td>
        <td>Total</td>
      </tr>

      <?php $soma = 0; ?>
      <?php foreach ($produtos as $p): ?>
        <?php $linha_total = $p['quantidade'] * $p['preco_unitario']; ?>
        <?php $soma += $linha_total; ?>
        <tr class="item">
          <td><?= htmlspecialchars($p['nome_produto']) ?></td>
          <td><?= $p['quantidade'] ?></td>
          <td><?= number_format($p['preco_unitario'], 2) ?></td>
          <td><?= number_format($linha_total, 2) ?></td>
        </tr>
      <?php endforeach; ?>

      <?php
        $total_com_iva = $factura['total'] ?? $soma;
        $iva = $total_com_iva * (17 / 117);
        $subtotal = $total_com_iva - $iva;
      ?>

      <tr class="total">
        <td colspan="2"></td>
        <td style="text-align: right;">Subtotal:</td>
        <td><?= number_format($subtotal, 2) ?></td>
      </tr>
      <tr class="total">
        <td colspan="2"></td>
        <td style="text-align: right;">IVA (17%):</td>
        <td><?= number_format($iva, 2) ?></td>
      </tr>
      <tr class="total">
        <td colspan="2"></td>
        <td style="text-align: right;"><strong>Total:</strong></td>
        <td><strong><?= number_format($total_com_iva, 2) ?></strong></td>
      </tr>
    </table>

    <p class="mt-4">
      <em>Obrigado pela sua preferência!<br>
      Esta fatura foi gerada pelo Mambo System Sales.</em>
    </p>

    <!-- Botões -->
    <button onclick="window.print()" class="btn btn-success mt-3">🖨️ Imprimir</button>
    <a href="enviar_documento.php?tipo=factura&factura_id=<?= $factura_id ?>" class="btn btn-secondary">✉️ Enviar por E-mail</a>
    <a href="factura_cotacao.php" class="btn btn-secondary mt-3">⬅️ Voltar para Vendas</a>

  </div>
</body>
</html>
