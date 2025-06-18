<?php
require_once '../../config/database.php';
$pdo = Database::conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE configuracoes SET
                nome_admin = :nome_admin,
                email_admin = :email_admin,
                telefone_suporte = :telefone_suporte,
                endereco = :endereco,
                horario_atendimento = :horario_atendimento,
                website = :website,
                ssl_ativado = :ssl_ativado,
                limite_conexoes = :limite_conexoes,
                tempo_expiracao = :tempo_expiracao
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome_admin' => $_POST['nome_admin'],
        ':email_admin' => $_POST['email_admin'],
        ':telefone_suporte' => $_POST['telefone_suporte'],
        ':endereco' => $_POST['endereco'],
        ':horario_atendimento' => $_POST['horario_atendimento'],
        ':website' => $_POST['website'],
        ':ssl_ativado' => $_POST['ssl_ativado'],
        ':limite_conexoes' => $_POST['limite_conexoes'],
        ':tempo_expiracao' => $_POST['tempo_expiracao'],
        ':id' => $_POST['id'],
    ]);

    header('Location: configuracoes.php');
    exit();
}
?>