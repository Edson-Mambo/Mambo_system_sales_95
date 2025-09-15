<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID do vale inválido.");
}

$pdo = Database::conectar();
$id_vale = (int) $_GET['id'];

// Buscar informações do vale
$stmtVale = $pdo->prepare("
    SELECT v.id, c.nome AS cliente_nome, v.valor_total, COALESCE(v.valor_pago,0) AS valor_pago, 
           COALESCE(v.saldo,0) AS saldo, v.status, v.data_registro
    FROM vales v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
");
$stmtVale->execute([$id_vale]);
$vale = $stmtVale->fetch(PDO::FETCH_ASSOC);

if (!$vale) {
    die("Vale não encontrado.");
}

// Buscar itens do vale
$stmtItens = $pdo->prepare("
    SELECT iv.produto_id, p.nome AS produto_nome, iv.quantidade, iv.preco_unitario,
           (iv.quantidade * iv.preco_unitario) AS total_item
    FROM itens_vale iv
    LEFT JOIN produtos p ON iv.produto_id = p.id
    WHERE iv.vale_id = ?
");
$stmtItens->execute([$id_vale]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Vale #<?= htmlspecialchars($vale['id']) ?></title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-header {
            font-weight: bold;
            font-size: 1.2rem;
        }
        .status-label {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            color: white;
        }
        .status-nenhum { background-color: #dc3545; }
        .status-parcelado { background-color: #ffc107; color: black; }
        .status-outro { background-color: #6c757d; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💳 Detalhes do Vale #<?= htmlspecialchars($vale['id']) ?></h2>
        <a href="listar_vales.php" class="btn btn-secondary">⬅ Voltar</a>
    </div>

    <!-- Informações do Vale -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            📄 Informações do Vale
        </div>
        <div class="card-body">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($vale['cliente_nome'] ?? 'Sem Cliente') ?></p>
            <p>
                <strong>Status:</strong>
                <span class="status-label 
                    <?= $vale['status'] === 'nenhum' ? 'status-nenhum' : ($vale['status'] === 'parcelado' ? 'status-parcelado' : 'status-outro') ?>">
                    <?= htmlspecialchars($vale['status']) ?>
                </span>
            </p>
            <p><strong>Valor Total:</strong> <span class="text-success fw-bold">MT <?= number_format($vale['valor_total'], 2, ',', '.') ?></span></p>
            <p><strong>Valor Pago:</strong> <span class="text-primary fw-bold">MT <?= number_format($vale['valor_pago'], 2, ',', '.') ?></span></p>
            <p><strong>Saldo:</strong> <span class="text-danger fw-bold">MT <?= number_format($vale['saldo'], 2, ',', '.') ?></span></p>
            <p><strong>Data de Criação:</strong> <?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></p>
        </div>
    </div>

    <!-- Itens do Vale -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            🛒 Itens do Vale
        </div>
        <div class="card-body">
            <?php if (empty($itens)): ?>
                <div class="alert alert-warning">Nenhum item encontrado para este vale.</div>
            <?php else: ?>
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-end">Preço Unitário</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['produto_nome']) ?></td>
                                <td class="text-center"><?= (int)$item['quantidade'] ?></td>
                                <td class="text-end">MT <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                <td class="text-end fw-bold">MT <?= number_format($item['total_item'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-4 text-end">
    <form action="venda_vale.php" method="post">
        <input type="hidden" name="id_vale" value="<?= htmlspecialchars($vale['id']) ?>">
        <button type="submit" class="btn btn-success btn-lg">
            💰 Fazer Pagamento
        </button>
    </form>
</div>



<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
