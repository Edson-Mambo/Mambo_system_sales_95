<?php
// /public/factura_cotacao.php

require_once '../src/Controller/FacturaCotacaoController.php';

$controller = new FacturaCotacaoController();
$controller->gerar();
