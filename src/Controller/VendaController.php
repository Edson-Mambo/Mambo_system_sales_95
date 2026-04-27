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

    /* =========================
       GERAR RECIBO POR CAIXA
    ========================= */
    private function gerarNumeroRecibo(int $abertura_id): int
    {
        $stmt = $this->pdo->prepare("
            SELECT ultimo_numero 
            FROM caixa_recibos 
            WHERE abertura_id = ? 
            FOR UPDATE
        ");
        $stmt->execute([$abertura_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $numero = (int)$row['ultimo_numero'] + 1;

            $stmt = $this->pdo->prepare("
                UPDATE caixa_recibos 
                SET ultimo_numero = ? 
                WHERE abertura_id = ?
            ");
            $stmt->execute([$numero, $abertura_id]);

        } else {

            $numero = 1;

            $stmt = $this->pdo->prepare("
                INSERT INTO caixa_recibos (abertura_id, ultimo_numero)
                VALUES (?, ?)
            ");
            $stmt->execute([$abertura_id, $numero]);
        }

        return $numero;
    }

    public function processarRequisicao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (isset($_POST['finalizar_venda'])) {

            header('Content-Type: application/json');

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

        if (isset($_POST['adicionar'])) {
            $this->adicionarProduto();
            $this->redirect();
        }

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
            $aberturaId = $_SESSION['abertura_id'] ?? null;

            /* =========================
               RECIBO POR CAIXA
            ========================= */
            $numeroRecibo = $this->gerarNumeroRecibo($aberturaId);

            /* =========================
               INSERIR VENDA
            ========================= */
            $stmt = $this->pdo->prepare("
                INSERT INTO vendas
                (usuario_id, cliente_id, total, valor_pago, troco, metodo_pagamento, data_venda, numero_recibo, abertura_id)
                VALUES (?,?,?,?,?,?,NOW(),?,?)
            ");

            $stmt->execute([
                $usuarioId,
                $clienteId,
                $total,
                $valorPago,
                $valorPago - $total,
                $metodo,
                $numeroRecibo,
                $aberturaId
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
            $_SESSION['numero_recibo'] = $numeroRecibo;
            $_SESSION['print_last_sale'] = $vendaId;

            return [
                'success' => true,
                'venda_id' => $vendaId,
                'numero_recibo' => $numeroRecibo,
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
}