<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$venda_id = $_GET['venda_id'] ?? null;
if (!$venda_id) {
    echo "Venda não encontrada.";
    exit;
}

// Buscar dados da venda
$stmtVenda = $pdo->prepare("SELECT v.*, u.nome AS nome_usuario 
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.id = ?");
$stmtVenda->execute([$venda_id]);
$venda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    echo "Venda não encontrada.";
    exit;
}

// Buscar produtos vendidos
$stmtProdutos = $pdo->prepare("SELECT pv.*, p.nome AS nome_produto 
    FROM produtos_vendidos pv
    LEFT JOIN produtos p ON pv.produto_id = p.id
    WHERE pv.venda_id = ?");
$stmtProdutos->execute([$venda_id]);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Recibo - Venda #<?= htmlspecialchars($venda['numero_recibo']) ?></title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { max-width: 600px; margin: 2rem auto; font-family: monospace; }
        .recibo { border: 1px solid #333; padding: 1rem; }
        h2, h4 { text-align: center; }
        table th, table td { font-size: 1rem; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="recibo">
        <h2>Recibo de Venda</h2>
        <h4>Nº <?= htmlspecialchars($venda['numero_recibo']) ?></h4>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i:s', strtotime($venda['data_hora'])) ?></p>
        <p><strong>Operador:</strong> <?= htmlspecialchars($venda['nome_usuario']) ?></p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-right">Preço Unit.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                        <td class="text-center"><?= $item['quantidade'] ?></td>
                        <td class="text-right">MT <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                        <td class="text-right">MT <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total:</th>
                    <th class="text-right">MT <?= number_format($venda['total'], 2, ',', '.') ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Valor Recebido:</th>
                    <th class="text-right">MT <?= number_format($venda['valor_recebido'], 2, ',', '.') ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Troco:</th>
                    <th class="text-right">MT <?= number_format($venda['troco'], 2, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>

        <p class="text-center">Obrigado pela preferência!</p>
    </div>
</body>
</html>
