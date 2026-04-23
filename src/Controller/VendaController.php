<?php

namespace Controller;

use PDO;
use Impressao\ImpressoraFactory;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../impressao/ImpressoraFactory.php';

class VendaController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['carrinho'] ??= [];
    }

    public function processarRequisicao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        /* =========================
           FINALIZAR VENDA
        ========================= */
        if (isset($_POST['finalizar_venda'])) {

            header('Content-Type: application/json');

            // 🔥 GARANTE cliente vindo do frontend
            if (isset($_POST['cliente_id'])) {
                $_SESSION['cliente_id'] = (int)$_POST['cliente_id'];
            }

            echo json_encode(
                $this->finalizarVenda(
                    (float)($_POST['valor_pago'] ?? 0),
                    $_POST['metodo_pagamento'] ?? 'dinheiro'
                )
            );
            exit;
        }

        /* =========================
           ADICIONAR PRODUTO
        ========================= */
        if (isset($_POST['adicionar'])) {
            $this->adicionarProduto();
            $this->redirect();
        }

        /* =========================
           REMOVER PRODUTO
        ========================= */
        if (isset($_POST['remover_produto'])) {
            unset($_SESSION['carrinho'][$_POST['remover_produto']]);
            $this->redirect();
        }
    }

    private function redirect(): void
    {
        $_SESSION['mensagem'] = "Operação realizada";
        header("Location: venda.php");
        exit;
    }

    private function adicionarProduto(): void
    {
        $busca = trim($_POST['busca_produto'] ?? '');
        $qtd = max(1, (int)($_POST['quantidade'] ?? 1));

        if (!$busca) return;

        $stmt = $this->pdo->prepare("
            SELECT * FROM produtos
            WHERE codigo_barra = :b OR nome LIKE :n
            LIMIT 1
        ");

        $stmt->execute([
            ':b' => $busca,
            ':n' => "%$busca%"
        ]);

        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$p) return;

        $codigo = $p['codigo_barra'];

        $_SESSION['carrinho'][$codigo] = [
            'produto_id' => $p['id'],
            'nome' => $p['nome'],
            'preco' => (float)$p['preco'],
            'quantidade' => ($_SESSION['carrinho'][$codigo]['quantidade'] ?? 0) + $qtd
        ];
    }

    /* =========================
       FINALIZAR VENDA
    ========================= */
    public function finalizarVenda(float $valorPago, string $metodo): array
    {
        $carrinho = $_SESSION['carrinho'];

        if (!$carrinho) {
            return ['success' => false, 'mensagem' => 'Carrinho vazio'];
        }

        $total = 0;

        foreach ($carrinho as $item) {
            $total += $item['preco'] * $item['quantidade'];
        }

        if ($valorPago < $total) {
            return ['success' => false, 'mensagem' => 'Valor insuficiente'];
        }

        try {
            $this->pdo->beginTransaction();

            $clienteId = $_SESSION['cliente_id'] ?? null;
            $usuarioId = $_SESSION['usuario_id'] ?? null;

            /* =========================
               INSERIR VENDA (CORRIGIDO)
            ========================= */
            $stmt = $this->pdo->prepare("
                INSERT INTO vendas
                (usuario_id, cliente_id, total, valor_pago, troco, metodo_pagamento, data_venda)
                VALUES (?,?,?,?,?,?,NOW())
            ");

            $stmt->execute([
                $usuarioId,
                $clienteId,
                $total,
                $valorPago,
                $valorPago - $total,
                $metodo
            ]);

            $vendaId = $this->pdo->lastInsertId();

            /* =========================
               ITENS
            ========================= */
            foreach ($carrinho as $item) {

                $this->pdo->prepare("
                    INSERT INTO produtos_vendidos
                    (venda_id, produto_id, quantidade, preco_unitario)
                    VALUES (?,?,?,?)
                ")->execute([
                    $vendaId,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['preco']
                ]);

                $this->pdo->prepare("
                    UPDATE produtos
                    SET estoque = estoque - ?
                    WHERE id = ?
                ")->execute([
                    $item['quantidade'],
                    $item['produto_id']
                ]);
            }

            $this->pdo->commit();

            $_SESSION['carrinho'] = [];
            $_SESSION['numero_recibo'] = $vendaId;

            $this->imprimir($vendaId, $carrinho, $total, $valorPago, $metodo);

            return [
                'success' => true,
                'venda_id' => $vendaId,
                'pdf_url' => "gerar_recibo.php?venda_id=$vendaId"
            ];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            return [
                'success' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /* =========================
       IMPRESSÃO
    ========================= */
    private function imprimir($vendaId, $carrinho, $total, $valorPago, $metodo): void
    {
        try {

            $config = $this->pdo->query("
                SELECT * FROM configuracoes_empresa LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);

            $printer = ImpressoraFactory::criar($config);

            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text(($config['nome_empresa'] ?? 'Empresa') . "\n");
            $printer->text("RECIBO #$vendaId\n");
            $printer->text("------------------------\n");

            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);

            foreach ($carrinho as $item) {
                $printer->text(
                    mb_strimwidth($item['nome'], 0, 20, '') .
                    " x{$item['quantidade']}\n"
                );
            }

            $printer->text("------------------------\n");
            $printer->text("TOTAL: MT " . number_format($total,2,',','.') . "\n");
            $printer->text("PAGO: MT " . number_format($valorPago,2,',','.') . "\n");
            $printer->text("TROCO: MT " . number_format($valorPago - $total,2,',','.') . "\n");

            $printer->feed(2);
            $printer->cut();
            $printer->close();

        } catch (\Throwable $e) {
            error_log("ERRO IMPRESSÃO: " . $e->getMessage());
        }
    }
}