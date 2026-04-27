<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

$pdo = Database::conectar();

if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST['produtos'] as $produto) {

        $stmt = $pdo->prepare("
            INSERT INTO inventario_fisico (produto_id, quantidade_fisica, data_registro)
            VALUES (?, ?, NOW())
        ");

        $stmt->execute([
            $produto['id'],
            $produto['quantidade_fisica']
        ]);
    }

    $_SESSION['mensagem'] = "Inventário registado com sucesso!";
    header("Location: inventario_fisico.php");
    exit;
}