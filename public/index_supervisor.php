<?php
session_start();

$timeout = 30 * 60;

// CONTROLO DE SESSÃO
if (isset($_SESSION['usuario_id']) && in_array($_SESSION['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    if (isset($_SESSION['ultimo_acesso'])) {
        $tempo_inativo = time() - $_SESSION['ultimo_acesso'];

        if ($tempo_inativo > $timeout) {
            session_unset();
            session_destroy();
            header("Location: ../login.php?mensagem=Sessão expirada por inatividade.");
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
<title>Mambo System 95 - Supervisor</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>



</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">MamboSystem95 - Supervisor</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menu">

      <ul class="navbar-nav me-auto">

        <li class="nav-item"><a class="nav-link" href="venda.php">Caixa</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastrar_produto.php">Cadastrar Produto</a></li>
        <li class="nav-item"><a class="nav-link" href="relatorio_vendas.php">Vendas</a></li>
        <li class="nav-item"><a class="nav-link" href="relatorio_venda_por_venda.php">Detalhado</a></li>
        <li class="nav-item"><a class="nav-link" href="relatorio_estoque.php">Stock</a></li>
        <li class="nav-item"><a class="nav-link" href="relatorio_estoque.php">Estoque</a></li>
        <li class="nav-item"><a class="nav-link" href="../src/View/relatorio_recepcao.php">Recepção Estoque</a></li>
        

        <!-- BOTÃO LABEL (SEM SUBMENU) -->
        <li class="nav-item">
            <a class="nav-link btn btn-warning text-dark ms-2 px-3" href="label_generator.php">
                🏷️ Labels
            </a>
        </li>

      </ul>

      <a href="logout.php" class="btn btn-danger">Sair</a>

    </div>
  </div>
</nav>

<!-- HEADER -->
<div class="container text-center mt-4">
    <h2 class="text-primary fw-bold">🚀 Painel Supervisor</h2>
    <p class="text-muted">
        Monitorização operacional em tempo real — vendas, stock e desempenho.
    </p>
</div>

<!-- KPI DASHBOARD -->
<div class="container">
    <div class="row g-3 justify-content-center">

        <div class="col-md-2">
            <div class="kpi-card">
                💰 <div class="value" id="vendasHoje">MZN 0.00</div>
                <div class="label">Vendas Hoje</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="kpi-card">
                💳 <div class="value" id="faturacaoTotal">MZN 0.00</div>
                <div class="label">Faturação Total</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="kpi-card">
                📦 <div class="value" id="totalProdutos">0</div>
                <div class="label">Produtos</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="kpi-card">
                ⚠️ <div class="value" id="stockBaixo">0</div>
                <div class="label">Stock Baixo</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="kpi-card">
                👤 <div class="value" id="totalUsuarios">0</div>
                <div class="label">Utilizadores</div>
            </div>
        </div>

    </div>
</div>

<!-- JS KPIs -->
<script>
async function carregarKPIs() {

    try {
        const res = await fetch('api_kpis.php');
        const data = await res.json();

        const vendas = document.getElementById('vendasHoje');
        const fat = document.getElementById('faturacaoTotal');
        const prod = document.getElementById('totalProdutos');
        const stock = document.getElementById('stockBaixo');
        const users = document.getElementById('totalUsuarios');

        if (!vendas || !fat || !prod || !stock || !users) return;

        vendas.innerText = "MZN " + Number(data.vendasHoje || 0).toFixed(2);
        fat.innerText = "MZN " + Number(data.faturacaoTotal || 0).toFixed(2);
        prod.innerText = data.totalProdutos || 0;
        stock.innerText = data.stockBaixo || 0;
        users.innerText = data.totalUsuarios || 0;

    } catch (e) {
        console.error("Erro KPIs Supervisor:", e);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    carregarKPIs();
    setInterval(carregarKPIs, 5000);
});
</script>

</body>
</html>