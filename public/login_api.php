<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

/* LOGIN */
if ($action === 'login') {

    $email = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nivel_acesso'] = $user['nivel'];

        echo json_encode([
            "ok" => true,
            "redirect" => match($user['nivel']) {
                'admin' => 'index_admin.php',
                'gerente' => 'index_gerente.php',
                'supervisor' => 'index_supervisor.php',
                'caixa' => 'venda.php',
                default => 'index.php'
            }
        ]);
        exit;
    }

    echo json_encode(["ok" => false, "msg" => "Credenciais inválidas"]);
    exit;
}

/* ADMIN AUTH */
if ($action === 'auth_admin') {

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("
        SELECT * FROM usuarios 
        WHERE email = ? AND nivel IN ('admin','gerente')
    ");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($senha, $admin['senha'])) {

        $_SESSION['reset_admin'] = $admin['id'];
        $_SESSION['reset_auth'] = true;

        echo json_encode(["ok" => true]);
        exit;
    }

    echo json_encode(["ok" => false, "msg" => "Acesso negado"]);
    exit;
}

/* LIST USERS */
if ($action === 'list_users') {

    if (!($_SESSION['reset_auth'] ?? false)) {
        echo json_encode(["ok" => false, "msg" => "Não autorizado"]);
        exit;
    }

    $stmt = $pdo->query("SELECT id, nome, email, nivel FROM usuarios");

    echo json_encode([
        "ok" => true,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
}

/* RESET PASSWORD */
if ($action === 'reset_password') {

    if (!($_SESSION['reset_auth'] ?? false)) {
        echo json_encode(["ok" => false, "msg" => "Sem permissão"]);
        exit;
    }

    $user_id = (int)($_POST['user_id'] ?? 0);
    $admin_password = $_POST['admin_password'] ?? '';

    // validar admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['reset_admin']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($admin_password, $admin['senha'])) {
        echo json_encode(["ok" => false, "msg" => "Senha admin inválida"]);
        exit;
    }

    $novaSenha = bin2hex(random_bytes(4));
    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE usuarios SET senha=? WHERE id=?");
    $stmt->execute([$hash, $user_id]);

    echo json_encode([
        "ok" => true,
        "senha" => $novaSenha
    ]);
    exit;
}

echo json_encode(["ok" => false]);