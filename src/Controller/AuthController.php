<?php
// src/Controller/AuthController.php

session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $_SESSION['erro_login'] = "Usuário e senha são obrigatórios.";
        header('Location: ../../public/login.php');
        exit;
    }

    $pdo = conectar();

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
        $_SESSION['id_usuario'] = $user['id'];

        header('Location: ../../public/venda.php');
        exit;
    } else {
        $_SESSION['erro_login'] = "Usuário ou senha incorretos.";
        header('Location: ../../public/login.php');
        exit;
    }
    // Depois de verificar o login e iniciar a sessão:
    $stmt = $pdo->prepare("INSERT INTO logs_login (id_usuario, hora_login) VALUES (:id_usuario, NOW())");
    $stmt->execute(['id_usuario' => $user['id']]);
    $_SESSION['id_log'] = $pdo->lastInsertId(); // Guardar o ID do log para usar no logout

}
