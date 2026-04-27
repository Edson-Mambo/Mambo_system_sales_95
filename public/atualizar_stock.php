<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$pdo->beginTransaction();

try {

    $stmt = $pdo->query("
        SELECT 
            p.id,
            IFNULL(f.quantidade_fisica, 0) AS fisico
        FROM produtos p
        LEFT JOIN inventario_fisico f 
        ON f.id = (
            SELECT id 
            FROM inventario_fisico 
            WHERE produto_id = p.id 
            ORDER BY data_registro DESC 
            LIMIT 1
        )
    ");

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare("
        UPDATE produtos 
        SET estoque = ? 
        WHERE id = ?
    ");

    foreach ($produtos as $p) {
        $update->execute([(int)$p['fisico'], $p['id']]);
    }

    $pdo->commit();

    $_SESSION['mensagem'] = "Stock ajustado com sucesso!";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
}

header("Location: comparar_inventario.php");
exit;