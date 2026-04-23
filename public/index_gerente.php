<?php
session_start();

require_once "../middleware/auth.php";
requireRole(['admin','gerente']);

$timeout = 30 * 60;

if (isset($_SESSION['usuario_id']) && in_array($_SESSION['nivel_acesso'], ['admin','gerente'])) {

    if (isset($_SESSION['ultimo_acesso'])) {
        if (time() - $_SESSION['ultimo_acesso'] > $timeout) {
            session_unset();
            session_destroy();
            header("Location: ../login.php?mensagem=Sessão expirada.");
            exit();
        }
    }

    $_SESSION['ultimo_acesso'] = time();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Mambo System 95 - Gerente</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>

/* BASE */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fa;
}

/* NAVBAR IGUAL ADMIN */
.navbar {
    background: #0d6efd;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    height: 60px;
}

.navbar .brand {
    font-weight: bold;
    font-size: 18px;
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
    padding: 10px 12px;
    display: block;
    border-radius: 6px;
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
    display: block;
    border-bottom: 1px solid #eee;
}

.dropdown a:hover {
    background: #f2f2f2;
}

.menu-item:hover .dropdown {
    display: block;
}

/* RIGHT MENU */
.right-menu {
    display: flex;
    gap: 10px;
    align-items: center;
}

.badge {
    background: orange;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}

.logout {
    background: red;
    padding: 8px 12px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
}

/* HERO */
.hero {
    padding: 30px 20px;
    text-align: center;
}

/* KPI GRID */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

.kpi-card {
    background: white;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    text-align: center;
    transition: 0.3s;
}

.kpi-card:hover {
    transform: translateY(-5px);
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
.action-section {
    padding: 0 20px 40px;
}

.action-title {
    text-align: center;
    margin-bottom: 20px;
}

.action-grid {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
}

.action-card {
    background: #0d6efd;
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}

.action-card:hover {
    background: #084298;
    transform: scale(1.05);
}

</style>
</head>

<body>

<!-- NAVBAR  -->
<div class="navbar">

    <div class="brand">🚀 Mambo System 95</div>

    <div class="menu">

        <!-- CADASTROS -->
        <div class="menu-item">
            <a href="#">Cadastros ▾</a>
            <div class="dropdown">
                <a href="cadastrar_produto.php">Cadastrar Produto</a>
                <a href="cadastrar_usuario.php">Cadastrar Usuário</a>
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

        <!-- RELATÓRIOS -->
        <div class="menu-item">
            <a href="#">Relatórios ▾</a>
            <div class="dropdown">
                <a href="relatorio_vendas.php">Vendas</a>
                <a href="relatorio_venda_por_venda.php">Detalhado</a>
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
            </div>
        </div>

        <!-- IDIOMA -->
        <div class="menu-item">
            <a href="#">🌐 Idioma ▾</a>
            <div class="dropdown">
                <a href="?lang=pt">Português</a>
                <a href="?lang=en">English</a>
                <a href="?lang=es">Español</a>
            </div>
        </div>

        <!-- SEGURANÇA -->
        <div class="menu-item">
            <a href="#">🔒 Segurança ▾</a>
            <div class="dropdown">
                <a href="alterar_senha.php">Alterar Senha</a>
            </div>
        </div>

    </div>

    <div class="right-menu">
        <span class="badge">Gerente</span>
        <a class="logout" href="logout.php">⛔ Sair</a>
    </div>

</div>

<!-- HERO -->

<!-- DASHBOARD HERO MELHORADO -->
<section style="padding:30px 20px; text-align:center;">
    <h1 style="margin:0; font-size:32px; color:#0d6efd;">
        🚀 Bem-vindo ao Mambo System 95
    </h1>
    <p style="margin-top:10px; color:#6c757d; font-size:14px; line-height:1.6;">
    Sistema inteligente de monitorização operacional em tempo real — vendas, stock, utilizadores e métricas estratégicas centralizadas num único painel.
</p>
</section>

<!-- KPI DASHBOARD -->
<section style="padding: 20px;">

    <div style="
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        max-width: 1200px;
        margin: auto;
    ">

        <div class="kpi-card">
            💰 <div class="value" id="vendasHoje">MZN 0.00</div>
            <div class="label">Vendas Hoje</div>
        </div>

        <div class="kpi-card">
            💳 <div class="value" id="faturacaoTotal">MZN 0.00</div>
            <div class="label">Faturação Total</div>
        </div>

        <div class="kpi-card">
            📦 <div class="value" id="totalProdutos">0</div>
            <div class="label">Produtos</div>
        </div>

        <div class="kpi-card">
            ⚠️ <div class="value" id="stockBaixo">0</div>
            <div class="label">Stock Baixo</div>
        </div>

        <div class="kpi-card">
            👤 <div class="value" id="totalUsuarios">0</div>
            <div class="label">Utilizadores</div>
        </div>

    </div>

</section>

<!-- ACTIONS -->
<div class="action-section">

    <h3 class="action-title">⚡ Ações Rápidas</h3>

    <div class="action-grid">

        <a class="action-card" href="venda.php">🛒 Caixa</a>
        <a class="action-card" href="label_generator.php">🏷️ Labels</a>
        <a class="action-card" href="relatorio_vendas.php">📊 Vendas</a>
        <a class="action-card" href="relatorio_estoque.php">📦 Stock</a>
        <a class="action-card" href="fecho_dia.php">📈 Fecho</a>

    </div>

</div>

<style>

/* KPI CARDS */
.kpi-card {
    width: 220px;
    background: white;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    text-align: center;
    transition: 0.3s;
}

.kpi-card:hover {
    transform: translateY(-6px);
}

.kpi-card .icon {
    font-size: 26px;
    margin-bottom: 8px;
}

.kpi-card .value {
    font-size: 22px;
    font-weight: bold;
    color: #0d6efd;
}

.kpi-card .label {
    font-size: 13px;
    color: #666;
}

/* ACTION CARDS */
.action-card {
    background: #0d6efd;
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}

.action-card:hover {
    background: #084298;
    transform: scale(1.05);
}
</style>
<!-- MODAL SEGURANÇA -->
<div id="securityModal" style="display:none;">
    <form method="POST">

        <input type="hidden" name="action" value="admin_reset">

        <input type="text" name="identificador" placeholder="Email ou Nome" required>
        <input type="password" name="admin_password" placeholder="Senha Admin" required>

        <button type="submit">Reset Password</button>

        <?php if ($erro) echo "<p style='color:red'>$erro</p>"; ?>
        <?php if ($mensagem) echo "<p style='color:green'>$mensagem</p>"; ?>

    </form>
</div>


<script>
async function carregarKPIs() {

    try {
        const res = await fetch('api_kpis.php');
        const data = await res.json();

        document.getElementById('vendasHoje').innerText =
            "MZN " + Number(data.vendasHoje ?? 0).toFixed(2);

        document.getElementById('faturacaoTotal').innerText =
            "MZN " + Number(data.faturacaoTotal ?? 0).toFixed(2);

        document.getElementById('totalProdutos').innerText =
            data.totalProdutos ?? 0;

        document.getElementById('stockBaixo').innerText =
            data.stockBaixo ?? 0;

        document.getElementById('totalUsuarios').innerText =
            data.totalUsuarios ?? 0;

    } catch (e) {
        console.error("Erro ao carregar KPIs:", e);
    }
}

carregarKPIs();
setInterval(carregarKPIs, 5000);
</script>

</body>
</html>

