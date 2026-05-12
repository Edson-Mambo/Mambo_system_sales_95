<?php

function jsonResponse($success, $message = '', $data = [])
{
    header('Content-Type: application/json');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);

    exit;
}