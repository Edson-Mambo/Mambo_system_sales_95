<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/InventarioController.php';

use Controller\InventarioController;

$pdo = Database::conectar();
$controller = new InventarioController($pdo);

// ⚠️ Aqui é onde busca os produtos
$produtos = $controller->listarProdutos();

// Inclui a view e passa $produtos
require_once __DIR__ . '/../src/View/inventario.view.php';
