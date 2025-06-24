<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = Database::conectar();

// Verifica se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido.";
    exit;
}

$id = (int) $_GET['id'];
$mensagem = '';

// Buscar dados do produto
$stmt = $pdo->prepare("SELECT * FROM produtos_takeaway WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}

// Atualizar se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $preco = (float) $_POST['preco'];

    if ($nome === '' || $preco <= 0) {
        $mensagem = "Por favor, preencha todos os campos corretamente.";
    } else {
        $stmt = $pdo->prepare("UPDATE produtos_takeaway SET nome = ?, preco = ? WHERE id = ?");
        $stmt->execute([$nome, $preco, $id]);
        header("Location: listar_takeaway.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto Take Away</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h2 class="mb-4">✏️ Editar Produto Take Away</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm border">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Produto:</label>
            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($produto['nome']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="preco" class="form-label">Preço (MT):</label>
            <input type="number" step="0.01" id="preco" name="preco" class="form-control" value="<?= htmlspecialchars($produto['preco']) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="listar_takeaway.php" class="btn btn-secondary ms-2">← Voltar</a>
    </form>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
