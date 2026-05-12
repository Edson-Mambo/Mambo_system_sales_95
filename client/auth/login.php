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
            $erro = "Erro de ligação com o servidor";
        } else {

            $res = json_decode($response, true);

            if (!is_array($res)) {
                $erro = "Resposta inválida do servidor";
            } elseif (($res['status'] ?? '') === 'success') {

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $res['usuario']['id'];
                $_SESSION['nome'] = $res['usuario']['nome'];
                $_SESSION['nivel'] = $res['usuario']['nivel'];

                // ⚠️ temporário (depois liga ao caixa real)
                $_SESSION['caixa_id'] = 1;

                header("Location: ../pos/index.php");
                exit;

            } else {
                $erro = $res['mensagem'] ?? 'Login inválido';
            }
        }
    }
}
?>

<form method="POST">
    <input name="email" placeholder="Email" required><br>
    <input name="senha" type="password" placeholder="Senha" required><br>
    <button type="submit">Entrar</button>

    <?php if (!empty($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
</form>