

<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Painel Admin - Mambo System Sales</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">MamboSystem95 - Admin</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="cadastrar_produto.php">Cadastrar Produto</a></li>
                <li class="nav-item"><a class="nav-link" href="../src/View/listar_produtos.view.php">Listar Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastrar_usuario.php">Cadastrar Usuário</a></li>
                <li class="nav-item"><a class="nav-link" href="ajustar_estoque.php">Ajustar Estoque</a></li>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="relatorioDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Relatórios
                </a>
                <ul class="dropdown-menu" aria-labelledby="relatorioDropdown">
                    <li><a class="dropdown-item" href="relatorio_vendas.php">Relatório de Vendas</a></li>
                    <li><a class="dropdown-item" href="relatorio_bebidas.php">Relatório de Bebidas</a></li>
                    <li><a class="dropdown-item" href="relatorio_mercearia.php">Relatório de Produtos de Mercearia</a></li>
                </ul>
                </li>

                <li class="nav-item"><a class="nav-link" href="relatorio_logins.php">Relatório de Logins</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorio_estoque.php">Relatório de Estoque</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastrar_produto_takeaway.php">Cadastrar Produtos Take Away</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorios_teka_away.php">Relatório de Take Away</a></li>
                <li class="nav-item"><a class="nav-link" href="fecho_dia.php">Fecho do dia</a></li>
                <li class="nav-item"><a class="nav-link" href="configuracoes/configuracoes.php">Configurações do Sistema</a></li>
                <li class="nav-item"><a href="logout.php" class="btn btn-danger btn-lg">Terminar Sessão</a></li>
                
                
            </ul>
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
