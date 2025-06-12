<?php
require_once '../config/database.php';

session_start();

$step = $_POST['step'] ?? 'choose'; // etapa atual (escolha ou form)
$tipo_usuario = $_POST['tipo_usuario'] ?? null;
$mensagem = '';

if ($step === 'register' && $tipo_usuario) {
    // Processar cadastro
    $nome = $_POST['nome'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$usuario || !$senha) {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        try {
            $pdo = Database::conectar();

            // Verificar se já existe usuário
            $check = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
            $check->execute([$usuario]);

            if ($check->rowCount() > 0) {
                $mensagem = "Erro: Nome de usuário já está em uso!";
            } else {
                // Inserir usuário
                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, tipo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $usuario, $hashSenha, $tipo_usuario]);

                // Redirecionar para menu conforme tipo
                if ($tipo_usuario === 'store') {
                    header("Location: store_menu.php");
                    exit;
                } elseif ($tipo_usuario === 'teka_away') {
                    header("Location: teka_away_menu.php");
                    exit;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container py-5">
    <h2 class="mb-4">Cadastro de Usuário</h2>

    <?php if ($step === 'choose'): ?>
        <!-- Etapa 1: Escolha do tipo de usuário -->
        <form method="POST" action="cadastrar_usuario.php" class="text-center">
            <input type="hidden" name="step" value="form" />
            <button name="tipo_usuario" value="store" class="btn btn-primary btn-lg mx-3">Usuário Store</button>
            <button name="tipo_usuario" value="teka_away" class="btn btn-success btn-lg mx-3">Usuário Teka Away</button>
        </form>

    <?php elseif ($step === 'form' && $tipo_usuario): ?>
        <!-- Etapa 2: Formulário de cadastro com tipo selecionado -->
        <form method="POST" action="cadastrar_usuario.php" class="w-50 mx-auto">
            <input type="hidden" name="step" value="register" />
            <input type="hidden" name="tipo_usuario" value="<?= htmlspecialchars($tipo_usuario) ?>" />

            <?php if ($mensagem): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" id="nome" name="nome" class="form-control" required />
            </div>

            <div class="mb-3">
                <label for="usuario" class="form-label">Nome de Usuário</label>
                <input type="text" id="usuario" name="usuario" class="form-control" required />
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required />
            </div>

            <button type="submit" class="btn btn-primary">Cadastrar Usuário <?= $tipo_usuario === 'store' ? '(Store)' : '(Teka Away)' ?></button>
        </form>

        <div class="text-center mt-3">
            <a href="cadastrar_usuario.php" class="btn btn-secondary">Voltar para escolher tipo</a>
        </div>
    <?php endif; ?>
</body>
</html>
