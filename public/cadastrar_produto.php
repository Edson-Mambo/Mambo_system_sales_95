<?php
require_once '../config/database.php';
$pdo = Database::conectar();
$cats = $pdo->query("SELECT * FROM categorias")->fetchAll();
include 'helpers/voltar_menu.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $codigo_barra = $_POST['codigo_barra'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $estoque = $_POST['estoque'] ?? 0; // Novo nome certo!

    $stmt = $pdo->prepare("INSERT INTO produtos (nome, codigo_barra, preco, categoria_id, estoque) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nome, $codigo_barra, $preco, $categoria, $estoque]);

    echo "<div class='alert alert-success text-center'>Produto cadastrado com sucesso!</div>";
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Cadastrar Produto</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2 class="text-center mb-4">Cadastrar Produto</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Produto</label>
            <input type="text" id="nome" name="nome" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="codigo_barra" class="form-label">Código de Barras</label>
            <input type="text" id="codigo_barra" name="codigo_barra" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="preco" class="form-label">Preço</label>
            <input type="number" id="preco" name="preco" step="0.01" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="estoque" class="form-label">Quantidade</label>
            <input type="number" id="estoque" name="estoque" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select id="categoria" name="categoria" class="form-select" required>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id']) ?>">
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
       
        <div class="text-center mt-4">
            <a href="voltar.php" class="btn btn-secondary">← Voltar ao Painel</a>
        </div>

    </form>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
