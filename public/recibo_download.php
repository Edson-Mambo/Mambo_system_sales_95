<?php
session_start();

if (isset($_SESSION['recibo_path'])) {
    $file = __DIR__ . '/../' . $_SESSION['recibo_path'];
    unset($_SESSION['recibo_path']);

    if (file_exists($file)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    } else {
        echo "Arquivo não encontrado.";
    }
} else {
    echo "Recibo não disponível.";
}
