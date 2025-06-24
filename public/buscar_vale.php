<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$nome = trim($_GET['cliente_nome'] ?? '');
$telefone = trim($_GET['cliente_telefone'] ?? '');

$vales = [];

// Só faz a busca se pelo menos um dos campos for preenchido
if ($nome !== '' || $telefone !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM vales
        WHERE (cliente_nome LIKE ? OR cliente_telefone LIKE ?)
          AND status IN ('aberto', 'parcelado')
        ORDER BY data_registro DESC
    ");
    $stmt->execute(["%$nome%", "%$telefone%"]);
    $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Buscar Vales Pendentes</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h2 class="mb-4">Buscar Vales Pendentes</h2>

    <!-- Formulário de busca -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="cliente_nome" class="form-label">Nome do Cliente</label>
            <input type="text" name="cliente_nome" id="cliente_nome" class="form-control" value="<?= htmlspecialchars($nome) ?>">
        </div>
        <div class="col-md-6">
            <label for="cliente_telefone" class="form-label">Telefone</label>
            <input type="text" name="cliente_telefone" id="cliente_telefone" class="form-control" value="<?= htmlspecialchars($telefone) ?>">
        </div>
        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
        </div>
    </form>

    <!-- Resultado da busca -->
    <?php if ($nome === '' && $telefone === ''): ?>
        <div class="alert alert-info">Informe pelo menos o nome ou telefone do cliente para buscar os vales.</div>
    <?php elseif (count($vales) === 0): ?>
        <div class="alert alert-warning">Nenhum vale pendente encontrado para esse cliente.</div>
    <?php else: ?>
        <h4 class="mb-3">Resultados para: <strong><?= htmlspecialchars($nome ?: $telefone) ?></strong></h4>

        <form method="POST" action="finalizar_vales.php">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Selecionar</th>
                        <th>Número do Vale</th>
                        <th>Valor Total (MT)</th>
                        <th>Valor Pago (MT)</th>
                        <th>Saldo (MT)</th>
                        <th>Status</th>
                        <th>Data de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $vale): ?>
                        <tr>
                            <td><input type="checkbox" name="vales_selecionados[]" value="<?= $vale['id'] ?>"></td>
                            <td><?= htmlspecialchars($vale['numero_vale']) ?></td>
                            <td><?= number_format($vale['valor_total'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['valor_pago'], 2, ',', '.') ?></td>
                            <td><?= number_format($vale['saldo'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($vale['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($vale['data_registro'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-success">✅ Finalizar Vales Selecionados</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
