<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin', 'gerente']);

$pdo = Database::conectar();

/* =========================
   LOG SAFE
========================= */
function safeLog($pdo, $type, $action, $description)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, type, action, description, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $type,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

    } catch (Exception $e) {}
}

/* =========================
   TABELAS ERP
========================= */
$tabelas = [
    "ajustes_estoque","carrinho_temp","categorias","clientes",
    "configuracoes","cotacao_itens","cotacoes","facturas",
    "fechos","fechos_dia","inventario_fisico","itens_vale",
    "itens_vendas_vales","itens_venda_teka_away","logs",
    "logs_login","logs_password_reset","logs_security",
    "logs_sistema","movimento_estoque","pagamentos_vale",
    "password_resets","password_reset_logs","produtos",
    "produtos_takeaway","produtos_vendidos",
    "produtos_vendidos_takeaway","recepcao_estoque",
    "usuarios","vales","vale_produtos","vendas",
    "vendas_takeaway","vendas_teka_away","vendas_vales"
];

$resultados = [];

/* =========================
   SCAN
========================= */
foreach ($tabelas as $tabela) {

    try {

        $check = $pdo->query("SHOW TABLES LIKE '$tabela'");

        if ($check->rowCount() == 0) {
            $resultados[] = [
                "tabela" => $tabela,
                "status" => "MISSING",
                "total" => 0
            ];
            continue;
        }

        $total = $pdo->query("SELECT COUNT(*) FROM `$tabela`")->fetchColumn();

        $status = "OK";

        if ($total == 0) $status = "EMPTY";
        elseif ($total < 5) $status = "LOW";

        $resultados[] = [
            "tabela" => $tabela,
            "status" => $status,
            "total" => $total
        ];

    } catch (Exception $e) {

        $resultados[] = [
            "tabela" => $tabela,
            "status" => "ERROR",
            "total" => 0
        ];
    }
}

/* =========================
   STATS
========================= */
$totalTables = count($resultados);
$errors = count(array_filter($resultados, fn($r)=>$r['status']=='ERROR'));
$empty = count(array_filter($resultados, fn($r)=>$r['status']=='EMPTY'));
$low = count(array_filter($resultados, fn($r)=>$r['status']=='LOW'));

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Auditoria ERP</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f5f7fb;
}

/* HEADER */
.header{
    background:white;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

/* CARDS */
.stat-card{
    background:white;
    padding:18px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
}

.stat-card h3{
    font-size:26px;
    margin:0;
}

/* BADGES */
.badge-ok{background:#198754;}
.badge-empty{background:#fd7e14;}
.badge-low{background:#ffc107; color:#000;}
.badge-error{background:#dc3545;}
.badge-missing{background:#6c757d;}

/* TABLE */
.table-container{
    background:white;
    border-radius:12px;
    padding:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

table{
    font-size:14px;
}

tbody tr:hover{
    background:#f1f5ff;
}

</style>
</head>

<body class="container py-4">

<!-- HEADER -->
<div class="header">
    <h3>🧠 Auditoria Inteligente do ERP</h3>
    <small>Verificação estrutural de base de dados e integridade do sistema</small>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <h3><?= $totalTables ?></h3>
            <small>Tabelas analisadas</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3 class="text-danger"><?= $errors ?></h3>
            <small>Erros críticos</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3 class="text-warning"><?= $empty ?></h3>
            <small>Tabelas vazias</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3 class="text-warning"><?= $low ?></h3>
            <small>Baixa atividade</small>
        </div>
    </div>

</div>

<!-- TABLE -->
<div class="table-container">

<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Tabela</th>
    <th>Status</th>
    <th>Registos</th>
</tr>
</thead>

<tbody>

<?php foreach ($resultados as $r): ?>

<tr>
    <td><?= $r['tabela'] ?></td>

    <td>
        <?php if ($r['status']=='OK'): ?>
            <span class="badge badge-ok">OK</span>

        <?php elseif ($r['status']=='EMPTY'): ?>
            <span class="badge badge-empty">VAZIA</span>

        <?php elseif ($r['status']=='LOW'): ?>
            <span class="badge badge-low">BAIXA</span>

        <?php elseif ($r['status']=='ERROR'): ?>
            <span class="badge badge-error">ERRO</span>

        <?php else: ?>
            <span class="badge badge-missing">FALTA</span>
        <?php endif; ?>
    </td>

    <td><?= $r['total'] ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<!-- VOLTAR -->
<div class="text-center mt-4">
    <a href="javascript:history.back()" class="btn btn-outline-secondary">
        ← Voltar
    </a>
</div>

</body>
</html>