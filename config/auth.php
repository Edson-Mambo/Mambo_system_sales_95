<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica login obrigatório
 */
function require_login()
{
    if (empty($_SESSION['usuario_id'])) {
        header("Location: /public/login.php");
        exit;
    }
}

/**
 * Verifica nível de acesso
 */
function require_role(array $roles)
{
    $nivel = $_SESSION['nivel_acesso'] ?? '';

    if (!in_array($nivel, $roles)) {
        header("Location: /public/index.php");
        exit;
    }
}

/**
 * Retorna nível atual
 */
function user_role()
{
    return $_SESSION['nivel_acesso'] ?? '';
}

/**
 * Retorna rota de voltar padrão
 */
function back_route()
{
    $nivel = user_role();

    $rotas = [
        'admin' => '/public/index_admin.php',
        'gerente' => '/public/index_gerente.php',
        'supervisor' => '/public/index_supervisor.php'
    ];

    return $rotas[$nivel] ?? '/public/index.php';
}