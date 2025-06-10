<?php
session_start();
require_once __DIR__ . '/../config/database.php';  // Inclui a classe Database

// Verifica se o usuário está logado
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // Conectar ao banco
    $pdo = Database::conectar();

    // Atualizar o logout_time para o último login desse usuário
    // Supondo que o último registro seja o mais recente (ORDER BY id DESC LIMIT 1)
    $stmt = $pdo->prepare("
        UPDATE logs_login 
        SET logout_time = NOW() 
        WHERE usuario_id = ? 
        ORDER BY login_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
}

// Destruir sessão
$_SESSION = [];
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;
