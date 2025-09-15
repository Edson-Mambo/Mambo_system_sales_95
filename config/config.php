<?php


// Idioma padrão (português)
$lang = $_SESSION['lang'] ?? 'pt';

// Carregar traduções
$translations = include dirname(__DIR__) . "/config/lang.php";

// Função para traduzir
function __($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
