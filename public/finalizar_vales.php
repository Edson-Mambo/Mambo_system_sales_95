<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::conectar();

    $id_vale = $_POST['id_vale'] ?? null;
    $valor_pago = $_POST['valor_pago'] ?? null;
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? null;
    $numero_pagamento = $_POST['numero_pagamento'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';

    if (!$id_vale || !$valor_pago || !$metodo_pagamento) {
        echo json_encode(['success' => false, 'mensagem' => 'Campos obrigatórios faltando']);
        exit;
    }

    $valor_pago = floatval(str_replace(',', '.', $valor_pago));
    if ($valor_pago <= 0) {
        echo json_encode(['success' => false, 'mensagem' => 'Valor pago deve ser maior que zero']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM vales WHERE id = ?");
    $stmt->execute([$id_vale]);
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vale) {
        echo json_encode(['success' => false, 'mensagem' => 'Vale não encontrado']);
        exit;
    }

    $saldo = floatval($vale['valor_total']) - floatval($vale['valor_pago']);
    if ($valor_pago > $saldo) {
        echo json_encode(['success' => false, 'mensagem' => 'Valor pago não pode ser maior que o saldo atual']);
        exit;
    }

    $stmtPag = $pdo->prepare("
        INSERT INTO pagamentos_vale 
        (vale_id, valor_pago, metodo_pagamento, numero_transacao, observacoes, data_pagamento)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmtPag->execute([$id_vale, $valor_pago, $metodo_pagamento, $numero_pagamento, $observacoes]);

    $novo_valor_pago = floatval($vale['valor_pago']) + $valor_pago;
    $stmtUpd = $pdo->prepare("UPDATE vales SET valor_pago = ? WHERE id = ?");
    $stmtUpd->execute([$novo_valor_pago, $id_vale]);

    echo json_encode(['success' => true, 'mensagem' => 'Pagamento registrado com sucesso!']);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
    exit;
}
