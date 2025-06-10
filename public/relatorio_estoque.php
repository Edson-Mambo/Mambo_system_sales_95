<?php
require_once '../config/database.php';

$pdo = Database::conectar();

$relatorio = $pdo->query("
    SELECT a.*, p.nome, u.nome AS usuario 
    FROM ajustes_estoque a 
    JOIN produtos p ON a.produto_id = p.id 
    JOIN usuarios u ON a.ajustado_por = u.id 
    ORDER BY data_ajuste DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Ajustes de Estoque</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <h2 class="mb-4">Relatório de Ajustes de Estoque</h2>

    <button class="btn btn-secondary mb-3" onclick="history.back()">← Voltar</button>


    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Motivo</th>
                <th>Usuário</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($relatorio as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['nome']) ?></td>
                <td><?= htmlspecialchars($r['quantidade_ajustada']) ?></td>
                <td><?= htmlspecialchars($r['motivo']) ?></td>
                <td><?= htmlspecialchars($r['usuario']) ?></td>
                <td><?= htmlspecialchars($r['data_ajuste']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
