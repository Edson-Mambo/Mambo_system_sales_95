<?php
session_start();

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensagem' => 'Requisição inválida']);
    exit;
}

$id_vale = $_POST['id_vale'] ?? null;
$valor_pago_novo = floatval($_POST['valor_pago_novo'] ?? 0);
$metodo_pagamento = $_POST['metodo_pagamento'] ?? null;
$numero_transacao = $_POST['numero_transacao'] ?? null;

if (!$id_vale || $valor_pago_novo <= 0 || !$metodo_pagamento) {
    echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos ou inválidos']);
    exit;
}

try {
    $pdo = Database::conectar();

    // Buscar valor total do vale
    $stmt = $pdo->prepare("SELECT valor_total FROM vales WHERE id = ?");
    $stmt->execute([$id_vale]);
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vale) {
        echo json_encode(['success' => false, 'mensagem' => 'Vale não encontrado']);
        exit;
    }

    $valor_total = floatval($vale['valor_total']);

    // Somar valor pago até agora
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_pago), 0) AS total_pago FROM pagamentos_vale WHERE vale_id = ?");
    $stmt->execute([$id_vale]);
    $total_pago = floatval($stmt->fetchColumn());

    $novo_total_pago = $total_pago + $valor_pago_novo;

    if ($novo_total_pago > $valor_total) {
        echo json_encode(['success' => false, 'mensagem' => 'Valor pago excede o saldo do vale']);
        exit;
    }

    // Verificar quantas parcelas já foram pagas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagamentos_vale WHERE vale_id = ?");
    $stmt->execute([$id_vale]);
    $parcelas_pagas = intval($stmt->fetchColumn());

    if ($parcelas_pagas >= 3 && $novo_total_pago < $valor_total) {
        echo json_encode(['success' => false, 'mensagem' => 'Limite de 3 parcelas atingido. Pague o saldo total restante']);
        exit;
    }

    // Inserir novo pagamento na tabela de parcelas
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos_vale (vale_id, valor_pago, metodo_pagamento, numero_transacao)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$id_vale, $valor_pago_novo, $metodo_pagamento, $numero_transacao]);

    // Atualizar status do vale
    if ($novo_total_pago == $valor_total) {
        $novo_status = 'pago';
    } elseif ($novo_total_pago < $valor_total) {
        $novo_status = 'parcelado';
    } else {
        $novo_status = 'pendente';
    }

    $stmt = $pdo->prepare("UPDATE vales SET status_pagamento = ? WHERE id = ?");
    $stmt->execute([$novo_status, $id_vale]);

    echo json_encode([
        'success' => true,
        'mensagem' => 'Pagamento registrado com sucesso! Saldo restante: MT ' . number_format($valor_total - $novo_total_pago, 2, ',', '.')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
