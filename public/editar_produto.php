<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
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

// Buscar todas as categorias
$stmtCats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
$cats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $codigo_barra = $_POST['codigo_barra'] ?? '';
    $preco = str_replace(',', '.', $_POST['preco'] ?? '');
    $estoque = $_POST['estoque'] ?? 0;
    $categoria_id = $_POST['categoria'] ?? null;

    if (empty($nome) || empty($codigo_barra) || !is_numeric($preco) || !is_numeric($estoque) || !$categoria_id) {
        $mensagem = '<div class="alert alert-danger">Preencha todos os campos corretamente.</div>';
    } else {
        // Atualiza o produto no banco
        $sql = "UPDATE produtos 
                SET nome = ?, codigo_barra = ?, preco = ?, estoque = ?, categoria_id = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $sucesso = $stmt->execute([$nome, $codigo_barra, $preco, $estoque, $categoria_id, $id]);

        if ($sucesso) {
            $mensagem = '<div class="alert alert-success">Produto atualizado com sucesso.</div>';
            $produto = [
                'nome' => $nome,
                'codigo_barra' => $codigo_barra,
                'preco' => $preco,
                'estoque' => $estoque,
                'categoria_id' => $categoria_id
            ];
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

    <form method="POST">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Produto</label>
            <input type="text" id="nome" name="nome" class="form-control" 
                   value="<?= htmlspecialchars($produto['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="mb-3">
            <label for="codigo_barra" class="form-label">Código de Barras</label>
            <input type="text" id="codigo_barra" name="codigo_barra" class="form-control" 
                   value="<?= htmlspecialchars($produto['codigo_barra'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="mb-3">
            <label for="preco" class="form-label">Preço</label>
            <input type="number" step="0.01" id="preco" name="preco" class="form-control" 
                   value="<?= htmlspecialchars($produto['preco'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="mb-3">
            <label for="estoque" class="form-label">Quantidade</label>
            <input type="number" id="estoque" name="estoque" class="form-control" 
                   value="<?= htmlspecialchars($produto['estoque'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select id="categoria" name="categoria" class="form-select" required>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?= $cat['id'] ?>" 
                        <?= ($produto['categoria_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
        <div class="text-center mt-4">
            <a href="../src/View/listar_produtos.view.php" class="btn btn-secondary">← Voltar ao Painel</a>
        </div>
    </form>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
