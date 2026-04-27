<?php
require_once '../config/database.php';

session_start();

$pdo = Database::conectar();

/* =========================
   VERIFICA LOGIN
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

/* =========================
   DADOS
========================= */
$relatorio = $pdo->query("
    SELECT 
        a.*,
        p.nome AS produto,
        u.nome AS usuario
    FROM ajustes_estoque a
    JOIN produtos p ON a.produto_id = p.id
    JOIN usuarios u ON a.ajustado_por = u.id
    ORDER BY a.data_ajuste DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   VOLTAR ERP
========================= */
$nivel = $_SESSION['nivel_acesso'] ?? '';

$voltar = [
    'admin' => '../public/index_admin.php',
    'gerente' => '../public/index_gerente.php',
    'supervisor' => '../public/index_supervisor.php'
][$nivel] ?? '../public/index.php';

$dataHoje = date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP | Ajustes de Estoque</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

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

/* CONTAINER ERP */
.erp-container{
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

/* TABLE ERP STYLE */
.table thead{
    background:#1f2937;
    color:#fff;
}

.table tbody tr:hover{
    background:#f3f4f6;
}

/* BADGE STYLE */
.badge-user{
    background:#2563eb;
    color:#fff;
    padding:5px 8px;
    border-radius:6px;
    font-size:12px;
}

.badge-product{
    font-weight:600;
}

/* FOOTER */
.footer{
    margin-top:15px;
    display:flex;
    justify-content:space-between;
    font-size:12px;
    color:#6b7280;
}

/* BOTÕES ERP */
.btn-erp{
    background:#2563eb;
    color:#fff;
    border:none;
    padding:8px 12px;
    border-radius:6px;
}

.btn-erp:hover{
    background:#1e40af;
}

</style>
</head>

<body class="container py-4">

<!-- HEADER ERP -->
<div class="header">
    <h3>📦 Ajustes de Estoque</h3>

    <div>
        <a href="<?= $voltar ?>" class="btn btn-light btn-sm">
            ← Voltar
        </a>
    </div>
</div>

<!-- CONTAINER -->
<div class="erp-container">

<!-- INFO -->
<div class="mb-3 d-flex justify-content-between">
    <div>
        <strong>Total de Registos:</strong> <?= count($relatorio) ?>
    </div>
    <div>
        📅 <?= $dataHoje ?>
    </div>
</div>

<!-- TABELA -->
<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Produto</th>
    <th>Quantidade</th>
    <th>Motivo</th>
    <th>Usuário</th>
    <th>Data</th>
</tr>
</thead>

<tbody>

<?php foreach ($relatorio as $r): ?>

<tr>

    <td class="badge-product">
        <?= htmlspecialchars($r['produto']) ?>
    </td>

    <td>
        <?php if ($r['quantidade_ajustada'] > 0): ?>
            <span class="text-success fw-bold">
                +<?= $r['quantidade_ajustada'] ?>
            </span>
        <?php else: ?>
            <span class="text-danger fw-bold">
                <?= $r['quantidade_ajustada'] ?>
            </span>
        <?php endif; ?>
    </td>

    <td>
        <?= htmlspecialchars($r['motivo']) ?>
    </td>

    <td>
        <span class="badge-user">
            <?= htmlspecialchars($r['usuario']) ?>
        </span>
    </td>

    <td>
        <?= date('d/m/Y H:i', strtotime($r['data_ajuste'])) ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<!-- FOOTER ERP -->
<div class="footer">
    <span>Sistema ERP Mambo System Sales 95</span>
    <span>Relatório de Auditoria de Estoque</span>
</div>

</div>
<script>
function loadAlerts() {
    fetch('/api/alerts.php')
        .then(r => r.json())
        .then(data => {
            console.log(data);

            // aqui atualizas UI (badge, modal, toast)
        });
}

// cada 10 segundos
setInterval(loadAlerts, 10000);

loadAlerts();
</script>
</body>
</html>