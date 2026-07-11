<?php

// Caminho corrigido: estava faltando a barra após __DIR__
require_once __DIR__ . '/../../../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class ImpressoraFactory
{
    public static function criar($config): Printer
    {
        $tipo = strtolower(trim($config['tipo_impressora'] ?? 'windows'));

        /* =========================
           WINDOWS (USB / SHARED)
        ========================= */
        if ($tipo === 'windows') {

            $nome = trim($config['nome_impressora'] ?? '');

            // Fallback seguro se vier vazio ou inválido
            if ($nome === '') {
                $nome = 'GP-C80 Series';
            }

            return new Printer(
                new WindowsPrintConnector($nome)
            );
        }

        /* =========================
           REDE (TCP/IP)
        ========================= */
        if ($tipo === 'rede') {

            $ip   = trim($config['ip_impressora'] ?? '');
            $port = (int)($config['porta_impressora'] ?? 9100);

            if ($ip === '') {
                throw new \Exception("IP da impressora não configurado.");
            }

            return new Printer(
                new NetworkPrintConnector($ip, $port)
            );
        }

        throw new \Exception("Tipo de impressora inválido: '{$tipo}'. Use 'windows' ou 'rede'.");
    }
}