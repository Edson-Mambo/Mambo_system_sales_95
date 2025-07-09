<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';


use Dompdf\Dompdf;

$pdo = Database::conectar();

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'factura';
$venda_id = $_GET['venda_id'] ?? $_POST['venda_id'] ?? null;
$venda = null;

// Busca venda se existir
if ($venda_id) {
    $sql = "SELECT * FROM vendas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$venda_id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_factura'])) {
    $empresa_nome = trim($_POST['empresa_nome']);
    $nuit = trim($_POST['nuit']);
    $morada = trim($_POST['morada']);
    $contacto = trim($_POST['contacto']);
    $email = trim($_POST['email']);

    $sql = "INSERT INTO facturas (venda_id, tipo, empresa_nome, nuit, morada, contacto, email)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$venda_id, $tipo, $empresa_nome, $nuit, $morada, $contacto, $email]);

    $factura_id = $pdo->lastInsertId();

    // Pega produtos vendidos
    $sql = "SELECT pv.*, p.nome AS nome_produto 
            FROM produtos_vendidos pv 
            JOIN produtos p ON pv.produto_id = p.id 
            WHERE pv.venda_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$venda_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gera PDF
    $dompdf = new Dompdf();
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
      <meta charset="UTF-8">
      <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 22px; color: #0056b3; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        table, th, td { border: 1px solid #333; }
        th, td { padding: 8px; text-align: left; }
        .no-border { border: none; }
        .header-info td { vertical-align: top; }
      </style>
    </head>
    <body>
     <img src="/public/imagens/logo.png" alt=" " style="max-height: 60px; margin-bottom: 10px;">

      <h1>Mambo System Sales</h1>
      <p><strong>Tel:</strong> +258 84 854 1787 |
         <strong>E-mail:</strong> info@mambosystem95.com |
         <strong>Local:</strong> Maputo, Moçambique</p>

      <table class="no-border header-info" style="width: 100%; margin-top: 20px;">
        <tr>
          <td>
            <strong>Fatura nº:</strong> <?= $factura_id ?><br>
            <strong>Tipo:</strong> <?= ucfirst($tipo) ?><br>
            <strong>Data da Venda:</strong> <?= date('d/m/Y H:i:s') ?>
          </td>
          <td>
            <strong>Cliente:</strong> <?= htmlspecialchars($empresa_nome) ?><br>
            <strong>NUIT:</strong> <?= htmlspecialchars($nuit) ?><br>
            <strong>Morada:</strong> <?= htmlspecialchars($morada) ?><br>
            <strong>Contacto:</strong> <?= htmlspecialchars($contacto) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($email) ?>
          </td>
        </tr>
      </table>

      <table>
        <thead style="background-color: #f0f0f0;">
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
          foreach ($produtos as $p):
            $linha_total = $p['quantidade'] * $p['preco_unitario'];
            $subtotal += $linha_total;
          ?>
          <tr>
            <td><?= htmlspecialchars($p['nome_produto']) ?></td>
            <td><?= $p['quantidade'] ?></td>
            <td><?= number_format($p['preco_unitario'], 2, ',', '.') ?></td>
            <td><?= number_format($linha_total, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php
          $iva = $subtotal * 0.17;
          $total = $subtotal + $iva;
          ?>
          <tr>
            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
            <td><?= number_format($subtotal, 2, ',', '.') ?></td>
          </tr>
          <tr>
            <td colspan="3" class="text-end"><strong>IVA (17%):</strong></td>
            <td><?= number_format($iva, 2, ',', '.') ?></td>
          </tr>
          <tr>
            <td colspan="3" class="text-end"><strong>Total:</strong></td>
            <td><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
          </tr>
        </tbody>
      </table>

      <p style="margin-top: 30px; font-style: italic; color: #555;">
        Obrigado pela sua preferência!<br>
        Esta fatura foi gerada pelo Mambo System Sales.
      </p>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfDir = __DIR__ . '/../../public/pdf/facturas/';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0777, true);
    }
    $pdfPath = $pdfDir . "factura_{$factura_id}.pdf";
    file_put_contents($pdfPath, $dompdf->output());

    echo "<div class='alert alert-success mt-3'>✅ Fatura gerada com sucesso! PDF salvo.</div>";
    echo "<a href='../public/imprimir_factura.php?factura_id=$factura_id' class='btn btn-success mt-2'>🖨️ Imprimir</a> ";
    echo "<a href='../public/enviar_documento.php?tipo=factura&factura_id=$factura_id' class='btn btn-secondary mt-2'>✉️ Enviar por E-mail</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title><?= ucfirst($tipo) ?> - Mambo System Sales</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
      min-height: 100vh;
    }
    h3 {
      color: #0d6efd;
      font-weight: 600;
    }
    .card-header h5 {
      color: #0d6efd;
      font-weight: 600;
    }
    .btn-primary {
      background-color: #0d6efd;
      border-color: #0d6efd;
    }
    .form-label {
      font-weight: 500;
    }
  </style>
</head>

<body class="container mt-5 mb-5">

  <h3 class="mb-4 text-center"><?= ucfirst($tipo) ?> | Mambo System Sales</h3>

  <!-- Busca do número do recibo -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header">
      <h5 class="mb-0">🔍 Buscar Venda por Nº de Recibo</h5>
    </div>
    <div class="card-body">
      <form method="GET" action="factura_cotacao.php" class="row g-3 align-items-center">
        <div class="col-md-6 col-lg-4">
          <input type="text" name="venda_id" class="form-control" placeholder="Ex: 123" required value="<?= htmlspecialchars($venda_id) ?>">
          <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-outline-primary">Buscar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Detalhes da venda -->
  <?php if ($venda): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header">
        <h5 class="mb-0">📄 Detalhes da Venda</h5>
      </div>
      <div class="card-body">
        <p><strong>Recibo nº:</strong> <?= htmlspecialchars($venda['id']) ?></p>
        <p><strong>Data:</strong> <?= htmlspecialchars($venda['data_venda']) ?></p>
        <p><strong>Total:</strong> MZN <?= number_format($venda['total'], 2, ',', '.') ?></p>
        <p><strong>Método de pagamento:</strong> <?= htmlspecialchars($venda['metodo_pagamento']) ?></p>
      </div>
    </div>

    <!-- Formulário para dados do cliente -->
    <div class="card mb-5 shadow-sm">
      <div class="card-header">
        <h5 class="mb-0">📝 Informações do Cliente</h5>
      </div>
      <div class="card-body">
        <form action="factura_cotacao.php" method="POST" class="row g-3">
          <input type="hidden" name="venda_id" value="<?= htmlspecialchars($venda_id) ?>">
          <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

          <div class="col-md-6">
            <label class="form-label" for="empresa_nome">Nome da Empresa</label>
            <input type="text" id="empresa_nome" name="empresa_nome" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="nuit">NUIT</label>
            <input type="text" id="nuit" name="nuit" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="morada">Morada</label>
            <input type="text" id="morada" name="morada" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="contacto">Contacto</label>
            <input type="text" id="contacto" name="contacto" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
          </div>

          <div class="col-12">
            <button type="submit" name="gerar_factura" class="btn btn-primary">💾 Gerar <?= ucfirst($tipo) ?></button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif ($venda_id): ?>
    <div class="alert alert-warning text-center">Nenhuma venda encontrada com o recibo nº <strong><?= htmlspecialchars($venda_id) ?></strong>.</div>
  <?php endif; ?>

  <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
</body>
</html>
