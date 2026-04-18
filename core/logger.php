<?php

function writeLog($pdo, $type, $action, $description = null)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs 
            (user_id, type, action, description, page, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $type,
            $action,
            $description,
            $_SERVER['REQUEST_URI'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

    } catch (Exception $e) {
        // nunca quebrar sistema por falha de log
    }
}