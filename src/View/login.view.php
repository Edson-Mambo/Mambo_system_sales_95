<?php
// public/login.php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: venda.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login - System TM</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow p-4" style="width: 400px;">
            <h4 class="text-center mb-4">System TM - Login</h4>
            <form method="POST" action="/src/Controller/AuthController.php">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" name="usuario" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" name="senha" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
