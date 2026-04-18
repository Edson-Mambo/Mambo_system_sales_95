<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {

    if (!($_SESSION['reset_auth'] ?? false)) {
        http_response_code(403);
        exit;
    }

    $user_id = intval($_POST['user_id']);
    $admin_password = $_POST['admin_password'] ?? '';
    $new_password = trim($_POST['new_password'] ?? '');
    $mode = $_POST['mode'] ?? 'auto'; // auto | manual

    /* =========================
       🔐 VALIDAR ADMIN
    ========================= */
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['reset_admin']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($admin_password, $admin['senha'])) {
        echo json_encode(["ok" => false, "msg" => "Senha admin inválida"]);
        exit;
    }

    /* =========================
       👤 BUSCAR USER
    ========================= */
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["ok" => false, "msg" => "Usuário não encontrado"]);
        exit;
    }

    /* =========================
       🔑 DEFINIR PASSWORD
    ========================= */
    if ($mode === 'manual') {

        if ($new_password === '') {
            echo json_encode(["ok" => false, "msg" => "Password vazia"]);
            exit;
        }

        $plain = $new_password;

    } else {
        $plain = bin2hex(random_bytes(4));
    }

    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->execute([$hash, $user_id]);

    /* =========================
       🧾 LOG ERP
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO logs_password_reset (admin_id, user_id, action, ip)
        VALUES (?, ?, 'reset', ?)
    ");

    $stmt->execute([
        $_SESSION['reset_admin'],
        $user_id,
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        "ok" => true,
        "senha" => $plain
    ]);
    exit;
}
?>

<form method="POST">
    <input type="password" name="senha" placeholder="Nova password" required>
    <button type="submit">Atualizar</button>
</form>