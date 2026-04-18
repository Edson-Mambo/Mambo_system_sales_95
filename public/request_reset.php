<?php
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identificador = trim($_POST['identificador']);

    // procurar user
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR nome = ?");
    $stmt->execute([$identificador, $identificador]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Usuário não encontrado.");
    }

    // gerar token seguro
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // guardar no banco
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expires]);

    // link de reset
    $link = "http://localhost/MamboSystem95/public/reset_password.php?token=$token";

    // enviar email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'SEU_EMAIL@gmail.com';
        $mail->Password = 'SENHA_APP_GOOGLE';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('SEU_EMAIL@gmail.com', 'MamboSystem95');
        $mail->addAddress($user['email']);

        $mail->Subject = "Recuperação de Password";
        $mail->Body = "Clique para redefinir sua password: $link";

        $mail->send();

        echo "Email enviado com sucesso.";

    } catch (Exception $e) {
        echo "Erro ao enviar email: {$mail->ErrorInfo}";
    }
}
?>