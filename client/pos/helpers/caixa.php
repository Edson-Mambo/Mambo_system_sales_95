<?php

require_once __DIR__ . '/../config/database.php';

function verificarCaixaAberto()
{
    $pdo = Database::conectar();

    $abertura_id = $_SESSION['abertura_id'] ?? null;

    if (!$abertura_id) {
        die("Caixa não aberto");
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM abertura_caixa
        WHERE id = ?
        AND status = 'aberto'
    ");

    $stmt->execute([$abertura_id]);

    if (!$stmt->fetch()) {

        unset($_SESSION['abertura_id']);

        die("Caixa fechado");
    }
}