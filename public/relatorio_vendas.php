
<?php
$agrupado = $agrupado ?? []; // garante que a variável exista
require_once '../config/database.php';
$pdo = Database::conectar();

// Consulta
$sql = "
SELECT 
    v.id AS venda_id,
    v.data_venda,
    v.total,
    v.valor_pago,
    v.troco,
    p.nome AS nome_produto,
    pv.quantidade,
    pv.preco_unitario,
    (pv.quantidade * pv.preco_unitario) AS subtotal
FROM vendas v
JOIN produtos_vendidos pv ON v.id = pv.venda_id
LEFT JOIN produtos p ON pv.produto_id = p.id
ORDER BY v.id DESC, pv.id ASC
";

$stmt = $pdo->query($sql);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar apenas por ID da venda
$agrupado = [];
foreach ($resultados as $linha) {
    $vendaId = $linha['venda_id'];
    $agrupado[$vendaId]['data'] = $linha['data_venda'];
    $agrupado[$vendaId]['total'] = $linha['total'];
    $agrupado[$vendaId]['valor_pago'] = $linha['valor_pago'];
    $agrupado[$vendaId]['troco'] = $linha['troco'];
    $agrupado[$vendaId]['itens'][] = $linha;
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Relatório de Vendas</h2>
        <button class="btn btn-primary no-print" onclick="window.print()">Imprimir Relatório</button>
    </div>

    <?php foreach ($agrupado as $vendaId => $dadosVenda): ?>
    <?php
        $dataVenda = $dadosVenda['data'];
        $itens = $dadosVenda['itens'];
    ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <strong>Data da Venda: <?= date('d/m/Y', strtotime($dataVenda)) ?></strong>
        </div>
        <div class="card-body">
            <h5>Venda Nº <?= $vendaId ?></h5>
            <table class="table table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td><?= number_format($item['preco_unitario'], 2, ',', '.') ?> MZN</td>
                            <td><?= number_format($item['subtotal'], 2, ',', '.') ?> MZN</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <td colspan="3" class="text-end"><strong>Total da Venda:</strong></td>
                        <td><strong><?= number_format($dadosVenda['total'], 2, ',', '.') ?> MZN</strong></td>
                    </tr>
                    <tr class="table-secondary">
                        <td colspan="3" class="text-end">Valor Pago:</td>
                        <td><?= number_format($dadosVenda['valor_pago'], 2, ',', '.') ?> MZN</td>
                    </tr>
                    <tr class="table-secondary">
                        <td colspan="3" class="text-end">Troco:</td>
                        <td><?= number_format($dadosVenda['troco'], 2, ',', '.') ?> MZN</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endforeach; ?>

    <div class="text-center mt-4">
        <button class="btn btn-secondary mb-3" onclick="history.back()">← Voltar</button>
    </div>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
