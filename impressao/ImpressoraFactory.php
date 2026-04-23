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

        // WINDOWS
        if ($tipo === 'windows') {

            $nome = $config['nome_impressora'] ?? '';

            if (empty($nome)) {
                // fallback automático (Windows default printer)
                $nome = trim(shell_exec('wmic printer where default=true get name'));
                $nome = preg_replace('/Name|\r|\n/', '', $nome);
            }

            return new Printer(new WindowsPrintConnector($nome));
        }

        // REDE
        if ($tipo === 'rede') {

            if (empty($config['ip_impressora'])) {
                throw new Exception("IP não configurado");
            }

            $porta = $config['porta_impressora'] ?? 9100;

            return new Printer(
                new NetworkPrintConnector($config['ip_impressora'], $porta)
            );
        }

        throw new Exception("Tipo inválido");
    }
}