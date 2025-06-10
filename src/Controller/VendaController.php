<?php
namespace Controller;

use PDO;
use PDOException;
use Dompdf\Dompdf;

class VendaController
{
    private PDO $pdo;
    public string $mensagem = '';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
    }

    public function processarRequisicao(): void
    {
        if (isset($_POST['finalizar_venda'])) {
            $this->finalizarVenda();
        } elseif (isset($_POST['adicionar'])) {
            $this->adicionarProduto();
        } elseif (isset($_POST['remover_produto'])) {
            $this->removerProduto($_POST['remover_produto']);
        }
    }

    public function getCarrinho(): array
    {
        return $_SESSION['carrinho'] ?? [];
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

    private function finalizarVenda(): void
    {
        $valorPago = (float) ($_POST['valor_pago'] ?? 0);
        $carrinho = $_SESSION['carrinho'] ?? [];

        if (empty($carrinho)) {
            $this->mensagem = 'Carrinho vazio.';
            return;
        }

        $total = 0;
        foreach ($carrinho as $item) {
            $total += $item['preco'] * $item['quantidade'];
        }

        if ($valorPago < $total) {
            $this->mensagem = 'Valor pago insuficiente.';
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $stmtVenda = $this->pdo->prepare("INSERT INTO vendas (usuario_id, total, valor_pago, troco, data_hora) VALUES (:usuario_id, :total, :valor_pago, :troco, NOW())");
            $stmtVenda->execute([
                ':usuario_id' => $_SESSION['usuario_id'],
                ':total' => $total,
                ':valor_pago' => $valorPago,
                ':troco' => $valorPago - $total
            ]);

            $vendaId = $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("INSERT INTO produtos_vendidos (venda_id, codigo_barra, quantidade, preco_unitario) VALUES (:venda_id, :codigo_barra, :quantidade, :preco)");

            foreach ($carrinho as $codigo => $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':codigo_barra' => $codigo,
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco']
                ]);

                $stmtEstoque = $this->pdo->prepare("UPDATE produtos SET estoque = estoque - :quantidade WHERE codigo_barra = :codigo");
                $stmtEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':codigo' => $codigo
                ]);
            }

            $this->pdo->commit();

            $_SESSION['numero_recibo'] = $vendaId;
            $_SESSION['carrinho'] = [];

            // Retorno vazio com sucesso para não aparecer JSON
            http_response_code(204); // 204 = No Content
            exit;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            echo json_encode([
                'success' => false,
                'mensagem' => 'Erro: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function gerarReciboPdf(int $vendaId): void
    {
        $stmt = $this->pdo->prepare("SELECT p.nome, pv.quantidade, pv.preco_unitario FROM produtos_vendidos pv JOIN produtos p ON pv.codigo_barra = p.codigo_barra WHERE pv.venda_id = ?");
        $stmt->execute([$vendaId]);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../View/recibo.view.php'; // seu template do recibo em HTML
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        $dompdf->stream("recibo_venda_{$vendaId}.pdf", ["Attachment" => true]);
    }
}
