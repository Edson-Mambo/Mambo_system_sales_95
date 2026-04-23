<?php
session_start();

/* limpa qualquer output anterior */
if (ob_get_length()) {
    ob_end_clean();
}

if (!isset($_SESSION['recibo_path'])) {
    exit("Recibo não disponível.");
}

$file = __DIR__ . '/../' . ltrim($_SESSION['recibo_path'], '/');

unset($_SESSION['recibo_path']);

if (!file_exists($file)) {
    exit("Arquivo não encontrado.");
}

/* garante que é PDF válido */
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    exit("Ficheiro inválido (não é PDF).");
}

/* headers corretos */
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));

readfile($file);
exit;