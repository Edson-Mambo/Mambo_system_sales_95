<?php

$lang = $_GET['lang'] ?? 'pt';
$_SESSION['lang'] = $lang;
header("Location: index.php");
exit;
