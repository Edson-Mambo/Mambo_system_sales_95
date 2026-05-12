<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = Database::conectar();

/* =========================
   DADOS DA EMPRESA (ROBUSTO)
========================= */
$stmt = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];

$config = array_merge([
    'nome_empresa'  => $config['nome_empresa'] ?? $config['nome'] ?? 'EMPRESA',
    'endereco'      => $config['endereco'] ?? '',
    'rua_avenida'   => $config['rua_avenida'] ?? '',
    'bairro'        => $config['bairro'] ?? '',
    'cidade'        => $config['cidade'] ?? '',
    'provincia'     => $config['provincia'] ?? '',
    'telefone'      => $config['telefone'] ?? '',
    'email_empresa' => $config['email_empresa'] ?? $config['email'] ?? '',
    'nuit_empresa'  => $config['nuit_empresa'] ?? $config['nuit'] ?? ''
], []);

/* =========================
   SESSÕES
========================= */
if (!isset($_SESSION['cotacao'])) {
  $_SESSION['cotacao'] = [];
}

if (!isset($_SESSION['cotacao_cliente'])) {
  $_SESSION['cotacao_cliente'] = [];
}

/* =========================
   ADICIONAR PRODUTO
========================= */
if (isset($_POST['adicionar_produto'])) {

  $codigo = trim($_POST['codigo']);
  $quantidade = intval($_POST['quantidade']);

  $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = ?");
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

/* =========================
   GERAR COTAÇÃO
========================= */
if (isset($_POST['gerar_cotacao'])) {

  /* CLIENTE MANUAL */
  $_SESSION['cotacao_cliente'] = [
    'empresa_nome' => trim($_POST['empresa_nome']),
    'nuit'         => trim($_POST['nuit']),
    'morada'       => trim($_POST['morada']),
    'contacto'     => trim($_POST['contacto']),
    'email'        => trim($_POST['email'])
  ];

  $itens = $_SESSION['cotacao'];

  if (count($itens) > 0) {

    /* =========================
       INSERIR COTAÇÃO
    ========================= */
    $stmt = $pdo->prepare("
      INSERT INTO cotacoes (
        data_criacao,
        empresa_nome,
        nuit,
        morada,
        contacto,
        email
      )
      VALUES (NOW(), ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
      $_SESSION['cotacao_cliente']['empresa_nome'],
      $_SESSION['cotacao_cliente']['nuit'],
      $_SESSION['cotacao_cliente']['morada'],
      $_SESSION['cotacao_cliente']['contacto'],
      $_SESSION['cotacao_cliente']['email']
    ]);

    $cotacao_id = $pdo->lastInsertId();

    /* =========================
       ITENS
    ========================= */
    foreach ($itens as $item) {

      $stmt = $pdo->prepare("
        INSERT INTO cotacao_itens (
          cotacao_id,
          produto,
          quantidade,
          preco_unitario
        )
        VALUES (?, ?, ?, ?)
      ");

      $stmt->execute([
        $cotacao_id,
        $item['nome'],
        $item['quantidade'],
        $item['preco_unitario']
      ]);
    }

    /* =========================
       PDF
    ========================= */
    $dompdf = new Dompdf();
    ob_start();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial; font-size: 12px; }
h1 { text-align:center; margin-bottom:5px; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
table, th, td { border:1px solid #333; }
th, td { padding:6px; }
hr { margin:10px 0; }
</style>
</head>
<body>

<!-- EMPRESA -->
<h1><?= htmlspecialchars($config['nome_empresa']) ?></h1>

<div style="text-align:center;">
<?= $config['rua_avenida'] ?>, <?= $config['bairro'] ?>,
<?= $config['cidade'] ?> - <?= $config['provincia'] ?><br>

Tel: <?= $config['telefone'] ?> |
Email: <?= $config['email_empresa'] ?><br>

NUIT: <?= $config['nuit_empresa'] ?>
</div>

<hr>

<!-- INFO -->
<table>
<tr>
<td>
<strong>Cotação Nº:</strong> <?= $cotacao_id ?><br>
<strong>Data:</strong> <?= date('d/m/Y H:i') ?>
</td>

<td>
<strong>Cliente:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['empresa_nome']) ?><br>
<strong>NUIT:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['nuit']) ?><br>
<strong>Contacto:</strong> <?= htmlspecialchars($_SESSION['cotacao_cliente']['contacto']) ?>
</td>
</tr>
</table>

<!-- ITENS -->
<table>
<thead style="background:#f2f2f2;">
<tr>
<th>Produto</th>
<th>Qtd</th>
<th>Preço</th>
<th>Total</th>
</tr>
</thead>
<tbody>

<?php
$total = 0;
foreach ($itens as $item):
$linha = $item['quantidade'] * $item['preco_unitario'];
$total += $linha;
?>
<tr>
<td><?= htmlspecialchars($item['nome']) ?></td>
<td><?= $item['quantidade'] ?></td>
<td><?= number_format($item['preco_unitario'], 2) ?></td>
<td><?= number_format($linha, 2) ?></td>
</tr>
<?php endforeach; ?>

<tr>
<td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
<td><strong><?= number_format($total, 2) ?> MZN</strong></td>
</tr>

</tbody>
</table>

<p style="margin-top:20px; text-align:center;">
Documento gerado automaticamente pelo computador - @ Mambo System 95
</p>

</body>
</html>

<?php
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dir = __DIR__ . '/../../public/pdf/cotacoes/';
    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    file_put_contents($dir . "cotacao_{$cotacao_id}.pdf", $dompdf->output());

    /* =========================
       LIMPAR SESSÃO
    ========================= */
    $_SESSION['cotacao'] = [];
    $_SESSION['cotacao_cliente'] = [];

    /* =========================
       BOTÕES FINAIS
    ========================= */
    echo "<div class='alert alert-success'>Cotação gerada com sucesso!</div>";

    echo "<div class='d-flex gap-2 mt-2'>";

    echo "<a href='../../public/pdf/cotacoes/cotacao_{$cotacao_id}.pdf' target='_blank' class='btn btn-primary'>
            🖨️ Abrir PDF
          </a>";

    echo "<a href='../../public/enviar_documento.php?tipo=cotacao&cotacao_id={$cotacao_id}' class='btn btn-secondary'>
            ✉️ Enviar Email
          </a>";

    echo "<a href='../../public/venda.php' class='btn btn-outline-dark'>
            ← Voltar
          </a>";

    echo "</div>";

    exit;

  } else {
    echo "<div class='alert alert-warning'>Adicione produtos primeiro.</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Cotação ERP</title>
<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h5><?= htmlspecialchars($config['nome_empresa']) ?> | Cotação</h5>

<!-- VOLTAR -->
<a href="../../public/venda.php" class="btn btn-outline-secondary mb-3">
← Voltar
</a>

<!-- PRODUTOS -->
<form method="POST" class="row g-2 mb-3">
<div class="col-md-4">
<input type="text" name="codigo" class="form-control" placeholder="Código" required>
</div>
<div class="col-md-2">
<input type="number" name="quantidade" value="1" class="form-control">
</div>
<div class="col-md-2">
<button class="btn btn-primary" name="adicionar_produto">Adicionar</button>
</div>
</form>

<!-- LISTA -->
<table class="table table-bordered">
<thead>
<tr>
<th>Produto</th>
<th>Qtd</th>
<th>Preço</th>
<th>Total</th>
</tr>
</thead>
<tbody>

<?php
$total = 0;
foreach ($_SESSION['cotacao'] as $item):
$t = $item['quantidade'] * $item['preco_unitario'];
$total += $t;
?>
<tr>
<td><?= htmlspecialchars($item['nome']) ?></td>
<td><?= $item['quantidade'] ?></td>
<td><?= number_format($item['preco_unitario'], 2) ?></td>
<td><?= number_format($t, 2) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<h5>Total: <?= number_format($total, 2) ?> MZN</h5>

<!-- CLIENTE -->
<form method="POST" class="row g-2">
<div class="col-md-4">
<input type="text" name="empresa_nome" class="form-control" placeholder="Cliente" required>
</div>
<div class="col-md-2">
<input type="text" name="nuit" class="form-control" placeholder="NUIT">
</div>
<div class="col-md-3">
<input type="text" name="morada" class="form-control" placeholder="Morada">
</div>
<div class="col-md-2">
<input type="text" name="contacto" class="form-control" placeholder="Contacto">
</div>
<div class="col-md-3 mt-2">
<input type="email" name="email" class="form-control" placeholder="Email">
</div>

<div class="col-md-2 mt-2">
<button class="btn btn-success" name="gerar_cotacao">
📝 Gerar Cotação
</button>
</div>
</form>

</body>
</html>