<?php
require_once '../../config/database.php';

$recibo = $_GET['recibo'] ?? '';

if (!$recibo) {
    echo "Recibo não encontrado.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produtos_vendidos WHERE recibo = ?");
$stmt->execute([$recibo]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM vendas WHERE recibo = ?");
$stmt->execute([$recibo]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<h2>Recibo Nº <?= htmlspecialchars($recibo) ?></h2>
<p><strong>Data:</strong> <?= $venda['data'] ?></p>
<p><strong>Total:</strong> <?= number_format($venda['total'], 2) ?> MZN</p>

<table border="1">
    <thead>
        <tr>
            <th>Produto</th>
            <th>Qtd</th>
            <th>Preço Unit.</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($produtos as $p): ?>
        <tr>
            <td><?= $p['produto_id'] ?></td>
            <td><?= $p['quantidade'] ?></td>
            <td><?= number_format($p['preco_unitario'], 2) ?></td>
            <td><?= number_format($p['preco_total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button onclick="window.print()">Imprimir</button>
<a href="../../public/venda.php">Nova Venda</a>
