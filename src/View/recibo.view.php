<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Venda</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>

    <h2>Recibo de Venda</h2>

    <p><strong>Data:</strong> <?= date('d/m/Y H:i:s') ?></p>
    <p><strong>Nº Recibo:</strong> <?= $_SESSION['numero_recibo'] ?? '---' ?></p>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Preço Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $totalGeral = 0;
                foreach ($produtos as $item):
                    $total = $item['quantidade'] * $item['preco_unitario'];
                    $totalGeral += $total;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['nome']) ?></td>
                    <td><?= $item['quantidade'] ?></td>
                    <td><?= number_format($item['preco_unitario'], 2, ',', '.') ?> MZN</td>
                    <td><?= number_format($total, 2, ',', '.') ?> MZN</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="3" style="text-align: right;">Total:</td>
                <td><?= number_format($totalGeral, 2, ',', '.') ?> MZN</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Obrigado pela preferência!</p>
    </div>

</body>
</html>
