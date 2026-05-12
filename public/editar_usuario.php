<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/voltar_menu.php';

$pdo = Database::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================
   VALIDAR ID
========================= */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: ../src/View/listar_usuario.php");
    exit();
}

/* =========================
   BUSCAR USUÁRIO
========================= */
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: ../src/View/listar_usuario.php");
    exit();
}

/* =========================
   ATUALIZAR USUÁRIO
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome   = trim($_POST['nome']);
    $email  = trim($_POST['email']);
    $senha  = $_POST['senha'];
    $nivel  = $_POST['nivel'];

    /* =========================
       SENHA ERP (SEGURA)
       - vazio = mantém atual
       - preenchido = atualiza hash
    ========================= */
    if (!empty($senha)) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    } else {
        $senhaHash = $usuario['senha'];
    }

    /* =========================
       UPDATE
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET nome = ?, email = ?, senha = ?, nivel = ?
        WHERE id = ?
    ");

    $ok = $stmt->execute([
        $nome,
        $email,
        $senhaHash,
        $nivel,
        $id
    ]);

    if ($ok) {
        header("Location: ../src/View/listar_usuario.php?success=1");
        exit();
    } else {
        echo "Erro ao atualizar usuário.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário - ERP</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-5">

    <h3 class="mb-4">✏️ Editar Usuário</h3>

    <form method="POST">

        <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">

        <!-- NOME -->
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control"
                   value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>

        <!-- EMAIL -->
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <!-- SENHA -->
        <div class="mb-3">
            <label>Senha (deixar vazio para manter atual)</label>
            <input type="password" name="senha" class="form-control"
                   placeholder="Nova senha">
        </div>

        <!-- NÍVEL ERP -->
        <div class="mb-3">
            <label>Nível de Acesso</label>
            <select name="nivel" class="form-select" required>

                <option value="gerente" 
                    <?= ($usuario['nivel'] === 'gerente') ? 'selected' : '' ?>>
                    Gerente
                </option>

                <option value="supervisor" 
                    <?= ($usuario['nivel'] === 'supervisor') ? 'selected' : '' ?>>
                    Supervisor
                </option>

                <option value="caixa" 
                    <?= ($usuario['nivel'] === 'caixa') ? 'selected' : '' ?>>
                    Caixa
                </option>

                <option value="teka_away" 
                    <?= ($usuario['nivel'] === 'teka_away') ? 'selected' : '' ?>>
                    Teka Away
                </option>

            </select>
        </div>

        <!-- BOTÕES -->
        <button type="submit" class="btn btn-primary">
            💾 Salvar Alterações
        </button>

        <a href="../src/View/listar_usuario.php" class="btn btn-secondary">
            ← Voltar
        </a>

    </form>

</body>
</html>