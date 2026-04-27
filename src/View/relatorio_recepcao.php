<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$pdo = Database::conectar();

/* =========================
   AUTH ERP
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

$nivel = $_SESSION['nivel_acesso'] ?? '';

$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos)) {
    header("Location: ../../public/index.php");
    exit;
}

/* =========================
   VOLTAR ERP
========================= */
$voltar = [
    'admin' => '../../public/index_admin.php',
    'gerente' => '../../public/index_gerente.php',
    'supervisor' => '../../public/index_supervisor.php'
][$nivel] ?? '../../public/index.php';

/* =========================
   FILTRO
========================= */
$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';

$sql = "
SELECT 
    re.*,
    p.nome AS produto
FROM recepcao_estoque re
JOIN produtos p ON p.id = re.produto_id
";

$params = [];

if ($data_inicial && $data_final) {
    $sql .= " WHERE DATE(re.data_recebimento) BETWEEN :inicio AND :fim";
    $params[':inicio'] = $data_inicial;
    $params[':fim'] = $data_final;
}

$sql .= " ORDER BY re.data_recebimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recepcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dataHoje = date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP | Recepção de Estoque</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>

/* =========================
   ERP BASE
========================= */
body{
    background:#f4f6f9;
}

/* HEADER ERP */
.header{
    background:#111827;
    color:#fff;
    padding:15px 20px;
    border-radius:10px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:15px;
}

.header h3{
    margin:0;
    font-size:18px;
}

/* CARD ERP */
.erp-card{
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

/* TABLE */
.table thead{
    background:#1f2937;
    color:#fff;
}

.table tbody tr:hover{
    background:#f3f4f6;
}

/* BADGES */
.badge-product{
    font-weight:600;
}

.badge-qty{
    font-weight:700;
}

/* FOOTER INFO */
.footer-info{
    display:flex;
    justify-content:space-between;
    font-size:12px;
    color:#6b7280;
    margin-top:10px;
}

/* BUTTONS ERP */
.btn-erp{
    border-radius:6px;
    padding:6px 10px;
}

/* FILTER BOX */
.filter-box{
    background:#fff;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

</style>
</head>

<body class="container py-4">

<!-- HEADER -->
<div class="header">
    <h3>📦 Recepção de Estoque</h3>

    <a href="<?= $voltar ?>" class="btn btn-light btn-sm">
        ← Voltar
    </a>
</div>

<!-- FILTER -->
<div class="filter-box">

<form class="row g-3" method="GET">

    <div class="col-md-4">
        <label class="form-label">Data Inicial</label>
        <input type="date" name="data_inicial" class="form-control"
               value="<?= htmlspecialchars($data_inicial) ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Data Final</label>
        <input type="date" name="data_final" class="form-control"
               value="<?= htmlspecialchars($data_final) ?>">
    </div>

    <div class="col-md-4 d-flex align-items-end gap-2">
        <button class="btn btn-primary btn-erp">Filtrar</button>
        <a href="?" class="btn btn-outline-secondary btn-erp">Limpar</a>
    </div>

</form>

</div>

<!-- TABLE CARD -->
<div class="erp-card">

<div class="d-flex justify-content-between mb-2">
    <div>
        <strong>Total:</strong> <?= count($recepcoes) ?> registos
    </div>
    <div>
        📅 <?= $dataHoje ?>
    </div>
</div>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>#</th>
    <th>Produto</th>
    <th>Quantidade</th>
    <th>Unidade</th>
    <th>Data</th>
    <th>Observação</th>
</tr>
</thead>

<tbody>

<?php foreach ($recepcoes as $i => $r): ?>

<tr>

    <td><?= $i + 1 ?></td>

    <td class="badge-product">
        <?= htmlspecialchars($r['produto']) ?>
    </td>

    <td>
        <span class="badge-qty">
            <?= number_format($r['quantidade_recebida'], 2, ',', '.') ?>
        </span>
    </td>

    <td>
        <?= $r['unidade'] === 'peca' ? 'Peça(s)' : 'Grama(s)' ?>
    </td>

    <td>
        <?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['observacao']) ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php if (empty($recepcoes)): ?>
    <div class="alert alert-warning text-center mt-3">
        Nenhuma recepção encontrada.
    </div>
<?php endif; ?>

<div class="footer-info">
    <span>Sistema Mambo System Sales 95</span>
    <span>Relatório de Recepção de Estoque</span>
</div>

</div>

<!-- EXPORT (mantido simples) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-table2excel/1.1.2/jquery.table2excel.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
</script>

</body>
</html>