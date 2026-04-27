<?php
session_start();

require_once '../config/database.php';
$pdo = Database::conectar();

include 'helpers/voltar_menu.php';

// =========================
// QUERY PRINCIPAL
// =========================
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
WHERE c.nome = 'Bebidas'
GROUP BY data, p.id
ORDER BY data DESC, total_valor DESC
";

$stmt = $pdo->query($sql);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
// AGRUPAMENTO POR DATA
// =========================
$agrupado = [];
$totalGeral = 0;

foreach ($dados as $linha) {
    $data = $linha['data'];
    $agrupado[$data][] = $linha;
    $totalGeral += $linha['total_valor'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Relatório Bebidas</title>

<link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">

<style>
body {
    background: #f4f6f9;
}

/* HERO ERP */
.hero {
    background: linear-gradient(135deg, #0d6efd, #084298);
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.hero h2 {
    margin: 0;
    font-size: 26px;
}

.hero p {
    margin: 5px 0 0;
    font-size: 14px;
    opacity: 0.9;
}

/* KPI */
.kpi {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.kpi-card {
    background: white;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.kpi-value {
    font-size: 18px;
    font-weight: bold;
    color: #0d6efd;
}

.kpi-label {
    font-size: 12px;
    color: #666;
}

/* TABLE STYLE */
.card {
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.table thead {
    background: #0d6efd;
    color: white;
}

.table td, .table th {
    vertical-align: middle;
}
</style>
</head>

<body>

<div class="container mt-4">

<!-- HERO -->
<div class="hero">
    <h2>📊 Relatório ERP - Vendas de Bebidas</h2>
    <p>Análise operacional por produto e desempenho diário do sistema Mambo System 95</p>
</div>

<!-- KPI -->
<div class="kpi">

    <div class="kpi-card">
        <div class="kpi-value"><?= count($agrupado) ?></div>
        <div class="kpi-label">Dias analisados</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value"><?= count($dados) ?></div>
        <div class="kpi-label">Registos encontrados</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-value"><?= number_format($totalGeral, 2, ',', '.') ?> MZN</div>
        <div class="kpi-label">Faturação total</div>
    </div>

</div>

<!-- RELATÓRIO -->
<?php foreach ($agrupado as $data => $produtos): ?>

<div class="card">

    <div class="card-header bg-primary text-white">
        📅 <?= date('d/m/Y', strtotime($data)) ?>
    </div>

    <div class="card-body">

        <table class="table table-bordered table-hover">

            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Qtd</th>
                    <th>Total</th>
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
                    <td><?= (int)$produto['total_quantidade'] ?></td>
                    <td><?= number_format($produto['total_valor'], 2, ',', '.') ?> MZN</td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <tfoot>
                <tr class="table-secondary">
                    <td colspan="3" class="text-end"><strong>Total do Dia</strong></td>
                    <td><strong><?= number_format($total_dia, 2, ',', '.') ?> MZN</strong></td>
                </tr>
            </tfoot>

        </table>

    </div>
</div>

<?php endforeach; ?>

<!-- VOLTAR ERP PRO -->
<div class="text-center mt-4">
    <a href="<?= $voltar ?? 'index.php' ?>" class="btn btn-secondary">
        ← Voltar ao Painel
    </a>
</div>

</div>

</body>
</html>