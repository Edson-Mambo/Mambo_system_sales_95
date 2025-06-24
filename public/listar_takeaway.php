<?php
require_once '../config/database.php';
$pdo = Database::conectar();

try {
    $sql = "SELECT * FROM produtos_takeaway ORDER BY id DESC"; // substitua "id" por outra coluna existente, se necessário
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar produtos Take Away: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Listar Produtos Take Away</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h2 class="mb-4">📋 Produtos Take Away</h2>

    <?php if (empty($produtos)): ?>
        <div class="alert alert-warning">Nenhum produto cadastrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Preço (MT)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $i => $produto): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= number_format($produto['preco'], 2, ',', '.') ?></td>
                            <td>
                                <a href="editar_produto_takeaway.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="deletar_takeaway.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja deletar este produto?')">Deletar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="index_admin.php" class="btn btn-secondary">← Voltar ao Painel</a>
    </div>
</div>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
