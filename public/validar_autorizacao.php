<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../config/database.php';

try {

    $pdo = Database::conectar();

    // receber JSON do fetch
    $data = json_decode(file_get_contents("php://input"), true);

    $senha = $data['senha'] ?? '';

    if (empty($senha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Senha obrigatória'
        ]);
        exit;
    }

    // buscar TODOS os utilizadores (evita erro de coluna inexistente)
    $stmt = $pdo->prepare("SELECT id, senha, nivel FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $autorizado = false;
    $usuario_autorizado = null;

    foreach ($usuarios as $user) {

        // adapta ao nome real da tua coluna
        $nivel = $user['nivel'] ?? $user['nivel_acesso'] ?? $user['role'] ?? '';

        $permitidos = ['admin', 'gerente', 'supervisor'];

        if (in_array($nivel, $permitidos)) {

            if (password_verify($senha, $user['senha'])) {
                $autorizado = true;
                $usuario_autorizado = $user['id'];
                break;
            }
        }
    }

    if ($autorizado) {

        echo json_encode([
            'success' => true,
            'usuario_id' => $usuario_autorizado
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Senha incorreta ou sem permissão'
        ]);
    }

} catch (Throwable $e) {

    // nunca deixar HTML escapar (evita erro JSON no JS)
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor'
    ]);
}