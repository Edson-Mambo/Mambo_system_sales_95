<?php
require_once '../config/database.php';

$pdo = Database::conectar(); // <- ISSO FALTAVA

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);
    $nivel = $_POST['nivel'];

    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $email, $senha, $nivel]);
    echo "Usuário cadastrado com sucesso!";
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Usuário</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center mb-4">Cadastrar Usuário</h2>
    <form method="POST" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px; margin: auto;">
        <div class="mb-3">
            <input type="text" name="nome" class="form-control" placeholder="Nome" required>
        </div>
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <input type="password" name="senha" class="form-control" placeholder="Senha" required>
        </div>
        <div class="mb-3">
            <select name="nivel" class="form-select" required>
                <option value="" disabled selected>Selecione o nível</option>
                <option value="caixa">Caixa</option>
                <option value="supervisor">Supervisor</option>
                <option value="gerente">Gerente</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-success">Cadastrar</button>
            <br>
            <div class="text-center mt-4">
                <button class="btn btn-secondary mb-3" onclick="history.back()">← Voltar</button>
            </div>


        </div>
    </form>
</div>

</body>
</html>
