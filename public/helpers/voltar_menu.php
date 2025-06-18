<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o destino do botão com base no nível de acesso
$nivel_acesso = $_SESSION['nivel_acesso'] ?? 'usuario';

switch ($nivel_acesso) {
    case 'admin':
        $pagina_destino = '../public/index_admin.php';
        break;
    case 'gerente':
        $pagina_destino = '../public/index_gerente.php';
        break;
    case 'supervisor':
        $pagina_destino = '../public/index_supervisor.php';
        break;
    default:
        $pagina_destino = '../index_usuario.php';
        break;
}
