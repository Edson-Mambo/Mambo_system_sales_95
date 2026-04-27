<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/voltar_menu.php';

/* =========================
   SEGURANÇA (opcional ERP)
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   VARIÁVEIS
========================= */
$mensagem = '';

$nivels_validos = [
    'caixa',
    'supervisor',
    'gerente',
    'admin',
    'store',
    'teka_away'
];

/* =========================
   PROCESSO POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome   = trim($_POST['nome'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $senha  = $_POST['senha'] ?? '';
    $nivel  = $_POST['nivel'] ?? '';

    /* VALIDAÇÃO */
    if (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($nivel, $nivels_validos)
    ) {
        $mensagem = "❌ Preencha todos os campos corretamente.";
    } else {

        try {
            $pdo = Database::conectar();

            /* VERIFICA EMAIL */
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $mensagem = "⚠️ Email já está em uso.";
            } else {

                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, nivel)
                    VALUES (?, ?, ?, ?)
                ");

                if ($stmt->execute([$nome, $email, $hashSenha, $nivel])) {
                    $mensagem = "✅ Usuário cadastrado com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao cadastrar usuário.";
                }
            }

        } catch (PDOException $e) {
            $mensagem = "❌ Erro no sistema: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Cadastrar Usuário</title>

<link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">

<style>
body {
    background: #eef2f7;
}

/* CARD ERP */
.card-erp {
    max-width: 600px;
    margin: auto;
    margin-top: 40px;
    padding: 25px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

/* HEADER */
.header {
    text-align: center;
    margin-bottom: 20px;
}

/* BOTÃO VOLTAR */
.voltar {
    text-align: center;
    margin-top: 20px;
}
</style>
</head>

<body>

<div class="container">

    <div class="card-erp">

        <!-- HEADER -->
        <div class="header">
            <h3>👤 Cadastro de Usuário</h3>
            <small>Sistema ERP</small>
        </div>

        <!-- MENSAGEM -->
        <?php if ($mensagem): ?>
            <div class="alert alert-info text-center">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="POST">

            <div class="mb-3">
                <label>Nome Completo</label>
                <input type="text" name="nome" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Nível de Acesso</label>
                <select name="nivel" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="caixa">Caixa</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="gerente">Gerente</option>
                    <option value="admin">Admin</option>
                    <!--<option value="store">Store</option>-->
                    <!--<option value="teka_away">Teka Away</option>-->
                </select>
            </div>

            <button class="btn btn-primary w-100">
                Criar Usuário
            </button>

        </form>

    </div>

    <!-- VOLTAR -->
    <div class="voltar">
        <a href="<?= voltarMenu() ?>" class="btn btn-outline-secondary">
            ← Voltar
        </a>
    </div>

</div>

</body>
</html>