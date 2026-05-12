<?php

require_once __DIR__ . '/../services/SyncService.php';

header('Content-Type: application/json');

try {
    $result = SyncService::syncClientes();

    echo json_encode([
        "success" => true,
        "message" => "Clientes sincronizados",
        "data" => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}