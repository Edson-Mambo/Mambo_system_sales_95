<?php
session_start();

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {

        $erro = "Preencha email e senha";

    } else {

        $apiUrl = "http://localhost/Mambo_system_sales_95/server/api/login.php";

        $data = json_encode([
            "email" => $email,
            "senha" => $senha
        ]);

        $ch = curl_init($apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($response === false) {

            $erro = "Erro de ligação: " . $curlError;

        } else {

            $res = json_decode($response, true);

            if (!is_array($res)) {

                $erro = "Resposta inválida do servidor";

           } elseif (($res['status'] ?? '') === 'success') {

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $res['usuario']['id'];
    $_SESSION['nome'] = $res['usuario']['nome'];
    $_SESSION['nivel'] = strtolower(trim($res['usuario']['nivel']));
    $_SESSION['caixa_id'] = 1;

    $nivel = $_SESSION['nivel'];

    switch ($nivel) {

        case 'admin':
        case 'administrador':
            header("Location: /Mambo_system_sales_95/public/index_admin.php");
            exit;

        case 'gerente':
            header("Location: /Mambo_system_sales_95/public/index_gerente.php");
            exit;

        case 'supervisor':
            header("Location: /Mambo_system_sales_95/public/index_supervisor.php");
            exit;

        case 'caixa':
            header("Location: /Mambo_system_sales_95/client/pos/index.php");
            exit;

        default:
            $erro = "Perfil de usuário sem permissão.";
            break;
    }

}
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<title>POS - Mambo System</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />

<script src="../../bootstrap/bootstrap-5.3.3/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body class="bg-dark">

<div class="container mt-5">
<div class="col-md-4 mx-auto">

<div class="card shadow-lg">

<div class="card-header bg-primary text-white text-center">
<h4>🔐 Mambo System 95</h4>
</div>

<div class="card-body">

<form method="POST">
    <input name="email" class="form-control" placeholder="Email" required><br>
    <input name="senha" class="form-control mb-2" type="password" placeholder="Senha" required><br>
    <button class="btn btn-primary w-100" type="submit">Entrar</button>

    <?php if (!empty($erro)): ?>
        <p style="color:red;">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>
</form>

</div>
</div>

</div>
</div>

</body>
</html>