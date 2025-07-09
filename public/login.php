<?php
session_start();
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/configuracoes/logMiddleware.php';




//require_once 'log.php';



// Supondo que $pdo, $user_id, $user_nome estejam definidos
//registrarLog($pdo, 'login_sucesso', 'Usuário logou com sucesso', $user_id, $user_nome, $_SERVER['REMOTE_ADDR']);


$pdo = Database::conectar();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['usuario'] ?? ''); // aqui o input continua 'usuario', mas representa o email
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        // Busca usuário pelo email (não mais pelo nome)
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica senha usando password_verify
            if (password_verify($senha, $user['senha'])) {
                // Login válido - criar sessão
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['nivel_acesso'] = $user['nivel'];

                // Registrar o login no banco
                $data_hora = date('Y-m-d H:i:s');
                $stmtLog = $pdo->prepare("INSERT INTO logs_login (usuario_id, login_time) VALUES (?, ?)");
                $stmtLog->execute([$user['id'], $data_hora]);

                // Redireciona conforme nível
                switch ($user['nivel']) {
                    case 'admin':
                        header('Location: index_admin.php');
                        exit;
                    case 'gerente':
                        header('Location: index_gerente.php');
                        exit;
                    case 'supervisor':
                        header('Location: index_supervisor.php');
                        exit;
                    case 'caixa':
                        header('Location: venda.php');
                        exit;
                    case 'store':
                        header('Location: store_menu.php');
                        exit;
                    case 'teka_away':
                        header('Location: teka_away_menu.php');
                        exit;
                    default:
                        $erro = 'Nível de acesso desconhecido.';
                }
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login - MamboSystem95</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>

</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center">
                        <h4>Login - MamboSystem95</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Email ou Nome</label>
                                <input type="text" name="usuario" id="usuario" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Entrar</button>
                        </form>
                    </div>
                    <div class="card-footer text-muted text-center">
                        &copy; <?= date('Y') ?> MamboSystem95
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['mensagem'])): ?>
    <div class="alert alert-warning">
        <?= htmlspecialchars($_GET['mensagem']) ?>
    </div>
<?php endif; ?>

</body>
</html>
