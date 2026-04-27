<?php

require_once __DIR__ . '/../impressao/ImpressoraFactory.php';

use Mike42\Escpos\Printer;

class ResumoCaixaImpressaoService
{
    public static function imprimir($abertura_id, $usuario_id, $pdo, $config)
    {
        try {

            /* =========================
               CONFIG SEGURA
            ========================= */
            $config = array_merge([
                'nome_empresa' => 'EMPRESA',
                'endereco' => '',
                'rua_avenida' => '',
                'bairro' => '',
                'cidade' => '',
                'provincia' => '',
                'telefone' => '',
                'email_empresa' => '',
                'nuit_empresa' => ''
            ], $config ?? []);

            /* =========================
               ABERTURA CAIXA
            ========================= */
            $stmt = $pdo->prepare("
                SELECT * FROM abertura_caixa
                WHERE id = ?
            ");
            $stmt->execute([$abertura_id]);
            $abertura = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$abertura) {
                throw new Exception("Abertura de caixa não encontrada");
            }

            /* =========================
               RESUMO CAIXA (CORRIGIDO)
            ========================= */
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(id) AS total_vendas,
                    COALESCE(SUM(total),0) AS total_faturado,
                    COALESCE(SUM(valor_pago),0) AS total_pago,
                    COALESCE(SUM(troco),0) AS total_troco
                FROM vendas
                WHERE abertura_id = ?
            ");
            $stmt->execute([$abertura_id]);
            $resumo = $stmt->fetch(PDO::FETCH_ASSOC);

            /* =========================
               IMPRESSORA
            ========================= */
            $printer = ImpressoraFactory::criar($config);

            /* =========================
               CABEÇALHO EMPRESA
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text($config['nome_empresa'] . "\n");
            $printer->setEmphasis(false);

            $printer->text($config['endereco'] . "\n");

            $printer->text(trim(
                $config['rua_avenida'] . ', ' .
                $config['bairro'] . ', ' .
                $config['cidade'] . ', ' .
                $config['provincia']
            ) . "\n");

            $printer->text("Tel: {$config['telefone']}\n");
            $printer->text("Email: {$config['email_empresa']}\n");
            $printer->text("NUIT: {$config['nuit_empresa']}\n");

            $printer->text("------------------------\n");

            /* =========================
               INFO CAIXA
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text("RESUMO DE CAIXA\n");
            $printer->text("Abertura ID: {$abertura_id}\n");
            $printer->text("Data Abertura: {$abertura['data_abertura']}\n");
            $printer->text("Fecho: " . date('Y-m-d H:i:s') . "\n");

            $printer->text("------------------------\n");

            /* =========================
               RESUMO FINANCEIRO
            ========================= */
            $printer->text("Vendas: " . ($resumo['total_vendas'] ?? 0) . "\n");
            $printer->text("Faturado: MT " . number_format($resumo['total_faturado'] ?? 0, 2) . "\n");
            $printer->text("Pago: MT " . number_format($resumo['total_pago'] ?? 0, 2) . "\n");
            $printer->text("Troco: MT " . number_format($resumo['total_troco'] ?? 0, 2) . "\n");

            $printer->text("------------------------\n");

            /* =========================
               OPERADOR
            ========================= */
            $printer->text("Operador ID: {$usuario_id}\n");

            $printer->text("------------------------\n");

            /* =========================
               FINAL
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("FECHO DE CAIXA\n");
            $printer->text("Obrigado!\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return true;

        } catch (Throwable $e) {

            error_log("ERRO RESUMO CAIXA: " . $e->getMessage());
            return false;
        }
    }
}