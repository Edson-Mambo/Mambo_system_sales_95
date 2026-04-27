<?php

require_once __DIR__ . '/vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

try {

    $connector = new WindowsPrintConnector("BIXOLON SRP-350II");
    $printer = new Printer($connector);

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("TESTE DE IMPRESSAO\n");
    $printer->text("MAMBO SYSTEM POS\n");
    $printer->cut();
    $printer->close();

    echo "Impressão enviada com sucesso";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}