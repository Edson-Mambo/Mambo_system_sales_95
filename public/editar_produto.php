<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';

require_once '../config/database.php';
include 'helpers/voltar_menu.php'; 

$pdo = Database::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar se foi passado o ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID do produto não fornecido.');
}

$id = $_GET['id'];
$mensagem = '';

// Buscar os dados atuais do produto
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    die('Produto não encontrado.');
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $codigo_barra = $_POST['codigo_barra'];
    $preco = str_replace(',', '.', $_POST['preco']);
    $quantidade = $_POST['quantidade'];

    if (empty($nome) || empty($codigo_barra) || !is_numeric($preco) || !is_numeric($quantidade)) {
        $mensagem = '<div class="alert alert-danger">Preencha todos os campos corretamente.</div>';
    } else {
        $sql = "UPDATE produtos SET nome = ?, codigo_barra = ?, preco = ?, quantidade = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $sucesso = $stmt->execute([$nome, $codigo_barra, $preco, $quantidade, $id]);

        if ($sucesso) {
            $mensagem = '<div class="alert alert-success">Produto atualizado com sucesso.</div>';
            // Atualiza os dados para mostrar no formulário
            $produto = ['nome' => $nome, 'codigo_barra' => $codigo_barra, 'preco' => $preco, 'quantidade' => $quantidade];
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao atualizar o produto.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Editar Produto</h2>

    <?= $mensagem ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label for="nome" class="form-label">Nome do Produto</label>
            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="codigo_barra" class="form-label">Código de Barras</label>
            <input type="text" class="form-control" id="codigo_barra" name="codigo_barra" value="<?= htmlspecialchars($produto['codigo_barra']) ?>" required>
        </div>
        <div class="col-md-4">
            <label for="preco" class="form-label">Preço</label>
            <input type="text" class="form-control" id="preco" name="preco" value="<?= htmlspecialchars($produto['preco']) ?>" required>
        </div>
        <div class="col-md-4">
            <label for="quantidade" class="form-label">Quantidade</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?= htmlspecialchars($produto['quantidade']) ?>" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <div class="text-center mt-4">
                <a href="<?= $pagina_destino ?>" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
            </div>

            
        </div>
    </form>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
