<?php

require_once '../helpers/auth.php';
require_once '../helpers/caixa.php';

require_once '../config/database.php';

require_once '../services/CarrinhoService.php';
require_once '../services/ClienteService.php';

verificarCaixa();
verificarCaixaAberto();

$pdo = Database::conectar();

$carrinho = CarrinhoService::obterCarrinho();

$total = CarrinhoService::calcularTotal($carrinho);

$clienteSelecionado = ClienteService::obterClienteSelecionado($pdo);

?>

<!DOCTYPE html>
<html lang="pt">
<head>

    <meta charset="UTF-8">

    <title>Mambo POS</title>

    <link href="assets/css/venda.css" rel="stylesheet">

</head>

<body>

<?php require 'partials/header.php'; ?>

<?php require 'partials/carrinho.php'; ?>

<?php require 'partials/modais.php'; ?>

<script src="assets/js/venda.js"></script>
<script src="assets/js/cliente.js"></script>
<script src="assets/js/autorizacao.js"></script>

</body>
</html>