<?php
require_once '../config/database.php';
$pdo = Database::conectar();

$id_vale = $_GET['id_vale'] ?? null;
if (!$id_vale) {
    echo json_encode(['success' => false, 'mensagem' => 'ID do vale não informado']);
    exit;
}

// Busca dados básicos do vale e cliente
$sql_vale = "
    SELECT 
        v.id,
        v.numero_vale,
        v.valor_total,
        v.status_pagamento,
        c.nome AS cliente_nome,
        c.telefone AS cliente_telefone
    FROM vales v
    JOIN clientes c ON c.id = v.cliente_id
    WHERE v.id = ?
";
$stmt_vale = $pdo->prepare($sql_vale);
$stmt_vale->execute([$id_vale]);
$vale = $stmt_vale->fetch(PDO::FETCH_ASSOC);

if (!$vale) {
    echo json_encode(['success' => false, 'mensagem' => 'Vale não encontrado']);
    exit;
}

// Soma total pago nas parcelas já registradas
$sql_pago = "SELECT COALESCE(SUM(valor_pago), 0) as total_pago, COUNT(*) as parcelas_pagas FROM pagamentos_vale WHERE vale_id = ?";
$stmt_pago = $pdo->prepare($sql_pago);
$stmt_pago->execute([$id_vale]);
$pagamento = $stmt_pago->fetch(PDO::FETCH_ASSOC);

$total_pago = (float)$pagamento['total_pago'];
$parcelas_pagas = (int)$pagamento['parcelas_pagas'];

$valor_total = (float)$vale['valor_total'];
$saldo = $valor_total - $total_pago;
if ($saldo < 0) $saldo = 0;

echo json_encode([
    'success' => true,
    'id_vale' => $vale['id'],
    'numero_vale' => $vale['numero_vale'],
    'valor_total' => $valor_total,
    'total_pago' => $total_pago,
    'saldo' => $saldo,
    'parcelas_pagas' => $parcelas_pagas,
    'status_pagamento' => $vale['status_pagamento'],
    'cliente_nome' => $vale['cliente_nome'],
    'cliente_telefone' => $vale['cliente_telefone']
]);
