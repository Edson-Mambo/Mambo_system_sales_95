<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);



require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::conectar();

    // Recebendo dados do form
    $id_vale = $_POST['id_vale'] ?? null;
    $valor_pago = $_POST['valor_pago'] ?? null;
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? null;
    $numero_pagamento = $_POST['numero_pagamento'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';
    $status_vale = $_POST['status_vale_modal'] ?? 'Aberto';

    if (!$id_vale || !$valor_pago || !$metodo_pagamento) {
        echo json_encode(['success' => false, 'mensagem' => 'Campos obrigatórios faltando']);
        exit;
    }

    // Formata valor pago
    $valor_pago = floatval(str_replace(',', '.', $valor_pago));
    if ($valor_pago <= 0) {
        echo json_encode(['success' => false, 'mensagem' => 'Valor pago deve ser maior que zero']);
        exit;
    }

    // Busca vale
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

    // Inserir pagamento
    $stmtPag = $pdo->prepare("
        INSERT INTO pagamentos_vale 
        (vale_id, valor_pago, metodo_pagamento, numero_transacao, observacoes, data_pagamento)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmtPag->execute([$id_vale, $valor_pago, $metodo_pagamento, $numero_pagamento, $observacoes]);

    // Atualiza valor pago no vale
    $novo_valor_pago = floatval($vale['valor_pago']) + $valor_pago;

    // Determina status final
    if ($novo_valor_pago >= floatval($vale['valor_total'])) {
        $status_vale = 'Pago';
        $novo_valor_pago = floatval($vale['valor_total']); // Garante que não ultrapasse o total
    } elseif ($novo_valor_pago > 0) {
        $status_vale = 'Parcelado';
    } else {
        $status_vale = 'Aberto';
    }

    $stmtUpd = $pdo->prepare("UPDATE vales SET valor_pago = ?, status = ? WHERE id = ?");
    $stmtUpd->execute([$novo_valor_pago, $status_vale, $id_vale]);

    echo json_encode(['success' => true, 'mensagem' => 'Pagamento registrado com sucesso!', 'status_vale' => $status_vale, 'novo_valor_pago' => $novo_valor_pago]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
    exit;
}
