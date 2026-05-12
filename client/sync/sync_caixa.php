<?php

require_once __DIR__ . '/../services/SyncService.php';

header('Content-Type: application/json');

try {
    $result = SyncService::syncCaixa();

    echo json_encode([
        "success" => true,
        "message" => "Caixa sincronizado",
        "data" => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}