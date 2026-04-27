<?php

require_once __DIR__ . '/../impressao/ImpressoraFactory.php';

use Mike42\Escpos\Printer;

class ReciboImpressaoService
{
    public static function imprimir($venda_id, $pdo, $config)
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
               VENDA + CLIENTE + OPERADOR
            ========================= */
            $stmt = $pdo->prepare("
                SELECT v.*,
                       u.nome AS operador,
                       c.nome AS cliente_nome,
                       c.apelido AS cliente_apelido,
                       c.telefone AS cliente_telefone,
                       c.email AS cliente_email,
                       c.morada AS cliente_morada,
                       c.nuit AS cliente_nuit
                FROM vendas v
                LEFT JOIN usuarios u ON u.id = v.usuario_id
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.id = ?
            ");

            $stmt->execute([$venda_id]);
            $venda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venda) {
                throw new Exception("Venda não encontrada");
            }

            /* =========================
               ITENS
            ========================= */
            $stmt = $pdo->prepare("
                SELECT pv.*, p.nome
                FROM produtos_vendidos pv
                JOIN produtos p ON p.id = pv.produto_id
                WHERE pv.venda_id = ?
            ");

            $stmt->execute([$venda_id]);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /* =========================
               RECIBO CORRETO (NÃO DEPENDE DO ID)
            ========================= */
            $numeroRecibo = $venda['numero_recibo'] ?? $venda_id;

            /* =========================
               CLIENTE
            ========================= */
            $cliente = 'Cliente Geral';
            $clienteDetalhes = '';

            if (!empty($venda['cliente_id'])) {

                $cliente = trim(
                    ($venda['cliente_nome'] ?? '') . ' ' .
                    ($venda['cliente_apelido'] ?? '')
                );

                if (!empty($venda['cliente_telefone'])) {
                    $clienteDetalhes .= "Tel: {$venda['cliente_telefone']}\n";
                }

                if (!empty($venda['cliente_email'])) {
                    $clienteDetalhes .= "Email: {$venda['cliente_email']}\n";
                }

                if (!empty($venda['cliente_morada'])) {
                    $clienteDetalhes .= "Morada: {$venda['cliente_morada']}\n";
                }

                if (!empty($venda['cliente_nuit'])) {
                    $clienteDetalhes .= "NUIT: {$venda['cliente_nuit']}\n";
                }
            }

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

            $printer->text(
                trim(
                    $config['rua_avenida'] . ', ' .
                    $config['bairro'] . ', ' .
                    $config['cidade'] . ', ' .
                    $config['provincia']
                ) . "\n"
            );

            $printer->text("Tel: {$config['telefone']}\n");
            $printer->text("Email: {$config['email_empresa']}\n");
            $printer->text("NUIT: {$config['nuit_empresa']}\n");

            $printer->text("------------------------\n");

            /* =========================
               INFO VENDA
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text("Recibo: #{$numeroRecibo}\n");
            $printer->text("Data: {$venda['data_venda']}\n");
            $printer->text("Operador: {$venda['operador']}\n");
            $printer->text("Cliente: {$cliente}\n");

            if (!empty($clienteDetalhes)) {
                $printer->text("------------------------\n");
                $printer->text("DADOS DO CLIENTE\n");
                $printer->text("------------------------\n");
                $printer->text($clienteDetalhes);
                $printer->text("------------------------\n");
            }

            $printer->text("Pagamento: {$venda['metodo_pagamento']}\n");
            $printer->text("------------------------\n");

            /* =========================
               ITENS
            ========================= */
            $total = 0;

            foreach ($itens as $i) {

                $qtd = (int)$i['quantidade'];
                $preco = (float)$i['preco_unitario'];
                $sub = $qtd * $preco;

                $total += $sub;

                $nome = mb_strimwidth($i['nome'], 0, 20, '...');

                $printer->text($nome . "\n");
                $printer->text(
                    $qtd . " x " .
                    number_format($preco, 2) .
                    " = " .
                    number_format($sub, 2) . "\n"
                );
            }

            /* =========================
               TOTAL / PAGAMENTO / TROCO
            ========================= */
            $valorPago = (float)($venda['valor_pago'] ?? 0);
            $troco = (float)($venda['troco'] ?? ($valorPago - $total));

            $printer->text("------------------------\n");
            $printer->text("TOTAL: MT " . number_format($total, 2) . "\n");
            $printer->text("PAGO: MT " . number_format($valorPago, 2) . "\n");
            $printer->text("TROCO: MT " . number_format($troco, 2) . "\n");

            /* =========================
               RODAPÉ
            ========================= */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("\nObrigado pela preferência!\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return true;

        } catch (Throwable $e) {

            error_log("ERRO IMPRESSÃO: " . $e->getMessage());
            return false;
        }
    }
}