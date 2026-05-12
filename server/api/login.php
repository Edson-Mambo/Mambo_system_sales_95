<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

try {

    $pdo = Database::conectar();

    $input = json_decode(file_get_contents("php://input"), true);

    $email = $input['email'] ?? '';
    $senha = $input['senha'] ?? '';

    if (!$email || !$senha) {
        throw new Exception("Dados inválidos");
    }

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Usuário não encontrado");
    }

    if (!password_verify($senha, $user['senha'])) {
        throw new Exception("Senha incorreta");
    }

    echo json_encode([
        "status" => "success",
        "usuario" => [
            "id" => $user['id'],
            "nome" => $user['nome'],
            "nivel" => $user['nivel']
        ]
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "mensagem" => $e->getMessage()
    ]);
}