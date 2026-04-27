<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class ImpressoraFactory
{
    public static function criar($config)
    {
        $tipo = $config['tipo_impressora'] ?? 'windows';

        if ($tipo === 'windows') {

            $nome = $config['nome_impressora'] ?? 'GP-C80 Series';

            // fallback automático seguro
            $impressoras = [
                "BIXOLON SRP-350II",
                "GP-C80 Series"
            ];

            if (!in_array($nome, $impressoras)) {
                $nome = "GP-C80 Series";
            }

            return new Printer(
                new WindowsPrintConnector($nome)
            );
        }

        if ($tipo === 'rede') {

            return new Printer(
                new NetworkPrintConnector(
                    $config['ip_impressora'],
                    $config['porta_impressora'] ?? 9100
                )
            );
        }

        throw new Exception("Tipo de impressora inválido");
    }
}