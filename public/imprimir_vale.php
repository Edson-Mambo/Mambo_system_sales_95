<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID do vale não fornecido.');
}

// Buscar dados do vale
$stmt = $pdo->prepare("SELECT * FROM vales WHERE id = ?");
$stmt->execute([$id]);
$vale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vale) {
    die('Vale não encontrado.');
}

// Buscar itens do vale com os nomes dos produtos
$stmtItens = $pdo->prepare("
    SELECT iv.quantidade, iv.preco_unitario, p.nome
    FROM itens_vale iv
    JOIN produtos p ON iv.produto_id = p.id
    WHERE iv.vale_id = ?
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Recibo do Vale - <?= htmlspecialchars($vale['numero_vale']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            color: #333;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .total {
            font-weight: bold;
            font-size: 1.1em;
        }
        .center {
            text-align: center;
        }
        .btn-print {
            margin-bottom: 20px;
            display: block;
            width: 100px;
            padding: 8px;
            background: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <a href="#" class="btn-print" onclick="window.print();return false;">🖨️ Imprimir</a>

    <h1>Recibo do Vale</h1>
    <h2><?= htmlspecialchars($vale['numero_vale']) ?></h2>

    <p><strong>Cliente:</strong> <?= htmlspecialchars($vale['cliente_nome']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($vale['cliente_telefone']) ?></p>
    <p><strong>Data do Registro:</strong> <?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></p>
    <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($vale['status'])) ?></p>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário (MT)</th>
                <th>Total (MT)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nome']) ?></td>
                <td class="center"><?= (int)$item['quantidade'] ?></td>
                <td><?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                <td><?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">Valor Total</td>
                <td class="total"><?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="total">Valor Pago</td>
                <td class="total"><?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="total">Saldo</td>
                <td class="total"><?= number_format($vale['saldo'], 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <p><em>Obrigado pela preferência!</em></p>

</body>
</html>
