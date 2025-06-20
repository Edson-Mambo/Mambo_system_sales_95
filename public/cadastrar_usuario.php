<?php
require_once '../config/database.php';

session_start();
include 'helpers/voltar_menu.php'; 

$mensagem = '';
$nivels_validos = ['caixa', 'supervisor', 'gerente', 'admin', 'store', 'teka_away'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $nivel = $_POST['nivel'] ?? '';

    if (!$nome || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$senha || !in_array($nivel, $nivels_validos)) {
        $mensagem = "Por favor, preencha todos os campos corretamente e informe um email válido.";
    } else {
        try {
            $pdo = Database::conectar();

            // Verificar se já existe usuário com mesmo email
            $check = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $mensagem = "Erro: Email já está em uso!";
            } else {
                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$nome, $email, $hashSenha, $nivel])) {
                    // Redireciona conforme nível
                    if ($nivel === 'store') {
                        header("Location: store_menu.php");
                        exit;
                    } elseif ($nivel === 'teka_away') {
                        header("Location: teka_away_menu.php");
                        exit;
                    } else {
                        header("Location: painel_admin.php");
                        exit;
                    }
                } else {
                    $mensagem = "Erro ao inserir usuário.";
                }
            }
        } catch (PDOException $e) {
            $mensagem = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Cadastrar Usuário</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container py-5">
    <h2 class="mb-4">Cadastro de Usuário</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="POST" action="cadastrar_usuario.php" class="w-50 mx-auto">

        <div class="mb-3">
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" id="nome" name="nome" class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email (Nome de Usuário)</label>
            <input type="email" id="email" name="email" class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="nivel" class="form-label">Nível do Usuário</label>
            <select id="nivel" name="nivel" class="form-select" required>
                <option value="" disabled selected>Selecione o nível</option>
                <option value="caixa">Caixa</option>
                <option value="supervisor">Supervisor</option>
                <option value="gerente">Gerente</option>
                <option value="admin">Admin</option>
                <option value="store">Store</option>
                <option value="teka_away">Teka Away</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar Usuário</button>
    </form>

    <div class="text-center mt-4">
        <a href="voltar.php" class="btn btn-secondary">← Voltar ao Painel</a>
    </div>
</body>
</html>
