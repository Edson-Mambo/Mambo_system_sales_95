<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/VendaController.php';

/* =========================
   SEGURANÇA ERP
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$nivel = $_SESSION['nivel_acesso'] ?? '';
$permitidos = ['admin', 'gerente', 'supervisor'];

if (!in_array($nivel, $permitidos)) {
    die("Acesso negado.");
}

/* =========================
   CONEXÃO
========================= */
$pdo = Database::conectar();

/* =========================
   BUSCA VENDAS
========================= */
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

/* =========================
   CONTROLLER (caso use PDF etc)
========================= */
$controller = new Controller\VendaController($pdo);

/* =========================
   VOLTAR ERP
========================= */
$voltar = [
    'admin' => '../public/index_admin.php',
    'gerente' => '../public/index_gerente.php',
    'supervisor' => '../public/index_supervisor.php'
][$nivel] ?? '../public/index.php';

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Relatório de Vendas</title>

<link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">

<style>
body {
    background: #eef2f7;
}

/* HEADER ERP */
.header {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* CARD VENDAS */
.venda-card {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

/* TABELA */
.table thead {
    background: #1f2937;
    color: #fff;
}

/* BOTÕES PDF/IMPRIMIR */
.actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h4 class="mb-0">🧾 Relatório de Vendas ERP</h4>
            <small><?= count($vendas) ?> vendas registradas</small>
        </div>

        <a href="<?= $voltar ?>" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>

    <!-- LISTA DE VENDAS -->
    <?php if (empty($vendas)): ?>

        <div class="alert alert-warning">
            Nenhuma venda registrada.
        </div>

    <?php else: ?>

        <?php foreach ($vendas as $venda): ?>

            <div class="card venda-card">

                <!-- HEADER DA VENDA -->
                <div class="card-header bg-primary text-white">
                    <strong>Recibo Nº: <?= $venda['id'] ?></strong>
                    |
                    <?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?>
                </div>

                <div class="card-body">

                    <p><b>Operador:</b> <?= htmlspecialchars($venda['operador']) ?></p>
                    <p><b>Cliente:</b> <?= htmlspecialchars($venda['cliente_nome'] ?? 'Consumidor Final') ?></p>
                    <p><b>Pagamento:</b> <?= htmlspecialchars($venda['metodo_pagamento']) ?></p>

                    <!-- PRODUTOS -->
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Qtd</th>
                                <th>Preço</th>
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

                        foreach ($produtos as $p):
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

                    <!-- TOTAL -->
                    <p><b>Total:</b> MT <?= number_format($venda['total'], 2, ',', '.') ?></p>
                    <p><b>Pago:</b> MT <?= number_format($venda['valor_pago'], 2, ',', '.') ?></p>
                    <p><b>Troco:</b> MT <?= number_format($venda['troco'], 2, ',', '.') ?></p>

                    <!-- AÇÕES -->
                    <div class="actions">

                        <?php
                        $pdfFile = "recibos/recibo_venda_{$venda['id']}.pdf";
                        ?>

                        <?php if (file_exists("../public/" . $pdfFile)): ?>
                            <a href="<?= $pdfFile ?>" target="_blank" class="btn btn-sm btn-primary">
                                Abrir PDF
                            </a>
                        <?php else: ?>
                            <span class="text-danger">PDF não encontrado</span>
                        <?php endif; ?>

                        <a href="imprimir_venda.php?venda_id=<?= $venda['id'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-success">
                            Imprimir
                        </a>

                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>