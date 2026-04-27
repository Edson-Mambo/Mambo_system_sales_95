<?php

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/empresa.php';
require_once __DIR__ . '/../services/ReciboImpressaoService.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = Database::conectar();
    $config = getConfigEmpresa($pdo);

    $venda_id = $_GET['venda_id'] ?? null;

    if (!$venda_id) {
        throw new Exception("Venda inválida");
    }

    /* =========================
       LIMPAR BUFFER (EVITA PDF/PRINT CORROMPER JSON)
    ========================= */
    ob_clean();

    /* =========================
       IMPRESSÃO CENTRALIZADA
    ========================= */
    $ok = ReciboImpressaoService::imprimir($venda_id, $pdo, $config);

    echo json_encode([
        "success" => $ok ? true : false,
        "venda_id" => $venda_id,
        "message" => $ok ? "Recibo impresso com sucesso" : "Falha na impressão"
    ]);

    exit;

} catch (Throwable $e) {

    ob_clean();

    echo json_encode([
        "success" => false,
        "erro" => $e->getMessage()
    ]);

    exit;
}