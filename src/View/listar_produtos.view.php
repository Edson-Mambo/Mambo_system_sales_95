<?php
require_once '../../config/database.php';
require_once '../Template/header.php';

// Buscar todos os produtos
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="mb-4">Lista de Produtos</h2>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Código de Barras</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Quantidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?= $produto['id'] ?></td>
                    <td><?= htmlspecialchars($produto['codigo_barras']) ?></td>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td><?= number_format($produto['preco'], 2, ',', '.') ?> MZN</td>
                    <td><?= $produto['quantidade'] ?></td>
                    <td>
                        <a href="editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../Template/footer.php'; ?>
