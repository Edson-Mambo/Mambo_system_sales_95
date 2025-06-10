<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Mambo System 95 - Gerente</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">MamboSystem95 - Gerente</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="cadastrar_produto.php">Cadastrar Produto</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastrar_usuario.php">Cadastrar Usuário</a></li>
                <li class="nav-item"><a class="nav-link" href="ajustar_estoque.php">Ajustar Estoque</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorio_vendas.php">Relatório de Vendas</a></li>
                
                <li class="nav-item"><a class="nav-link" href="relatorio_estoque.php">Relatório de Estoque</a></li>
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
