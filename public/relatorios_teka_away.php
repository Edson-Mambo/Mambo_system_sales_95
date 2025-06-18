<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();
include 'helpers/voltar_menu.php'; 


// Filtros de data
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

$condicoes = [];
$params = [];

if (!empty($dataInicio)) {
    $condicoes[] = 'v.data_venda >= :inicio';
    $params[':inicio'] = $dataInicio . ' 00:00:00';
}
if (!empty($dataFim)) {
    $condicoes[] = 'v.data_venda <= :fim';
    $params[':fim'] = $dataFim . ' 23:59:59';
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

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
FROM vendas_takeaway v
JOIN produtos_vendidos_takeaway pv ON v.id = pv.venda_id
LEFT JOIN produtos_takeaway p ON pv.produto_id = p.id
$where
ORDER BY v.id DESC, pv.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupamento por venda
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
<html lang="pt-MZ">
<head>
    <meta charset="UTF-8">
    <title>Relatório Takeaway Agrupado</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h2 class="text-center mb-4">Relatório de Vendas - Takeaway (Agrupado)</h2>
    <div class="text-center mt-4">
            <a href="<?= $pagina_destino ?>" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
        </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="data_inicio" class="form-label">Data Início:</label>
            <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>">
        </div>
        <div class="col-md-4">
            <label for="data_fim" class="form-label">Data Fim:</label>
            <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
            <a href="exportar_teka_excel.php?data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>" class="btn btn-success">Exportar para Excel</a>
        </div>
    </form>

    <?php if ($agrupado): ?>
        <?php foreach ($agrupado as $vendaId => $venda): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white">
                    Venda #<?= $vendaId ?> | Data: <?= htmlspecialchars($venda['data']) ?>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Qtd</th>
                                <th>Preço Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($venda['itens'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                                    <td><?= $item['quantidade'] ?></td>
                                    <td><?= number_format($item['preco_unitario'], 2) ?> MZN</td>
                                    <td><?= number_format($item['subtotal'], 2) ?> MZN</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total:</strong> <?= number_format($venda['total'], 2) ?> MZN</p>
                    <p><strong>Valor Pago:</strong> <?= number_format($venda['valor_pago'], 2) ?> MZN</p>
                    <p><strong>Troco:</strong> <?= number_format($venda['troco'], 2) ?> MZN</p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">Nenhum resultado encontrado.</div>
    <?php endif; ?>
</body>
</html>
