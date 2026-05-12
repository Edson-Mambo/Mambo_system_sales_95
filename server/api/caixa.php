<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

try {

    $pdo = Database::conectar();

    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    // 🔥 DEBUG IMPORTANTE
    if (!$input) {
        throw new Exception("JSON inválido ou vazio: " . $raw);
    }

    $usuario_id = $input['usuario_id'] ?? null;
    $valor_abertura = $input['valor_abertura'] ?? 0;
    $valor_fecho = $input['valor_fecho'] ?? 0;
    $data_abertura = $input['data_abertura'] ?? date('Y-m-d H:i:s');
    $data_fecho = $input['data_fecho'] ?? date('Y-m-d H:i:s');

    if (!$usuario_id) {
        throw new Exception("usuario_id não enviado");
    }

    // 🔥 calcular vendas reais
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0)
        FROM vendas
        WHERE usuario_id = ?
        AND data_venda BETWEEN ? AND ?
    ");

    $stmt->execute([
        $usuario_id,
        $data_abertura,
        $data_fecho
    ]);

    $total_vendas = $stmt->fetchColumn();

    // 🔥 cálculo seguro
    $diferenca = ($valor_fecho + 0) - ($valor_abertura + $total_vendas);

    // 🔥 inserir fecho de caixa
    $stmt = $pdo->prepare("
        INSERT INTO caixa_fechos 
        (
            usuario_id,
            valor_abertura,
            valor_fecho,
            total_vendas,
            diferenca,
            data_abertura,
            data_fecho
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuario_id,
        $valor_abertura,
        $valor_fecho,
        $total_vendas,
        $diferenca,
        $data_abertura,
        $data_fecho
    ]);

    echo json_encode([
        "status" => "success",
        "total_vendas" => $total_vendas,
        "diferenca" => $diferenca
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "mensagem" => $e->getMessage()
    ]);
}