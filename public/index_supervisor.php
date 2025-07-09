<?php
session_start();

$timeout = 30 * 60; // 30 minutos em segundos

// Verifica se o usuário está logado e tem nível de acesso válido
if (isset($_SESSION['usuario_id']) && in_array($_SESSION['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    if (isset($_SESSION['ultimo_acesso'])) {
        $tempo_inativo = time() - $_SESSION['ultimo_acesso'];
        if ($tempo_inativo > $timeout) {
            // Tempo de inatividade excedeu o limite - destruir sessão e redirecionar para login
            session_unset();
            session_destroy();
            header("Location: ../login.php?mensagem=Sessão expirada por inatividade.");
            exit();
        }
    }
    // Atualiza o timestamp do último acesso
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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">MamboSystem95 - Supervisor</a>

    <!-- Botão toggle para dispositivos móveis -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupervisor" aria-controls="navbarSupervisor" aria-expanded="false" aria-label="Alternar navegação">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupervisor">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <li class="nav-item">
          <a class="nav-link" href="venda.php">Caixa</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="relatorio_vendas.php">Relatório de Vendas</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="relatorios_teka_away.php">Relatório de Take Away</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="relatorio_estoque.php">Relatório de Estoque</a>
        </li>

      </ul>

      <!-- Botão Terminar Sessão -->
      <div class="d-flex">
        <a href="logout.php" class="btn btn-danger btn-lg">Terminar Sessão</a>
      </div>
    </div>
  </div>
</nav>

<style>
    body {
        background: linear-gradient(to right, #e3f2fd, #f8f9fa);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .container {
        max-width: 1100px;
        background-color: #ffffff;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    .dashboard-card {
        border-radius: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
        background-color: #f1f3f5;
        border: none;
    }

    .dashboard-card:hover {
        transform: translateY(-5px) scale(1.03);
        box-shadow: 0 0 25px rgba(13, 110, 253, 0.3);
    }

    .card-title {
        font-weight: 700;
        color: #0d6efd;
        margin-bottom: 1rem;
    }

    .card-text {
        font-size: 2.8rem;
        font-weight: 700;
        color: #212529;
    }

    .header-title {
        font-weight: 900;
        color: #0d6efd;
        text-align: center;
        margin-bottom: 3rem;
        font-size: 2.5rem;
    }

    .btn-danger {
        padding: 0.75rem 2rem;
        font-size: 1.1rem;
        border-radius: 10px;
        font-weight: 600;
    }
</style>



</body>
</html>


