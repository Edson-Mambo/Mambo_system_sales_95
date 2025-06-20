<?php
session_start();

require_once '../config/database.php';
$pdo = Database::conectar();
include 'helpers/voltar_menu.php'; 


$mensagem = '';
$produto_encontrado = false;
$prod = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['buscar'])) {
        $busca = trim($_POST['busca']);
        $busca_param = "%$busca%";

        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$busca, $busca_param]);
        $prod = $stmt->fetch();

        if ($prod) {
            $produto_encontrado = true;
        } else {
            $mensagem = "Produto não encontrado!";
        }
    }

    if (isset($_POST['ajustar'])) {
        $id_produto = $_POST['id_produto'];
        $quantidade = (int)$_POST['quantidade'];
        $novo_preco = (float)$_POST['preco'];
        $motivo = trim($_POST['motivo']);
        $ajustado_por = $_SESSION['usuario_id'] ?? 1; // ajuste para pegar da sessão

        $stmt = $pdo->prepare("INSERT INTO ajustes_estoque (produto_id, quantidade_ajustada, motivo, ajustado_por) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_produto, $quantidade, $motivo, $ajustado_por]);

        $pdo->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ?, preco = ? WHERE id = ?")
            ->execute([$quantidade, $novo_preco, $id_produto]);

        $mensagem = "Estoque e preço ajustados com sucesso!";

        // Atualiza o produto para mostrar valores atualizados
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id_produto]);
        $prod = $stmt->fetch();
        $produto_encontrado = true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Ajustar Estoque</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h2 class="mb-4">Ajustar Estoque e Preço</h2>
        <a href="voltar.php" class="btn btn-secondary">← Voltar ao Painel</a>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <!-- Formulário de busca -->
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Nome ou Código de Barras</label>
                <input type="text" name="busca" class="form-control" required value="<?= htmlspecialchars($_POST['busca'] ?? '') ?>">
            </div>
            <button type="submit" name="buscar" class="btn btn-primary">Buscar Produto</button>
        </form>

        <?php if ($produto_encontrado && $prod): ?>
            <form method="POST">
                <input type="hidden" name="id_produto" value="<?= $prod['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($prod['nome']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Código de Barras</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($prod['codigo_barra']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantidade Atual</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($prod['quantidade_estoque']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nova Quantidade (positivo ou negativo)</label>
                    <input type="number" name="quantidade" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Novo Preço</label>
                    <input type="number" step="0.01" name="preco" class="form-control" value="<?= htmlspecialchars($prod['preco']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Motivo do Ajuste</label>
                    <input type="text" name="motivo" class="form-control" required>
                </div>

                <button type="submit" name="ajustar" class="btn btn-success w-100">Salvar Ajustes</button>
            </form>
        <?php endif; ?>

        

    </div>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
