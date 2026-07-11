<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../server/services/ClienteService.php';

$termo = trim($_GET['termo'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$service  = new ClienteService();
$clientes = $service->buscar($termo);

echo json_encode($clientes ?: []);