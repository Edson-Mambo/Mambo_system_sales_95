<?php
session_start();
require_once '../config/database.php';
require_once '../src/Controller/VendaController.php';
$pdo = Database::conectar();



// Buscar todas as vendas
$stmtVendas = $pdo->prepare("
    SELECT v.id, v.data_venda, v.total, v.valor_pago, v.troco, v.metodo_pagamento,
           u.nome AS operador,
           c.nome AS cliente_nome
    FROM vendas v
    JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    ORDER BY v.data_venda DESC
");
$stmtVendas->execute();
$vendas = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">Relatório de Vendas</h2>

    <?php if(empty($vendas)): ?>
        <p>Nenhuma venda registrada.</p>
    <?php else: ?>
        <?php foreach($vendas as $venda): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>Recibo Nº: <?= $venda['id'] ?></strong>
                    - <?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?>
                </div>
                <div class="card-body">
                    <p><b>Operador:</b> <?= htmlspecialchars($venda['operador']) ?></p>
                    <p><b>Cliente:</b> <?= htmlspecialchars($venda['cliente_nome'] ?? 'Consumidor Final') ?></p>
                    <p><b>Método de Pagamento:</b> <?= htmlspecialchars($venda['metodo_pagamento']) ?></p>

                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Qtd</th>
                                <th>Preço Unitário</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmtProdutos = $pdo->prepare("
                            SELECT p.nome, pv.quantidade, pv.preco_unitario
                            FROM produtos_vendidos pv
                            JOIN produtos p ON pv.produto_id = p.id
                            WHERE pv.venda_id = ?
                        ");
                        $stmtProdutos->execute([$venda['id']]);
                        $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

                        foreach($produtos as $p):
                            $subtotal = $p['quantidade'] * $p['preco_unitario'];
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td><?= $p['quantidade'] ?></td>
                                <td><?= number_format($p['preco_unitario'], 2, ',', '.') ?></td>
                                <td><?= number_format($subtotal, 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p><b>Total:</b> MT <?= number_format($venda['total'], 2, ',', '.') ?></p>
                    <p><b>Pago:</b> MT <?= number_format($venda['valor_pago'], 2, ',', '.') ?></p>
                    <p><b>Troco:</b> MT <?= number_format($venda['troco'], 2, ',', '.') ?></p>

                    <button class="btn btn-sm btn-success" onclick="imprimirVenda(<?= $venda['id'] ?>)">
                        Imprimir
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function imprimirVenda(vendaId) {
    if (!confirm('Deseja enviar esta venda para impressão?')) return;

    fetch(`imprimir_venda.php?venda_id=${vendaId}`)
        .then(response => response.text())
        .then(text => {
            alert(text); // exibe mensagem "Impressão enviada com sucesso!"
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao enviar impressão.');
        });
}
</script>
</body>
</html>
