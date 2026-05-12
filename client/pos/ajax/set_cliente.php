<?php

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['cliente_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Cliente inválido"
    ]);
    exit;
}

$_SESSION['cliente_id'] = (int)$data['cliente_id'];

echo json_encode([
    "success" => true
]);