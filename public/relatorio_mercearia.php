<?php
require_once '../config/database.php';
$pdo = Database::conectar();
include 'helpers/voltar_menu.php'; 


// Consulta para produtos vendidos da categoria "Bebidas" agrupado por dia
$sql = "
SELECT 
    DATE(v.data_venda) AS data,
    p.nome AS nome_produto,
    c.nome AS categoria,
    SUM(pv.quantidade) AS total_quantidade,
    SUM(pv.quantidade * pv.preco_unitario) AS total_valor
FROM produtos_vendidos pv
JOIN produtos p ON pv.produto_id = p.id
JOIN categorias c ON p.categoria_id = c.id
JOIN vendas v ON pv.venda_id = v.id
WHERE c.nome = 'Produtos da Mercearia'
GROUP BY data, p.id
ORDER BY data DESC, total_valor DESC
";

$stmt = $pdo->query($sql);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por data
$agrupado = [];
foreach ($dados as $linha) {
    $data = $linha['data'];
    $agrupado[$data][] = $linha;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Bebidas por Dia</title>
    <link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Relatório de Vendas - Bebidas por Dia</h2>

    <?php foreach ($agrupado as $data => $produtos): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Data: <?= date('d/m/Y', strtotime($data)) ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade Vendida</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $total_dia = 0;
                            foreach ($produtos as $produto): 
                                $total_dia += $produto['total_valor'];
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($produto['nome_produto']) ?></td>
                                <td><?= htmlspecialchars($produto['categoria']) ?></td>
                                <td><?= $produto['total_quantidade'] ?></td>
                                <td><?= number_format($produto['total_valor'], 2, ',', '.') ?> MZN</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="3" class="text-end"><strong>Total do Dia:</strong></td>
                            <td><strong><?= number_format($total_dia, 2, ',', '.') ?> MZN</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-4">
            <a href="<?= $pagina_destino ?>" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
        </div>
</div>
</body>
</html>
