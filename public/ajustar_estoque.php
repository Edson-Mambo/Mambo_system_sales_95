<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado. Faça login primeiro.');
}

$mensagem = '';
$produto_encontrado = false;
$prod = null;

// Se enviou o formulário de BUSCA
if (isset($_POST['buscar'])) {
    $busca = trim($_POST['busca'] ?? '');
    if ($busca !== '') {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? OR codigo_barra = ? LIMIT 1");
        $stmt->execute(["%$busca%", $busca]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        $produto_encontrado = $prod ? true : false;
        if (!$produto_encontrado) {
            $mensagem = '<div class="alert alert-warning">Produto não encontrado.</div>';
        }
    }
}

// Se enviou o formulário de AJUSTE
if (isset($_POST['ajustar'])) {
    $id_produto = intval($_POST['id_produto'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $preco = floatval($_POST['preco'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if ($id_produto <= 0 || $motivo === '') {
        $mensagem = '<div class="alert alert-danger">Preencha todos os campos obrigatórios.</div>';
    } else {
        // Registra ajuste na tabela de log
        $stmt = $pdo->prepare("INSERT INTO ajustes_estoque (produto_id, quantidade_ajustada, motivo, ajustado_por) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_produto, $quantidade, $motivo, $usuario_id]);

        // Atualiza estoque somando
        $stmt = $pdo->prepare("UPDATE produtos SET estoque = estoque + ?, preco = ? WHERE id = ?");
        $stmt->execute([$quantidade, $preco, $id_produto]);

        $mensagem = '<div class="alert alert-success">Estoque ajustado com sucesso!</div>';

        // Para exibir os dados atualizados:
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id_produto]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        $produto_encontrado = $prod ? true : false;
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
        <a href="voltar.php" class="btn btn-secondary mb-3">← Voltar ao Painel</a>

        <?= $mensagem ?>

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
    <label class="form-label">Estoque Atual</label>
    <input type="text" class="form-control" value="<?= htmlspecialchars($prod['estoque']) ?>" disabled>
</div>


                <div class="mb-3">
                    <label class="form-label">Ajuste de Quantidade (positivo ou negativo)</label>
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
