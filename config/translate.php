<?php
// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}

// Alterar idioma via GET
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en', 'es'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$idioma = $_SESSION['lang'];

// Carregar o arquivo de tradução
$langFile = __DIR__ . "/../lang/$idioma.php";
if (file_exists($langFile)) {
    $TR = include $langFile;
} else {
    $TR = include __DIR__ . "/../lang/pt.php";
}

// Função global para traduzir
function __($key) {
    global $TR;
    return $TR[$key] ?? $key;
}
