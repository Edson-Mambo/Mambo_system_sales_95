<?php


if (isset($_SESSION['usuario_id']) && in_array($_SESSION['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    // Apenas atualiza o último acesso (opcional, pode até remover se não precisar)
    $_SESSION['ultimo_acesso'] = time();
}

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Idioma atual
$idioma = $_SESSION['lang'] ?? 'pt';

include '../src/Template/header.php';
require_once __DIR__ . '/../config/translate.php';



?>




<!DOCTYPE html>
<html lang="pt-MZ">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Painel Admin - Mambo System 95</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
  <link href="http://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">🚀 Mambo System 95</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- Cadastros -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="cadastroDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-plus"></i> Cadastros
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="cadastrar_produto.php">Cadastrar Produto</a></li>
            <li><a class="dropdown-item" href="cadastrar_usuario.php">Cadastrar Usuário</a></li>
            <li><a class="dropdown-item" href="cadastrar_produto_takeaway.php">Cadastrar Take Away</a></li>
            <li><a class="dropdown-item" href="ajustar_estoque.php">Ajustar Estoque</a></li>
            <li><a class="dropdown-item" href="../src/View/recepcao_estoque.view.php" class="nav-link">Recepção de Estoque</a></li>

          </ul>
        </li>

        <!-- Listagem -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="listagemDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-list"></i> Listagem
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="../src/View/listar_usuario.php">Listar Usuarios</a></li>
            <li><a class="dropdown-item" href="../src/View/listar_produtos.view.php">Produtos</a></li>
            <li><a class="dropdown-item" href="../src/View/inventario.view.php">Inventário</a></li>
            <li><a class="dropdown-item" href="listar_takeaway.php">Take Away</a></li>
          </ul>
        </li>

        <!-- Vales -->
        <li class="nav-item">
          <a class="nav-link" href="listar_vales.php">
            <i class="fa-solid fa-ticket"></i> Vales
          </a>
        </li>

        <!-- Relatórios -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="relatorioDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-chart-line"></i> Relatórios
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="relatorio_vendas.php">Relatório Vendas</a></li>
            <li><a class="dropdown-item" href="relatorio_venda_por_venda.php">Relatório Detalhado</a></li>
            
        
            <li><a class="dropdown-item" href="relatorios_teka_away.php">Relatório Take Away</a></li>
            <li><a class="dropdown-item" href="relatorio_logins.php">Relatório de Logins</a></li>
            <li><a class="dropdown-item" href="relatorio_estoque.php">Relatório de Estoque</a></li>
            <li><a class="dropdown-item" href="../src/View/relatorio_recepcao.php">Relatório de Estoque Recebido</a></li>
           
          </ul>
        </li>

        <!-- Venda -->
        <li class="nav-item">
          <a class="nav-link" href="venda.php">
            <i class="fa-solid fa-shopping-cart"></i> Venda
          </a>
        </li>

        <!-- Take Away -->
        <li class="nav-item">
          <a class="nav-link" href="teka_away_menu.php">
            <i class="fa-solid fa-utensils"></i> Take Away
          </a>
        </li>

        <!-- Configurações -->
        <li class="nav-item">
          <a class="nav-link" href="configuracoes/configuracoes.php">
            <i class="fa-solid fa-gear"></i> Configurações
          </a>
        </li>

       <!-- Dropdown de Idioma -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                🌐 <?= __('language'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                <li>
                    <a class="dropdown-item <?= $idioma == 'pt' ? 'active' : '' ?>" href="?lang=pt">
                        🇲🇿 Português
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $idioma == 'en' ? 'active' : '' ?>" href="?lang=en">
                        🇬🇧 English
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $idioma == 'es' ? 'active' : '' ?>" href="?lang=es">
                        🇪🇸 Español
                    </a>
                </li>
            </ul>
        </li>

      </ul>

      <!-- Botão Logout -->
      <div class="d-flex">
        <a href="logout.php" class="btn btn-danger btn-lg">
          <i class="fa-solid fa-right-from-bracket"></i> Terminar Sessão
        </a>
      </div>
    </div>
  </div>
</nav>



<section class="hero bg-light text-center p-5 mb-5 shadow-sm">
  <div class="container">
    <h1 class="display-4 fw-bold text-primary mb-3">Bem-vindo, Admin!</h1>
    <p class="lead mb-4">Gerencie vendas, estoques, relatórios e muito mais com eficiência e segurança.<br> 
      O <strong>Mambo System 95</strong> é a força que move o seu negócio para o sucesso.</p>
   
  </div>
</section>

<main class="container">
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card dashboard-card text-center p-4">
        <i class="fa-solid fa-cubes fa-3x text-primary mb-3"></i>
        <h5 class="card-title fw-bold">Gestão de Produtos</h5>
        <p class="card-text">Cadastre, liste e ajuste seu inventário com poucos cliques.</p>
        <a href="cadastrar_produto.php" class="btn btn-outline-primary"><i class="fa-solid fa-plus"></i> Novo Produto</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card dashboard-card text-center p-4">
        <i class="fa-solid fa-file-lines fa-3x text-success mb-3"></i>
        <h5 class="card-title fw-bold">Relatórios Avançados</h5>
        <p class="card-text">Acompanhe vendas, estoques e desempenho em tempo real.</p>
        <a href="relatorio_vendas.php" class="btn btn-outline-success"><i class="fa-solid fa-chart-line"></i> Acessar Relatórios</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card dashboard-card text-center p-4">
        <i class="fa-solid fa-gears fa-3x text-warning mb-3"></i>
        <h5 class="card-title fw-bold">Configurações</h5>
        <p class="card-text">Ajuste as preferências do sistema de forma segura.</p>
        <a href="configuracoes/configuracoes.php" class="btn btn-outline-warning"><i class="fa-solid fa-wrench"></i> Configurar</a>
      </div>
    </div>
  </div>
</main>

<style>
  body {
    background: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .hero {
    border-radius: 20px;
  }

  .dashboard-card {
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
  }

  .dashboard-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  }

  .btn {
    border-radius: 10px;
    font-weight: 600;
  }
</style>

</body>
</html>
