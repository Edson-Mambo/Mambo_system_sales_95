<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin']);

$pdo = Database::conectar();

$erro = '';
$mensagem = '';

/* =========================
   🔐 RESET ADMIN CONTROLADO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_reset') {

    if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], ['admin', 'gerente'])) {
        die("Acesso negado.");
    }

    $identificador  = trim($_POST['identificador'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    // 🔍 Buscar admin logado
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($admin_password, $admin['senha'])) {
        $erro = "Senha de administrador incorreta.";
    } else {

        // 🔍 Buscar usuário alvo
        $stmt = $pdo->prepare("
            SELECT id, nome, email 
            FROM usuarios 
            WHERE email = ? OR nome = ?
            LIMIT 1
        ");
        $stmt->execute([$identificador, $identificador]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $erro = "Usuário não encontrado.";
        } else {

            // 🔐 Gerar nova senha segura
            $novaSenha = bin2hex(random_bytes(4));
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);

            $mensagem = "Nova senha gerada para {$user['nome']}: <strong>$novaSenha</strong>";
        }
    }
}

/* =========================
   ⏱ CONTROLE DE SESSÃO
========================= */
if (isset($_SESSION['usuario_id'])) {

    $tempoLimite = 1800; // 30 minutos

    if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempoLimite) {
        session_unset();
        session_destroy();
        header("Location: login.php?expirado=1");
        exit;
    }

    $_SESSION['ultimo_acesso'] = time();
}

/* =========================
   🌐 IDIOMA
========================= */
$idioma = $_SESSION['lang'] ?? 'pt';
require_once __DIR__ . '/../config/lang.php';

/* =========================
   📊 KPIs (ROBUSTO)
========================= */

try {

    $stmt = $pdo->query("
        SELECT
            (SELECT IFNULL(SUM(total),0) FROM vendas WHERE DATE(data_venda) = CURDATE()) AS vendasHoje,
            (SELECT COUNT(*) FROM produtos) AS totalProdutos,
            (SELECT COUNT(*) FROM produtos WHERE stock <= 5) AS stockBaixo,
            (SELECT COUNT(*) FROM usuarios) AS totalUsuarios
    ");

    $kpis = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

    $vendasHoje    = $kpis['vendasHoje']    ?? 0;
    $totalProdutos = $kpis['totalProdutos'] ?? 0;
    $stockBaixo    = $kpis['stockBaixo']    ?? 0;
    $totalUsuarios = $kpis['totalUsuarios'] ?? 0;

} catch (PDOException $e) {

    // ⚠️ Evita quebrar o sistema em produção
    $vendasHoje = 0;
    $totalProdutos = 0;
    $stockBaixo = 0;
    $totalUsuarios = 0;

    // Em ambiente dev podes ativar:
    // echo $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Mambo System 95 - Admin</title>





<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fa;
}

/* TOP BAR */
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
    min-width: 220px;
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

/* RIGHT */
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
</style>

</head>

<body>

<!-- NAVBAR CUSTOM -->
<div class="navbar">

    <div class="brand">🚀 Mambo System 95</div>

    <div class="menu">

        <!-- CADASTROS -->
        <div class="menu-item">
            <a href="#">Cadastros ▾</a>
            <div class="dropdown">
                <a href="cadastrar_produto.php">Cadastrar Produto</a>
                <a href="cadastrar_usuario.php">Cadastrar Usuário</a>
                 <!-- <a href="cadastrar_produto_takeaway.php">Take Away Produto</a>-->
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
                <a href="../src/View/inventario.view.php">Inventário</a>
                  <!-- <a href="listar_takeaway.php">Take Away</a>-->
            </div>
        </div>

        <!-- VALES -->
        <!--<div class="menu-item">
            <a href="#">Vales ▾</a>
          <div class="dropdown">
                <a href="../src/View/view_vale_formulario.php">Emitir Vales</a>
                <a href="listar_vales.php">Histórico</a>
            </div>
        </div>-->

        <div class="menu-item">
            <a href="label_generator.php">🏷️ Label</a>
        </div>

        <!-- RELATÓRIOS -->
        <div class="menu-item">
            <a href="#">Relatórios ▾</a>
            <div class="dropdown">
                <a href="relatorio_vendas.php">Vendas</a>
                <a href="relatorio_venda_por_venda.php">Detalhado</a>
                 <!-- <a href="relatorios_teka_away.php">Take Away</a>-->
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
                <!-- <a href="caixa.php">Caixa</a>-->
            </div>
        </div>

        <!-- TAKE AWAY -->
          <!--<div class="menu-item">
            <a href="#">Take Away ▾</a>
            <div class="dropdown">
                <a href="teka_away_menu.php">Menu Take Away</a>
                <a href="pedidos.php">Pedidos</a>
                <a href="entregas.php">Entregas</a>
            </div>
        </div>-->

        <!-- CONFIGURAÇÕES -->
        <div class="menu-item">
            <a href="#">Configurações ▾</a>
            <div class="dropdown">
                <a href="configuracoes/configuracoes.php">Sistema</a>
                <a href="empresa.php">Empresa</a>
                <!--<a href="backup.php">Backup</a>-->
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
                <a href="system_scan.php">Logs</a>
                <a href="permissoes.php">Permissões</a>
                <a href="alterar_senha.php">Alterar Senha</a>
            </div>
        </div>

    </div>

    <div class="right-menu">
        <span class="badge">Admin</span>
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

<!-- ACÇÕES RÁPIDAS -->
<section style="padding: 0 20px 40px;">
    <h3 style="text-align:center; margin-bottom:20px;">⚡ Ações Rápidas</h3>

    <div style="display:flex; gap:15px; flex-wrap:wrap; justify-content:center;">

        <a class="action-card" href="venda.php">🛒 Nova Venda</a>
        <a class="action-card" href="cadastrar_produto.php">➕ Novo Produto</a>
        <a class="action-card" href="ajustar_estoque.php">📊 Ajustar Stock</a>
        <a class="action-card" href="relatorio_vendas.php">📈 Relatórios</a>
        <a class="action-card" href="listar_vales.php">🎟️ Vales</a>

    </div>
</section>

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