<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

try {
    $pdo = Database::conectarLocal();
} catch (Throwable $e) {
    die("Erro de conexão: " . $e->getMessage());
}

/* =========================
   DATA HOJE
========================= */
$dataHoje = date('Y-m-d');

/* =========================
   VENDAS DO DIA
========================= */
try {

    $sql = "
        SELECT 
            v.id,
            v.total,
            v.data,
            v.metodo_pagamento,
            v.status,
            v.usuario_id,
            COALESCE(u.nome, 'Caixa não identificado') AS usuario_nome
        FROM vendas v
        LEFT JOIN usuarios u ON u.id = v.usuario_id
        WHERE date(v.data) = :data
        ORDER BY v.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':data' => $dataHoje]);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    die("Erro ao buscar vendas: " . $e->getMessage());
}

/* =========================
   TOTAL DO DIA
========================= */
$totalDia = 0;

foreach ($vendas as $v) {
    $totalDia += (float)($v['total'] ?? 0);
}

/* =========================
   USUÁRIO
========================= */
$usuario_nome = $_SESSION['nome'] ?? 'Caixa';

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Relatório de Vendas</title>

<style>
body {
    font-family: Arial, sans-serif;
    background:#f5f5f5;
    padding:20px;
}

h2 {
    margin-bottom: 10px;
}

table {
    width:100%;
    border-collapse: collapse;
    background:#fff;
    margin-top:10px;
}

th, td {
    border:1px solid #ddd;
    padding:10px;
    text-align:left;
}

th {
    background:#222;
    color:#fff;
}

.total-box {
    background:#222;
    color:#fff;
    padding:15px;
    margin-bottom:15px;
    font-size:18px;
    border-radius:5px;
}

.ok { color:green; font-weight:bold; }
.pend { color:orange; font-weight:bold; }
</style>

</head>

<body>

<h2>📊 Vendas do Dia (<?= $dataHoje ?>)</h2>

<div class="total-box">
💰 Total do Dia: <?= number_format($totalDia, 2, ',', '.') ?> MT
</div>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Caixa</th>
    <th>Total</th>
    <th>Método de Pagamento</th>
    <th>Status</th>
    <th>Data</th>
</tr>
</thead>

<tbody>

<?php if (empty($vendas)): ?>

<tr>
    <td colspan="6">Nenhuma venda hoje.</td>
</tr>

<?php else: ?>

<?php foreach ($vendas as $v): ?>

<?php
$status = strtolower($v['status'] ?? 'pendente');

$class = match($status) {
    'pago' => 'ok',
    'pendente' => 'pend',
    default => 'pend'
};

/* =========================
   FIX FINAL DO NOME DO CAIXA
========================= */
$usuario_nome = $v['usuario_nome']
    ?? $_SESSION['nome']
    ?? 'Caixa';
?>

<tr>
    <td><?= (int)$v['id'] ?></td>
    <td><?=  htmlspecialchars($usuario_nome) ?></td>
    <td><?= number_format((float)$v['total'], 2, ',', '.') ?></td>
    <td><?= htmlspecialchars($v['metodo_pagamento'] ?? '-') ?></td>
    <td class="<?= $class ?>"><?= htmlspecialchars($status) ?></td>
    <td><?= htmlspecialchars($v['data']) ?></td>
</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>
</table>

</body>
</html>