<?php
require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produtos'])) {
    $pdo = Database::conectar();

    try {
        $pdo->beginTransaction();

        foreach ($_POST['produtos'] as $produto) {
            $id = (int)$produto['id'];
            $quantidade_fisica = (int)$produto['quantidade_fisica'];

            // Insere registro de inventário físico
            $stmt = $pdo->prepare("INSERT INTO inventario_fisico (produto_id, quantidade_fisica) VALUES (?, ?)");
            $stmt->execute([$id, $quantidade_fisica]);
        }

        $pdo->commit();

        $_SESSION['mensagem'] = "Inventário físico salvo com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem'] = "Erro ao salvar inventário: " . $e->getMessage();
    }
} else {
    $_SESSION['mensagem'] = "Nenhum dado recebido.";
}

header("Location: inventario_fisico.php");
exit;
