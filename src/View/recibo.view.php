<?php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

/* -------------------------
   CONFIGURAÇÕES EMPRESA
--------------------------*/
$stmtConfig = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

/* helpers seguros */
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Recibo de Venda</title>

<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 13px;
        color: #000;
        margin: 0;
        padding: 10px;
    }

    .center { text-align: center; }

    .logo {
        display: block;
        margin: 0 auto 8px auto;
        max-height: 90px;
    }

    .empresa {
        text-align: center;
        line-height: 1.4;
        margin-bottom: 10px;
    }

    .linha {
        border-top: 1px dashed #000;
        margin: 8px 0;
    }

    .info {
        margin: 5px 0;
        font-size: 12px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    th, td {
        border-bottom: 1px solid #ddd;
        padding: 6px 4px;
        font-size: 12px;
    }

    th {
        background: #f2f2f2;
        text-align: left;
    }

    .right { text-align: right; }

    .total {
        font-weight: bold;
        border-top: 2px solid #000;
    }

    .footer {
        text-align: center;
        margin-top: 15px;
        font-size: 11px;
    }
</style>
</head>

<body>

<!-- LOGO -->
<?php if (!empty($config['logo'])): ?>
    <img class="logo" src="../public/uploads/<?= e($config['logo']) ?>">
<?php endif; ?>

<!-- EMPRESA -->
<div class="empresa">
    <strong><?= e($config['nome_empresa'] ?? 'Empresa') ?></strong><br>

    <?= e($config['rua_avenida'] ?? '') ?><br>

    <?php if (!empty($config['bairro']) || !empty($config['cidade'])): ?>
        <?= e($config['bairro']) ?><?= (!empty($config['bairro']) && !empty($config['cidade'])) ? ' - ' : '' ?><?= e($config['cidade']) ?><br>
    <?php endif; ?>

    <?= e($config['provincia'] ?? '') ?><br>

    <?php if (!empty($config['telefone'])): ?>
        Tel: <?= e($config['telefone']) ?><br>
    <?php endif; ?>

    <?php if (!empty($config['email_empresa'])): ?>
        <?= e($config['email_empresa']) ?>
    <?php endif; ?>
</div>

<div class="linha"></div>

<!-- INFO VENDA -->
<div class="info">
    <strong>Data:</strong> <?= date('d/m/Y H:i:s') ?><br>
    <strong>Nº Recibo:</strong> <?= e($_SESSION['numero_recibo'] ?? '---') ?>
</div>

<div class="linha"></div>

<!-- PRODUTOS -->
<table>
    <thead>
        <tr>
            <th>Produto</th>
            <th class="right">Qtd</th>
            <th class="right">Preço</th>
            <th class="right">Total</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $totalGeral = 0;

        foreach ($produtos as $item):

            $nome = mb_strimwidth($item['nome'] ?? '', 0, 25, '...');
            $qtd = (int)$item['quantidade'];
            $preco = (float)$item['preco_unitario'];
            $total = $qtd * $preco;
            $totalGeral += $total;
        ?>
        <tr>
            <td><?= e($nome) ?></td>
            <td class="right"><?= $qtd ?></td>
            <td class="right"><?= number_format($preco, 2, ',', '.') ?></td>
            <td class="right"><?= number_format($total, 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>

    <tfoot>
        <tr class="total">
            <td colspan="3" class="right">TOTAL:</td>
            <td class="right"><?= number_format($totalGeral, 2, ',', '.') ?> MZN</td>
        </tr>
    </tfoot>
</table>

<div class="linha"></div>

<!-- RODAPÉ -->
<div class="footer">
    <?= e($config['mensagem_rodape'] ?? 'Obrigado pela preferência!') ?>
</div>

</body>
</html>