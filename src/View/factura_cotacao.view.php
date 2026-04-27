<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = Database::conectar();

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'factura';
$venda_id = $_GET['venda_id'] ?? $_POST['venda_id'] ?? null;
$venda = null;

// =========================
// BUSCA VENDA
// =========================
if ($venda_id) {
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
    $stmt->execute([$venda_id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   GERAR DOCUMENTO ERP
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_factura'])) {

    $empresa_nome = trim($_POST['empresa_nome']);
    $nuit = trim($_POST['nuit']);
    $morada = trim($_POST['morada']);
    $contacto = trim($_POST['contacto']);
    $email = trim($_POST['email']);

    // salvar factura
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

    // =========================
    // PRODUTOS
    // =========================
    $stmt = $pdo->prepare("
        SELECT pv.*, p.nome AS nome_produto
        FROM produtos_vendidos pv
        JOIN produtos p ON pv.produto_id = p.id
        WHERE pv.venda_id = ?
    ");
    $stmt->execute([$venda_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // PDF ERP
    // =========================
    $dompdf = new Dompdf();
    ob_start();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial; font-size: 12px; color: #333; }

.header {
    display:flex;
    justify-content:space-between;
    border-bottom:2px solid #0d6efd;
    padding-bottom:10px;
}

h1 { color:#0d6efd; font-size:20px; }

table {
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}

th {
    background:#0d6efd;
    color:white;
    padding:8px;
}

td {
    border:1px solid #ddd;
    padding:8px;
}

.footer {
    margin-top:20px;
    font-style:italic;
    text-align:center;
}
</style>
</head>

<body>

<div class="header">
    <div>
        <h1>Mambo System Sales</h1>
        <p>Maputo • Moçambique</p>
    </div>

    <div>
        <strong>Factura #<?= $factura_id ?></strong><br>
        Tipo: <?= ucfirst($tipo) ?><br>
        Data: <?= date('d/m/Y H:i') ?>
    </div>
</div>

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
    <td colspan="3"><strong>Subtotal</strong></td>
    <td><?= number_format($subtotal, 2, ',', '.') ?></td>
</tr>

<tr>
    <td colspan="3"><strong>IVA (17%)</strong></td>
    <td><?= number_format($iva, 2, ',', '.') ?></td>
</tr>

<tr>
    <td colspan="3"><strong>Total</strong></td>
    <td><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
</tr>

</tbody>
</table>

<div class="footer">
Obrigado por escolher o Mambo System 
</div>

</body>
</html>

<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfDir = __DIR__ . '/../../public/pdf/facturas/';
if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);

file_put_contents($pdfDir . "factura_$factura_id.pdf", $dompdf->output());

echo "<div class='alert alert-success'>✔ Documento gerado com sucesso</div>";
exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Facturação</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }

.hero {
    background:#0d6efd;
    color:white;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
}

.card {
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}
</style>
</head>

<body class="container mt-4">

<!-- HERO ERP -->
<div class="hero">
    <h3>📄 Facturação Inteligente</h3>
    <p>Emissão automática de facturas e controlo de vendas em tempo real no Mambo System 95</p>
</div>

<!-- BUSCA -->
<div class="card mb-4 p-3">
<form method="GET" class="row g-2">

    <div class="col-md-6">
        <input type="text" name="venda_id" class="form-control" placeholder="Número da venda">
    </div>

    <div class="col-md-2">
        <button class="btn btn-primary w-100">Buscar</button>
    </div>

</form>
</div>

<!-- RESULTADO -->
<?php if ($venda): ?>

<div class="card p-3 mb-4">

<h5>Venda #<?= $venda['id'] ?></h5>
<p>Total: MZN <?= number_format($venda['total'], 2, ',', '.') ?></p>

</div>

<!-- FORM CLIENTE -->
<div class="card p-3">

<form method="POST" class="row g-2">

<input type="hidden" name="venda_id" value="<?= $venda_id ?>">
<input type="hidden" name="tipo" value="<?= $tipo ?>">

<div class="col-md-6">
<input class="form-control" name="empresa_nome" placeholder="Empresa" required>
</div>

<div class="col-md-6">
<input class="form-control" name="nuit" placeholder="NUIT" required>
</div>

<div class="col-md-6">
<input class="form-control" name="morada" placeholder="Morada" required>
</div>

<div class="col-md-6">
<input class="form-control" name="contacto" placeholder="Contacto" required>
</div>

<div class="col-md-6">
<input class="form-control" name="email" placeholder="Email" required>
</div>

<div class="col-12">
<button class="btn btn-success w-100" name="gerar_factura">
Gerar Documento 
</button>
</div>

</form>

</div>

<?php endif; ?>

<!-- VOLTAR ERP -->
<?php
$nivel = $_SESSION['nivel'] ?? $_SESSION['nivel_acesso'] ?? 'caixa';

$voltar = match ($nivel) {
    'admin', 'gerente' => '../public/index_admin.php', // ou dashboard principal ERP
    'supervisor'       => '../public/index_supervisor.php',
    'store'            => '../public/venda.php',
    'teka_away'        => '../public/venda_teka_away.php',
    default            => '../public/venda.php'
};
?>

<div class="text-center mt-4">
    <a href="<?= htmlspecialchars($voltar) ?>" class="btn btn-outline-secondary px-4">
        ← Voltar ao Painel
    </a>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>