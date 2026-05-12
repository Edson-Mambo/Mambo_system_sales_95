<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = Database::conectar();

/* =========================
   EMPRESA (ROBUSTO ERP)
========================= */
$stmt = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];

$config = array_merge([
    'nome_empresa'  => $config['nome_empresa'] ?? 'EMPRESA',
    'rua_avenida'   => $config['rua_avenida'] ?? '',
    'bairro'        => $config['bairro'] ?? '',
    'cidade'        => $config['cidade'] ?? '',
    'provincia'     => $config['provincia'] ?? '',
    'telefone'      => $config['telefone'] ?? '',
    'email_empresa' => $config['email_empresa'] ?? '',
    'nuit_empresa'  => $config['nuit_empresa'] ?? ''
], []);

/* =========================
   VARIÁVEIS
========================= */
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'factura';
$venda_id = $_GET['venda_id'] ?? $_POST['venda_id'] ?? null;
$venda = null;

/* =========================
   BUSCAR VENDA
========================= */
if ($venda_id) {
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
    $stmt->execute([$venda_id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   GERAR FACTURA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_factura'])) {

    $empresa_nome = trim($_POST['empresa_nome']);
    $nuit = trim($_POST['nuit']);
    $morada = trim($_POST['morada']);
    $contacto = trim($_POST['contacto']);
    $email = trim($_POST['email']);

    /* =========================
       INSERIR FACTURA
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO facturas 
        (venda_id, tipo, empresa_nome, nuit, morada, contacto, email)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $venda_id,
        $tipo,
        $empresa_nome,
        $nuit,
        $morada,
        $contacto,
        $email
    ]);

    $factura_id = $pdo->lastInsertId();

    /* =========================
       PRODUTOS
    ========================= */
    $stmt = $pdo->prepare("
        SELECT pv.*, p.nome AS nome_produto
        FROM produtos_vendidos pv
        JOIN produtos p ON pv.produto_id = p.id
        WHERE pv.venda_id = ?
    ");
    $stmt->execute([$venda_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

.header { text-align:center; border-bottom:2px solid #0d6efd; padding-bottom:10px; }

h1 { color:#0d6efd; margin:0; }

table { width:100%; border-collapse:collapse; margin-top:15px; }

th { background:#0d6efd; color:#fff; padding:8px; }

td { border:1px solid #ddd; padding:6px; }

.footer { text-align:center; margin-top:20px; font-style:italic; }
</style>
</head>

<body>

<div class="header">
    <h1><?= htmlspecialchars($config['nome_empresa']) ?></h1>
    <small>
        <?= $config['rua_avenida'] ?>, <?= $config['bairro'] ?>,
        <?= $config['cidade'] ?> - <?= $config['provincia'] ?><br>
        Tel: <?= $config['telefone'] ?> |
        Email: <?= $config['email_empresa'] ?><br>
        NUIT: <?= $config['nuit_empresa'] ?>
    </small>
</div>

<hr>

<h3>Factura #<?= $factura_id ?></h3>

<p>
<strong>Tipo:</strong> <?= ucfirst($tipo) ?><br>
<strong>Data:</strong> <?= date('d/m/Y H:i') ?>
</p>

<hr>

<h3>Cliente</h3>
<p>
<strong><?= htmlspecialchars($empresa_nome) ?></strong><br>
NUIT: <?= htmlspecialchars($nuit) ?><br>
Morada: <?= htmlspecialchars($morada) ?><br>
Contacto: <?= htmlspecialchars($contacto) ?><br>
Email: <?= htmlspecialchars($email) ?>
</p>

<table>
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
$subtotal = 0;
foreach ($produtos as $p):
$totalLinha = $p['quantidade'] * $p['preco_unitario'];
$subtotal += $totalLinha;
?>
<tr>
<td><?= htmlspecialchars($p['nome_produto']) ?></td>
<td><?= $p['quantidade'] ?></td>
<td><?= number_format($p['preco_unitario'], 2, ',', '.') ?></td>
<td><?= number_format($totalLinha, 2, ',', '.') ?></td>
</tr>
<?php endforeach; ?>

<?php
$iva = $subtotal * 0.17;
$total = $subtotal + $iva;
?>

<tr>
<td colspan="3" style="text-align:right;"><strong>Subtotal</strong></td>
<td><?= number_format($subtotal, 2, ',', '.') ?></td>
</tr>

<tr>
<td colspan="3" style="text-align:right;"><strong>IVA (17%)</strong></td>
<td><?= number_format($iva, 2, ',', '.') ?></td>
</tr>

<tr>
<td colspan="3" style="text-align:right;"><strong>Total</strong></td>
<td><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
</tr>

</tbody>
</table>

<div class="footer">
Obrigado por escolher o Mambo System ERP
</div>

</body>
</html>

<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* =========================
   SALVAR PDF (IGUAL COTAÇÃO)
========================= */
$pdfDir = __DIR__ . '/../../public/pdf/facturas/';

if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

file_put_contents($pdfDir . "factura_{$factura_id}.pdf", $dompdf->output());

/* =========================
   LIMPAR SESSÃO (IGUAL COTAÇÃO)
========================= */
$_SESSION['factura'] = [];

/* =========================
   BOTÕES FINAIS (IGUAL COTAÇÃO)
========================= */

echo "<div class='alert alert-success'>✔ Factura gerada com sucesso!</div>";

echo "<div class='d-flex gap-2 mt-2'>";

/* PDF */
echo "<a href='../public/pdf/facturas/factura_{$factura_id}.pdf' target='_blank' class='btn btn-primary'>
        🖨️ Abrir PDF
      </a>";

/* EMAIL */
echo "<a href='../public/enviar_documento.php?tipo=factura&factura_id={$factura_id}' class='btn btn-secondary'>
        ✉️ Enviar Email
      </a>";

/* VOLTAR */
echo "<a href='../public/venda.php' class='btn btn-outline-dark'>
        ← Voltar
      </a>";

echo "</div>";

exit;

} else {
    echo "<div class='alert alert-warning'>Adicione produtos primeiro.</div>";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Factura </title>
<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-4">

<h4><?= htmlspecialchars($config['nome_empresa']) ?> | Facturação ERP</h4>

<!-- VOLTAR (ANTES) -->
<a href="../public/venda.php" class="btn btn-outline-secondary mb-3">
← Voltar
</a>

<!-- BUSCA -->
<form method="GET" class="row g-2 mb-3">
<div class="col-md-6">
<input type="text" name="venda_id" class="form-control" placeholder="ID venda">
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Buscar</button>
</div>
</form>

<?php if ($venda): ?>

<div class="card p-3 mb-3">
<h5>Venda #<?= $venda['id'] ?></h5>
<p>Total: MZN <?= number_format($venda['total'], 2, ',', '.') ?></p>
</div>

<div class="card p-3">

<form method="POST" class="row g-2">

<input type="hidden" name="venda_id" value="<?= $venda_id ?>">
<input type="hidden" name="tipo" value="<?= $tipo ?>">

<div class="col-md-6">
<input class="form-control" name="empresa_nome" placeholder="Empresa" required>
</div>

<div class="col-md-6">
<input class="form-control" name="nuit" placeholder="NUIT">
</div>

<div class="col-md-6">
<input class="form-control" name="morada" placeholder="Morada">
</div>

<div class="col-md-6">
<input class="form-control" name="contacto" placeholder="Contacto">
</div>

<div class="col-md-6">
<input class="form-control" name="email" placeholder="Email">
</div>

<div class="col-12">
<button class="btn btn-success w-100" name="gerar_factura">
Gerar Documento
</button>
</div>

</form>

</div>

<?php endif; ?>

</body>
</html>