<?php
session_start();

if (!isset($_SESSION['nivel_acesso'])) {
    header('Location: login.php');
    exit;
}

switch ($_SESSION['nivel_acesso']) {
    case 'admin':
        header('Location: index_admin.php');
        break;
    case 'gerente':
        header('Location: index_gerente.php');
        break;
    case 'supervisor':
        header('Location: index_supervisor.php');
        break;
    default:
        header('Location: login.php');
        break;
}

exit;
