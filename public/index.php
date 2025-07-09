<?php
header("Location: login.php");

require_once __DIR__ . '/../src/Logger.php';

require_once '../configuracoes/logMiddleware.php';
// Conexão PDO
$pdo = Database::conectar();

// Inicializa logger automático
Logger::init($pdo);
exit;
