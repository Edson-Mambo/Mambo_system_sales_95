<?php
namespace Controller;

use PDO;
use PDOException;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

require_once __DIR__ . '/../../vendor/autoload.php';

class VendaController
{
    private PDO $pdo;
    public string $mensagem = '';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
    }

    public function processarRequisicao(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    if (isset($_POST['finalizar_venda'])) {
        $this->responderFinalizacaoAjax();
        return;
    }

    if (isset($_POST['adicionar'])) {
        $this->adicionarProduto();
        $this->redirecionarComMensagem();
    }

    if (isset($_POST['remover_produto'])) {
        $this->removerProduto($_POST['remover_produto']);
        $this->redirecionarComMensagem();
    }
}

    private function redirecionarComMensagem(): void
    {
        $_SESSION['mensagem'] = $this->mensagem;
        header('Location: venda.php');
        exit;
    }

    private function adicionarProduto(): void
    {
        $busca = trim($_POST['busca_produto'] ?? '');
        $quantidade = (int) ($_POST['quantidade'] ?? 1);

        if ($busca === '' || $quantidade <= 0) {
            $this->mensagem = 'Dados inválidos.';
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = :busca OR nome LIKE :nome LIMIT 1");
        $stmt->execute([
            ':busca' => $busca,
            ':nome' => "%$busca%"
        ]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            $codigo = $produto['codigo_barra'];
            if (isset($_SESSION['carrinho'][$codigo])) {
                $_SESSION['carrinho'][$codigo]['quantidade'] += $quantidade;
            } else {
                $_SESSION['carrinho'][$codigo] = [
                    'nome' => $produto['nome'],
                    'preco' => (float)$produto['preco'],
                    'quantidade' => $quantidade
                ];
            }
            $this->mensagem = 'Produto adicionado.';
        } else {
            $this->mensagem = 'Produto não encontrado.';
        }
    }

    private function removerProduto(string $codigo): void
    {
        if (isset($_SESSION['carrinho'][$codigo])) {
            unset($_SESSION['carrinho'][$codigo]);
            $this->mensagem = 'Produto removido.';
        }
    }

private function responderFinalizacaoAjax(): void
{
    header('Content-Type: application/json; charset=utf-8');

    $valorPago = (float) ($_POST['valor_pago'] ?? 0);
    $metodoPagamento = $_POST['metodo_pagamento'] ?? 'dinheiro';

    $resultado = $this->finalizarVenda($valorPago, $metodoPagamento);

    echo json_encode($resultado);
    exit;
}


    public function finalizarVenda(float $valorPago, string $metodoPagamento): array
{
    $carrinho = $_SESSION['carrinho'] ?? [];

    if (empty($carrinho)) {
        return ['success' => false, 'mensagem' => 'Carrinho vazio.'];
    }

    $total = array_reduce($carrinho, function ($soma, $item) {
        return $soma + ($item['preco'] * $item['quantidade']);
    }, 0);

    if ($valorPago < $total) {
        return ['success' => false, 'mensagem' => 'Valor pago insuficiente.'];
    }

    try {
        $this->pdo->beginTransaction();

        $stmtVenda = $this->pdo->prepare("
            INSERT INTO vendas 
            (usuario_id, total, valor_pago, troco, metodo_pagamento, cliente_id, data_venda)
            VALUES 
            (:usuario_id, :total, :valor_pago, :troco, :metodo_pagamento, :cliente_id, NOW())
        ");

        $stmtVenda->execute([
            ':usuario_id' => $_SESSION['usuario_id'] ?? null,
            ':total' => $total,
            ':valor_pago' => $valorPago,
            ':troco' => $valorPago - $total,
            ':metodo_pagamento' => $metodoPagamento,
            ':cliente_id' => $_SESSION['cliente_id'] ?? null
        ]);

        $vendaId = $this->pdo->lastInsertId();

        foreach ($carrinho as $codigo => $item) {

            $stmtProduto = $this->pdo->prepare("
                SELECT id FROM produtos WHERE codigo_barra = :codigo LIMIT 1
            ");

            $stmtProduto->execute([':codigo' => $codigo]);
            $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new \Exception("Produto não encontrado: $codigo");
            }

            $stmtItem = $this->pdo->prepare("
                INSERT INTO produtos_vendidos 
                (venda_id, produto_id, quantidade, preco_unitario)
                VALUES (:venda_id, :produto_id, :quantidade, :preco)
            ");

            $stmtItem->execute([
                ':venda_id' => $vendaId,
                ':produto_id' => $produto['id'],
                ':quantidade' => $item['quantidade'],
                ':preco' => $item['preco']
            ]);

            $this->pdo->prepare("
                UPDATE produtos 
                SET estoque = estoque - :qtd 
                WHERE id = :id
            ")->execute([
                ':qtd' => $item['quantidade'],
                ':id' => $produto['id']
            ]);
        }

        $this->pdo->commit();

        // limpar sessão
        $_SESSION['numero_recibo'] = $vendaId;
        $_SESSION['carrinho'] = [];
        unset($_SESSION['cliente_id']);

        /**
         * 🖨️ IMPRESSÃO SEGURA (NUNCA PODE QUEBRAR A VENDA)
         * Funciona com qualquer impressora instalada no Windows
         */
        try {

            $printerName = $_ENV['PRINTER_NAME'] ?? 'GP-C80 Series';

            if (class_exists(\Mike42\Escpos\PrintConnectors\WindowsPrintConnector::class)) {

                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerName);
                $printer = new \Mike42\Escpos\Printer($connector);

                $nomeCaixa = $_SESSION['usuario_nome'] ?? 'Desconhecido';

                $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
                $printer->text("Mambo System Sales\n");
                $printer->text("--------------------------------\n");

                $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
                $printer->text("Recibo: $vendaId\n");
                $printer->text("Caixa: $nomeCaixa\n");
                $printer->text("Data: " . date('d/m/Y H:i:s') . "\n");
                $printer->text("--------------------------------\n");

                foreach ($carrinho as $item) {
                    $printer->text(sprintf(
                        "%-15.15s %3d x %7.2f = %7.2f\n",
                        $item['nome'],
                        $item['quantidade'],
                        $item['preco'],
                        $item['quantidade'] * $item['preco']
                    ));
                }

                $printer->text("--------------------------------\n");
                $printer->text("Total: MT " . number_format($total, 2, ',', '.') . "\n");
                $printer->text("Pago: MT " . number_format($valorPago, 2, ',', '.') . "\n");
                $printer->text("Troco: MT " . number_format($valorPago - $total, 2, ',', '.') . "\n");
                $printer->text("Metodo: $metodoPagamento\n");

                $printer->cut();
                $printer->close();
            }

        } catch (\Throwable $e) {
            // 🔴 NUNCA quebra venda
            error_log("Erro impressão: " . $e->getMessage());
        }

        return [
            'success' => true,
            'venda_id' => $vendaId
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
