<?php
session_start();
require_once '../config/database.php';
require_once '../src/Controller/VendaController.php';

use Controller\VendaController;

$controller = new VendaController($pdo);

if (isset($_GET['venda_id'])) {
    $controller->gerarReciboPdf($_GET['venda_id']);
}
