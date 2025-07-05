<?php
require_once '../config/database.php';

$pdo = Database::conectar();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID do vale não especificado.");
}

// Buscar vale + cliente
$stmt = $pdo->prepare("
    SELECT v.*, 
           c.nome, c.apelido, c.telefone, c.telefone_alt, c.email, c.morada
    FROM vales v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$vale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vale) {
    die("Vale não encontrado.");
}

// Buscar itens do vale
$stmtItens = $pdo->prepare("SELECT * FROM itens_vale WHERE vale_id = ?");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recibo do Vale</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        .empresa-info { font-size: 13px; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <!-- LADO ESQUERDO: Dados da Empresa -->
        <div class="empresa-info">
            <h4 class="mb-1">Mambo System Sales 95</h4>
            <p class="mb-0"><strong>Nome:</strong> Edson Mambo</p>
            <p class="mb-0"><strong>Email:</strong> edsonmambo@epcompany.co.mz</p>
            <p class="mb-0"><strong>Telefone:</strong> +258 84 854 1787</p>
            <p class="mb-0"><strong>Endereço:</strong> Bairro Aeroporto A, Rua da Patria, Q10, Casa 531</p>
            <p class="mb-0"><strong>Website:</strong> https://www.epcompany.inc.co.mz</p>
            <p class="mb-0"><strong>Horário:</strong> 08:00 às 22:00</p>
        </div>

        <!-- LADO DIREITO: Dados do Cliente -->
        <div>
            <h5>Dados do Cliente</h5>
            <p class="mb-0"><strong>Nome:</strong> <?= htmlspecialchars($vale['nome']) ?> <?= htmlspecialchars($vale['apelido']) ?></p>
            <p class="mb-0"><strong>Telefone:</strong> <?= htmlspecialchars($vale['telefone']) ?></p>
            <p class="mb-0"><strong>Tel. Alternativo:</strong> <?= htmlspecialchars($vale['telefone_alt']) ?></p>
            <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($vale['email']) ?></p>
            <p class="mb-0"><strong>Morada:</strong> <?= htmlspecialchars($vale['morada']) ?></p>
            <p class="mb-0"><strong>Data do Registro:</strong> <?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></p>
            <p class="mb-0"><strong>Status:</strong> <?= ucfirst($vale['status']) ?></p>
        </div>
    </div>

    <h3 class="text-center mb-4">Recibo do Vale Nº <?= $vale['numero_vale'] ?></h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário (MT)</th>
                <th>Total (MT)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $total = 0;
                foreach ($itens as $item): 
                $subtotal = $item['preco'] * $item['quantidade'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['produto_nome']) ?></td>
                <td><?= $item['quantidade'] ?></td>
                <td><?= number_format($item['preco'], 2, ',', '.') ?></td>
                <td><?= number_format($subtotal, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="3">Valor Total</th>
                <th><?= number_format($vale['valor_total'], 2, ',', '.') ?></th>
            </tr>
            <tr>
                <th colspan="3">Valor Pago</th>
                <th><?= number_format($vale['valor_pago'], 2, ',', '.') ?></th>
            </tr>
            <tr>
                <th colspan="3">Saldo</th>
                <th><?= number_format($vale['saldo'], 2, ',', '.') ?></th>
            </tr>
        </tbody>
    </table>

    <p class="text-center fst-italic">Obrigado pela preferência!</p>

    <div class="mt-3">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir</button>
    </div>
</div>

</body>
</html>
