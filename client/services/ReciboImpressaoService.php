<?php

require_once __DIR__ . '/../pos/impressao/ImpressoraFactory.php';

use Mike42\Escpos\Printer;

class ReciboImpressaoService
{
    public static function imprimir($venda_id, $pdoLocal, $config)
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
               VENDA (LOCAL SQLITE)
            ========================= */
            $stmt = $pdoLocal->prepare("
                SELECT v.*, u.nome AS operador
                FROM vendas v
                LEFT JOIN usuarios u ON u.id = v.usuario_id
                WHERE v.id = ?
            ");

            $stmt->execute([$venda_id]);
            $venda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venda) {
                throw new Exception("Venda não encontrada");
            }

            /* =========================
               ITENS (LOCAL SQLITE)
            ========================= */
            $stmt = $pdoLocal->prepare("
                SELECT vi.*, p.nome
                FROM venda_itens vi
                JOIN produtos p ON p.id = vi.produto_id
                WHERE vi.venda_id = ?
            ");

            $stmt->execute([$venda_id]);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /* =========================
               RECIBO
            ========================= */
            $numeroRecibo = $venda['id'];

            /* =========================
               CLIENTE
            ========================= */
            $cliente = 'Cliente Geral';

            if (!empty($venda['cliente_id'])) {
                $stmtCli = $pdoLocal->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmtCli->execute([$venda['cliente_id']]);
                $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

                if ($cli) {
                    $cliente = $cli['nome'];
                }
            }

            /* =========================
               IMPRESSORA
            ========================= */
            $printer = ImpressoraFactory::criar($config);

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text($config['nome_empresa'] . "\n");
            $printer->setEmphasis(false);

            $printer->text($config['endereco'] . "\n");
            $printer->text($config['telefone'] . "\n");
            $printer->text($config['email_empresa'] . "\n");
            $printer->text($config['nuit_empresa'] . "\n");
            $printer->text("------------------------\n");

            /* =========================
               INFO VENDA
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text("Recibo: #{$numeroRecibo}\n");
            $printer->text("Data: {$venda['data']}\n");
            $printer->text("Operador: {$venda['operador']}\n");
            $printer->text("Cliente: {$cliente}\n");
            $printer->text("Pagamento: {$venda['metodo_pagamento']}\n");
            $printer->text("------------------------\n");

            /* =========================
               ITENS
            ========================= */
            $total = 0;

            foreach ($itens as $i) {

                $qtd = (float)$i['quantidade'];
                $preco = (float)$i['preco'];
                $sub = $qtd * $preco;

                $total += $sub;

                $nome = mb_strimwidth($i['nome'], 0, 20, '...');

                $printer->text($nome . "\n");
                $printer->text($qtd . " x " . number_format($preco, 2) . " = " . number_format($sub, 2) . "\n");
            }

            /* =========================
               TOTAL
            ========================= */
            $printer->text("------------------------\n");
            $printer->text("TOTAL: MT " . number_format($total, 2) . "\n");

            $printer->text("\nObrigado!\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return true;

        } catch (Throwable $e) {

            error_log("ERRO RECIBO: " . $e->getMessage());
            return false;
        }
    }
}