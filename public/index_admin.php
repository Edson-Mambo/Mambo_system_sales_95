<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin']);

$pdo = Database::conectar();

/* =========================
   SEGURANÇA ERP
========================= */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================
   TIMEOUT ERP
========================= */
$timeout = 1800;

if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso'] > $timeout)) {
    session_destroy();
    header("Location: ../login.php?expirado=1");
    exit;
}

$_SESSION['ultimo_acesso'] = time();

/* =========================
   KPIs (BACKEND + FALLBACK)
========================= */
try {

    $stmt = $pdo->query("
        SELECT
            (SELECT IFNULL(SUM(total),0) FROM vendas WHERE DATE(data_venda)=CURDATE()) AS vendasHoje,
            (SELECT IFNULL(SUM(total),0) FROM vendas) AS faturacaoTotal,
            (SELECT COUNT(*) FROM produtos) AS totalProdutos,
            (SELECT COUNT(*) FROM produtos WHERE stock <= 5) AS stockBaixo,
            (SELECT COUNT(*) FROM usuarios) AS totalUsuarios
    ");

    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {

    $kpi = [
        'vendasHoje'=>0,
        'faturacaoTotal'=>0,
        'totalProdutos'=>0,
        'stockBaixo'=>0,
        'totalUsuarios'=>0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Mambo System 95 - Admin</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>

/* ================= BASE ERP ================= */
body{
    margin:0;
    font-family:Arial;
    background:#f5f6fa;
}

/* ================= NAVBAR ORIGINAL ================= */
.navbar{
    background:#0d6efd;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 20px;
    height:60px;
}

.brand{
    font-weight:bold;
}

.menu{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.menu-item{
    position:relative;
}

.menu-item a{
    color:white;
    text-decoration:none;
    padding:10px 12px;
    display:block;
    border-radius:6px;
}

.menu-item a:hover{
    background:rgba(255,255,255,0.15);
}

.dropdown{
    position:absolute;
    top:45px;
    left:0;
    background:white;
    min-width:220px;
    border-radius:8px;
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
    display:none;
    z-index:999;
}

.dropdown a{
    color:#333;
    padding:10px;
    display:block;
    border-bottom:1px solid #eee;
}

.menu-item:hover .dropdown{
    display:block;
}

.right-menu{
    display:flex;
    gap:10px;
    align-items:center;
}

.badge{
    background:orange;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

.logout{
    background:red;
    padding:8px 12px;
    border-radius:6px;
    color:white;
    text-decoration:none;
}

/* ================= HERO ERP PRO ================= */
.hero{
    text-align:center;
    padding:45px 20px;
}

.hero h1{
    font-size:36px;
    color:#0d6efd;
    margin-bottom:10px;
}

.hero p{
    max-width:950px;
    margin:auto;
    color:#555;
    font-size:15px;
    line-height:1.7;
}

.hero-tags{
    margin-top:18px;
    display:flex;
    justify-content:center;
    flex-wrap:wrap;
    gap:10px;
}

.hero-tags span{
    background:#e7f1ff;
    color:#0d6efd;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
}

/* ================= KPI GRID ================= */
.kpi-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:15px;
    padding:20px;
    max-width:1200px;
    margin:auto;
}

.kpi-card{
    background:white;
    padding:22px;
    border-radius:16px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    text-align:center;
    transition:.3s;
}

.kpi-card:hover{
    transform:translateY(-5px);
}

.kpi-value{
    font-size:22px;
    font-weight:bold;
    color:#0d6efd;
}

.kpi-label{
    font-size:13px;
    color:#666;
}

</style>
</head>

<body>

<!-- ================= MENU ORIGINAL ================= -->
<div class="navbar">

    <div class="brand">🚀 Mambo System 95</div>

    <div class="menu">

        <!-- CADASTROS -->
        <div class="menu-item">
            <a href="#">Cadastros ▾</a>
            <div class="dropdown">
                <a href="cadastrar_produto.php">Cadastrar Produto</a>
                <a href="cadastrar_usuario.php">Cadastrar Usuário</a>
                <a href="ajustar_estoque.php">Ajustar Estoque</a>
                <a href="../src/View/recepcao_estoque.view.php">Recepção de Estoque</a>
            </div>
        </div>

        <!-- LISTAGEM -->
        <div class="menu-item">
            <a href="#">Listagem ▾</a>
            <div class="dropdown">
                <a href="../src/View/listar_usuario.php">Listar Usuários</a>
                <a href="../src/View/listar_produtos.view.php">Produtos</a>
                
            </div>
        </div>

        <div class="menu-item">
            <a href="label_generator.php">🏷️ Label</a>
        </div>


        <div class="menu-item">
            <a href="../src/View/inventario.view.php">Inventário</a>
        </div>        

        <!-- RELATÓRIOS -->
        <div class="menu-item">
            <a href="#">Relatórios ▾</a>
            <div class="dropdown">
                <a href="relatorio_vendas.php">Vendas</a>
                <a href="relatorio_venda_por_venda.php">Detalhado</a>
                <a href="relatorio_logins.php">Logins</a>
                <a href="relatorio_estoque.php">Estoque</a>
                <a href="../src/View/relatorio_recepcao.php">Recepção Estoque</a>
            </div>
        </div>

        <!-- VENDA -->
        <div class="menu-item">
            <a href="#">Venda ▾</a>
            <div class="dropdown">
                <a href="venda.php">Nova Venda</a>
            </div>
        </div>

        <!-- CONFIGURAÇÕES -->
        <div class="menu-item">
            <a href="#">Configurações ▾</a>
            <div class="dropdown">
                <a href="configuracoes/configuracoes.php">Sistema</a>
                <a href="configuracoes_empresa.php">Info Empresa</a>
            </div>
        </div>

        <!-- SEGURANÇA -->
        <div class="menu-item">
            <a href="#">🔒 Segurança ▾</a>
            <div class="dropdown">
                <a href="system_scan.php">Logs</a>
                <a href="permissoes.php">Permissões</a>
                <a href="alterar_senha.php">Alterar Senha</a>
            </div>
        </div>

    </div>

    <div class="right-menu">
        <span class="badge">ADMIN</span>
        <a class="logout" href="logout.php">Sair</a>
    </div>

</div>

<!-- ================= HERO ERP ================= -->
<section class="hero">
    <h1>ERP Mambo System 95</h1>
    <p>
        Sistema ERP profissional desenvolvido para gestão completa de operações empresariais em tempo real.
        Integra vendas, stock, utilizadores, relatórios avançados e auditoria centralizada, permitindo controlo total e decisões estratégicas baseadas em dados.
    </p>

    <div class="hero-tags">
        <span>📦 Stock Inteligente</span>
        <span>💰 Vendas em Tempo Real</span>
        <span>👤 Gestão de Utilizadores</span>
        <span>🔐 Segurança ERP</span>
        <span>📊 Relatórios Avançados</span>
    </div>
</section>

<!-- ================= KPI ================= -->
<section class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-value" id="vendasHoje"><?= number_format($kpi['vendasHoje'],2) ?></div>
        <div class="kpi-label">Vendas Hoje</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="faturacaoTotal"><?= number_format($kpi['faturacaoTotal'],2) ?></div>
        <div class="kpi-label">Faturação Total</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="totalProdutos"><?= $kpi['totalProdutos'] ?></div>
        <div class="kpi-label">Produtos</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="stockBaixo"><?= $kpi['stockBaixo'] ?></div>
        <div class="kpi-label">Stock Baixo</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value" id="totalUsuarios"><?= $kpi['totalUsuarios'] ?></div>
        <div class="kpi-label">Utilizadores</div>
    </div>

</section>

<!-- ================= JS ERP ================= -->
<script>
async function loadKPIs(){

    try{
        const res = await fetch('api_kpis.php');
        const d = await res.json();

        document.getElementById('vendasHoje').innerText = Number(d.vendasHoje || 0).toFixed(2);
        document.getElementById('faturacaoTotal').innerText = Number(d.faturacaoTotal || 0).toFixed(2);
        document.getElementById('totalProdutos').innerText = d.totalProdutos || 0;
        document.getElementById('stockBaixo').innerText = d.stockBaixo || 0;
        document.getElementById('totalUsuarios').innerText = d.totalUsuarios || 0;

    } catch(e){
        console.log("ERP offline mode");
    }
}

loadKPIs();
setInterval(loadKPIs, 5000);
</script>

</body>
</html>