
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class ImpressoraFactory
{
    public static function criar(array $config)
    {
        try {

            // 1️⃣ TENTA REDE
            if (!empty($config['ip_impressora'])) {
                if (self::testarRede($config['ip_impressora'])) {
                    return new Printer(
                        new NetworkPrintConnector(
                            $config['ip_impressora'],
                            $config['porta_impressora'] ?? 9100
                        )
                    );
                }
            }

            // 2️⃣ LISTA DE IMPRESSORAS WINDOWS
            $impressoras = [
                $config['nome_impressora'] ?? null,
                "BIXOLON SRP-350II",
                "GP-C80 Series"
            ];

            foreach ($impressoras as $nome) {

                if (!$nome) continue;

                try {
                    return new Printer(new WindowsPrintConnector($nome));
                } catch (Throwable $e) {
                    self::log("Falha na impressora: $nome");
                }
            }

            throw new Exception("Nenhuma impressora disponível");

        } catch (Throwable $e) {

            // 🔥 NUNCA QUEBRA O SISTEMA
            self::log("ERRO CRÍTICO IMPRESSÃO: " . $e->getMessage());

            return null; // importante!
        }
    }

    private static function testarRede($ip, $porta = 9100)
    {
        $con = @fsockopen($ip, $porta, $errno, $errstr, 1);

        if ($con) {
            fclose($con);
            return true;
        }

        return false;
    }

    private static function log($msg)
    {
        file_put_contents(
            __DIR__ . "/../logs/impressao.log",
            date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL,
            FILE_APPEND
        );
    }
}