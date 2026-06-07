<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();
$erro = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = "Preencha email e senha";
    } else {

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {

            session_regenerate_id(true);

            /* =========================
               SESSÃO PADRONIZADA
            ========================= */
            $_SESSION['usuario_id'] = (int)$user['id'];
            $_SESSION['nome'] = $user['nome'];

            // 🔥 IMPORTANTE: compatível com POS
            $_SESSION['nivel'] = strtolower(trim($user['nivel']));

            unset($_SESSION['abertura_id'], $_SESSION['carrinho']);

            /* =========================
               REDIRECIONAMENTO POR NÍVEL
            ========================= */
            switch ($_SESSION['nivel']) {

                case 'admin':
                    header("Location: index_admin.php");
                    exit;

                case 'gerente':
                    header("Location: index_gerente.php");
                    exit;

                case 'supervisor':
                    header("Location: index_supervisor.php");
                    exit;

                case 'caixa':

                    $usuario_id = (int)$user['id'];

                    /* =========================
                       VERIFICAR CAIXA ABERTO
                    ========================= */
                    $stmt = $pdo->prepare("
                        SELECT id
                        FROM abertura_caixa
                        WHERE usuario_id = ?
                        AND status = 'aberto'
                        LIMIT 1
                    ");
                    $stmt->execute([$usuario_id]);
                    $abertura = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$abertura) {

                        $stmt = $pdo->prepare("
                            INSERT INTO abertura_caixa
                            (usuario_id, valor_inicial, status)
                            VALUES (?, 0, 'aberto')
                        ");
                        $stmt->execute([$usuario_id]);

                        $abertura_id = $pdo->lastInsertId();

                    } else {
                        $abertura_id = $abertura['id'];
                    }

                    $_SESSION['abertura_id'] = $abertura_id;

                    header("Location: /Mambo_system_sales_95/client/pos/index.php");

                    exit;

                default:
                    header("Location: login.php?erro=nivel_invalido");
                    exit;
            }

        } else {
            $erro = "Credenciais inválidas";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Login - Mambo System 95</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-dark">

<div class="container mt-5">
<div class="col-md-4 mx-auto">

<div class="card shadow-lg">

<div class="card-header bg-primary text-white text-center">
<h4>🔐 Mambo System 95</h4>
</div>

<div class="card-body">

<?php if (!empty($erro)): ?>
<div class="alert alert-danger">
    <?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<form method="POST">

<input name="usuario" class="form-control mb-2" placeholder="Email" required>

<input name="senha" type="password" class="form-control mb-2" placeholder="Senha" required>

<button name="login" class="btn btn-primary w-100">
Entrar
</button>

</form>

</div>
</div>

</div>
</div>

</body>
</html>