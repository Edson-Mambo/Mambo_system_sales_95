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


<!-- BARRA SUPERIOR CUSTOM (Electron POS) -->
<div class="top-bar">
  <button onclick="window.api.minimize()">_</button>
  <button onclick="window.api.maximize()">[]</button>
  <button onclick="window.api.close()">X</button>
</div>


<style>
.top-bar {
  position: fixed;
top: 50px;
  left: 0;
  right: 0;
  height: 40px;
  
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 5px;
  padding: 5px;
}

.top-bar button {
  width: 35px;
  height: 30px;
  border: none;
  cursor: pointer;
  color: white;
  background: #333;
}

.top-bar button:hover {
  background: #555;
}

.top-bar button:last-child {
  background: red;
}
</style>


<style>
body{
    background: linear-gradient(to right, #e3f2fd, #f8f9fa);
    font-family: 'Segoe UI';
}

/* HEADER */
.header {
    background: linear-gradient(90deg,#0d6efd,#084298);
    color:white;
    padding:25px;
    border-radius:16px;
    margin-top:20px;
}

/* CARD ACTIONS */
.action-box{
    background:white;
    border-radius:16px;
    padding:20px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
    height:100%;
}

.action-title{
    font-weight:700;
    margin-bottom:15px;
    color:#0d6efd;
}

.btn-action{
    display:block;
    padding:12px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    margin-bottom:10px;
    transition:.3s;
    text-align:center;
}

.btn-cash{ background:#198754; color:white; }
.btn-cash:hover{ background:#157347; transform:scale(1.02); }

.btn-label{ background:#ffc107; color:#000; }
.btn-label:hover{ background:#e0a800; transform:scale(1.02); }

.btn-report{ background:#0d6efd; color:white; }
.btn-report:hover{ background:#084298; transform:scale(1.02); }

.btn-config{ background:#6c757d; color:white; }
.btn-config:hover{ background:#495057; transform:scale(1.02); }

</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="header text-center">
    <h2>👔 Painel Gerente</h2>
    <p>Gestão operacional completa — vendas, stock, equipa e relatórios</p>
</div>

<!-- ACTION GRID -->
<div class="row mt-4 g-3">

    <!-- CAIXA -->
    <div class="col-md-3">
        <div class="action-box">
            <div class="action-title">🛒 Caixa</div>

            <a href="venda.php" class="btn-action btn-cash">
                Abrir Caixa
            </a>

            <a href="caixa.php" class="btn-action btn-cash">
                Caixa Diário
            </a>
        </div>
    </div>

    <!-- LABELS -->
    <div class="col-md-3">
        <div class="action-box">
            <div class="action-title">🏷️ Labels</div>

            <a href="label_generator.php" class="btn-action btn-label">
                Gerar Etiquetas
            </a>
        </div>
    </div>

    <!-- RELATÓRIOS -->
    <div class="col-md-3">
        <div class="action-box">
            <div class="action-title">📊 Relatórios</div>

            <a href="relatorio_vendas.php" class="btn-action btn-report">Vendas</a>
            <a href="relatorio_estoque.php" class="btn-action btn-report">Stock</a>
            <a href="fecho_dia.php" class="btn-action btn-report">Fecho do Dia</a>
        </div>
    </div>

    <!-- SISTEMA -->
    <div class="col-md-3">
        <div class="action-box">
            <div class="action-title">⚙️ Sistema</div>

            <a href="cadastrar_produto.php" class="btn-action btn-config">Produtos</a>
            <a href="cadastrar_usuario.php" class="btn-action btn-config">Utilizadores</a>
            <a href="configuracoes/configuracoes.php" class="btn-action btn-config">Configurações</a>
        </div>
    </div>

</div>

<!-- LOGOUT -->
<div class="text-center mt-4">
    <a href="logout.php" class="btn btn-danger btn-lg px-5">
        Terminar Sessão
    </a>
</div>

</div>

</body>
</html>