<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// evita qualquer output que quebre JSON
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = Database::conectar();

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        echo json_encode([
            "status" => "error",
            "mensagem" => "JSON inválido"
        ]);
        exit;
    }

    $senha = trim($input['senha'] ?? '');

    if (!$senha) {
        echo json_encode([
            "status" => "error",
            "mensagem" => "Senha obrigatória"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT senha 
        FROM usuarios 
        WHERE nivel IN ('admin','gerente')
    ");
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $u) {
        if (password_verify($senha, $u['senha'])) {
            echo json_encode(["status" => "success"]);
            exit;
        }
    }

    echo json_encode([
        "status" => "error",
        "mensagem" => "Senha inválida"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "mensagem" => "Erro interno"
    ]);
}