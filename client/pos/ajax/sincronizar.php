<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../services/SyncService.php';

try {

    SyncService::initLocal();

    SyncService::pushToServer();

    SyncService::pullFromServer('produtos');

    echo json_encode([
        "success"=>true,
        "message"=>"Produtos sincronizados"
    ]);

} catch(Throwable $e){

    echo json_encode([
        "success"=>false,
        "message"=>$e->getMessage()
    ]);

}