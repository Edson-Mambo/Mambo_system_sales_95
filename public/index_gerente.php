<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

requireRole(['admin','gerente']);

$pdo = Database::conectar();

/* =========================
   SEGURANÇA BASE
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   DADOS DO UTILIZADOR
========================= */
$usuario = $_SESSION['usuario_nome'] ?? 'Utilizador';
$nivel   = $_SESSION['nivel_acesso'] ?? 'gerente';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Mambo System 95 - Gerente</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>

/* =========================
   BASE ERP DESIGN
========================= */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fa;
}

/* NAVBAR */
.navbar {
    background: #0d6efd;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    height: 60px;
}

.brand {
    font-weight: bold;
}

/* MENU */
.menu {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.menu-item {
    position: relative;
}

.menu-item a {
    color: white;
    text-decoration: none;
    padding: 10px;
    border-radius: 6px;
    display: block;
}

.menu-item a:hover {
    background: rgba(255,255,255,0.15);
}

/* DROPDOWN */
.dropdown {
    position: absolute;
    top: 45px;
    left: 0;
    background: white;
    min-width: 200px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    display: none;
    z-index: 999;
}

.dropdown a {
    color: #333;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.dropdown a:hover {
    background: #f2f2f2;
}

.menu-item:hover .dropdown {
    display: block;
}

/* RIGHT */
.right-menu {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge {
    background: orange;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}

.logout {
    background: red;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
}

/* HERO */
.hero {
    text-align: center;
    padding: 25px;
}

/* KPI */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    padding: 20px;
    max-width: 1200px;
    margin: auto;
}

.kpi-card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    text-align: center;
}

.kpi-value {
    font-size: 22px;
    font-weight: bold;
    color: #0d6efd;
}

.kpi-label {
    font-size: 13px;
    color: #666;
}

/* ACTIONS */
.actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 20px;
}

.action {
    background: #0d6efd;
    color: white;
    padding: 10px 15px;
    border-radius: 10px;
    text-decoration: none;
}

.action:hover {
    background: #084298;
}

</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">

    <div class="brand">🚀 Mambo System 95</div>

    <div class="menu">

        <div class="menu-item">
            <a href="#">Cadastros ▾</a>
            <div class="dropdown">
                <a href="cadastrar_produto.php">Produto</a>
                <a href="cadastrar_usuario.php">Usuário</a>
                
            </div>
        </div>

        <div class="menu-item">
            <a href="#">Relatórios ▾</a>
            <div class="dropdown">
                <a href="relatorio_vendas.php">Vendas</a>
                <a href="relatorio_estoque.php">Estoque</a>
                <
            </div>
        </div>

        <div class="menu-item">
            <a href="label_generator.php">Labels</a>
        </div>

        <div class="menu-item">
            <a href="../src/View/factura_cotacao.view.php">🏷️ Factura e Cotação</a>
        </div>

        <div class="menu-item">
            <a href="alterar_senha.php">Segurança</a>
        </div>

    </div>

    <div class="right-menu">
        <span class="badge"><?= htmlspecialchars($nivel) ?></span>
        <a class="logout" href="logout.php">Sair</a>
    </div>

</div>

<!-- HERO -->
<div class="hero">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario) ?></h2>
    <p>Sistema ERP Mambo System 95</p>
</div>

<!-- KPI -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-value" id="vendasHoje">0</div>
        <div class="kpi-label">Vendas Hoje</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="produtos">0</div>
        <div class="kpi-label">Produtos</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="stock">0</div>
        <div class="kpi-label">Stock Baixo</div>
    </div>

</div>

<!-- ACTIONS -->
<div class="actions">
    <a class="action" href="venda.php">Nova Venda</a>
    <a class="action" href="label_generator.php">Labels</a>
    <a class="action" href="relatorio_vendas.php">Relatórios</a>
</div>

<!-- JS KPIs -->
<script>
async function loadKPIs(){
    try {
        const res = await fetch('api_kpis.php');
        const data = await res.json();

        document.getElementById('vendasHoje').innerText = data.vendasHoje ?? 0;
        document.getElementById('produtos').innerText = data.totalProdutos ?? 0;
        document.getElementById('stock').innerText = data.stockBaixo ?? 0;

    } catch(e){
        console.log('Erro KPI');
    }
}

loadKPIs();
setInterval(loadKPIs, 5000);
</script>

</body>
</html>