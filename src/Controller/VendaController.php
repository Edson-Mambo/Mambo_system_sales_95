<?php
namespace Controller;

use PDO;
use PDOException;
use Dompdf\Dompdf;
use Dompdf\Options;

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
            $this->finalizarVenda();
        } elseif (isset($_POST['adicionar'])) {
            $this->adicionarProduto();
            $this->redirecionarComMensagem();
        } elseif (isset($_POST['remover_produto'])) {
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

    private function finalizarVenda(): void
    {
        header('Content-Type: application/json');

        $valorPago = (float) ($_POST['valor_pago'] ?? 0);
        $carrinho = $_SESSION['carrinho'] ?? [];
        $metodoPagamento = $_POST['metodo_pagamento'] ?? 'dinheiro';

        if (empty($carrinho)) {
            echo json_encode(['success' => false, 'mensagem' => 'Carrinho vazio.']);
            return;
        }

        $total = array_reduce($carrinho, fn($soma, $item) => $soma + ($item['preco'] * $item['quantidade']), 0);

        if ($valorPago < $total) {
            echo json_encode(['success' => false, 'mensagem' => 'Valor pago insuficiente.']);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $stmtVenda = $this->pdo->prepare("INSERT INTO vendas (usuario_id, total, valor_pago, troco, metodo_pagamento, data_venda) VALUES (:usuario_id, :total, :valor_pago, :troco, :metodo_pagamento, NOW())");
            $stmtVenda->execute([
                ':usuario_id' => $_SESSION['usuario_id'] ?? null,
                ':total' => $total,
                ':valor_pago' => $valorPago,
                ':troco' => $valorPago - $total,
                ':metodo_pagamento' => $metodoPagamento
            ]);

            $vendaId = $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("INSERT INTO produtos_vendidos (venda_id, produto_id, quantidade, preco_unitario) VALUES (:venda_id, (SELECT id FROM produtos WHERE codigo_barra = :codigo_barra), :quantidade, :preco)");

            foreach ($carrinho as $codigo => $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':codigo_barra' => $codigo,
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco']
                ]);

                $stmtEstoque = $this->pdo->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE codigo_barra = :codigo");
                $stmtEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':codigo' => $codigo
                ]);
            }

            $this->pdo->commit();

            $_SESSION['numero_recibo'] = $vendaId;
            $_SESSION['carrinho'] = [];

            $pdfPath = $this->gerarPdfParaArquivo($vendaId);

            echo json_encode([
                'success' => true,
                'venda_id' => $vendaId,
                'pdfPath' => $pdfPath
            ]);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            echo json_encode([
                'success' => false,
                'mensagem' => 'Erro ao salvar venda: ' . $e->getMessage()
            ]);
        }
    }

    private function gerarPdfParaArquivo(int $vendaId): string
{
    // Pega dados da venda
    $stmtVenda = $this->pdo->prepare("SELECT v.*, u.nome AS nome_usuario FROM vendas v JOIN usuarios u ON v.usuario_id = u.id WHERE v.id = ?");
    $stmtVenda->execute([$vendaId]);
    $venda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

    // Pega produtos vendidos nessa venda
    $stmtProdutos = $this->pdo->prepare("
        SELECT p.nome, pv.quantidade, pv.preco_unitario 
        FROM produtos_vendidos pv 
        JOIN produtos p ON pv.produto_id = p.id 
        WHERE pv.venda_id = ?
    ");
    $stmtProdutos->execute([$vendaId]);
    $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

    // Monta o HTML do recibo com estilos inline para Dompdf
    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 700px; margin: auto; padding: 20px; border: 1px solid #333;">
        <h1 style="text-align:center; color: #2c3e50;">Mambo System Sales</h1>
        <p style="text-align:center; font-size: 0.9em; margin-top: -10px;">Rua da Patria Nº 510, Aeroporto "A" - Maputo, Moçambique</p>
        <hr style="border: 1px solid #ccc; margin: 15px 0;">
        
        <table style="width: 100%; font-size: 0.9em; margin-bottom: 15px;">
            <tr>
                <td><strong>Recibo Nº:</strong> ' . htmlspecialchars($vendaId) . '</td>
                <td style="text-align:right;"><strong>Data:</strong> ' . date('d/m/Y H:i:s', strtotime($venda['data_venda'])) . '</td>
            </tr>
            <tr>
                <td><strong>Operador:</strong> ' . htmlspecialchars($venda['nome_usuario']) . '</td>
                <td style="text-align:right;"><strong>Método:</strong> ' . ucfirst(htmlspecialchars($venda['metodo_pagamento'])) . '</td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
            <thead>
                <tr style="background-color: #34495e; color: white;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align:left;">Produto</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align:center;">Qtd</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align:right;">Preço Unit.</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($produtos as $item) {
        $subtotal = $item['quantidade'] * $item['preco_unitario'];
        $html .= '
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($item['nome']) . '</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align:center;">' . $item['quantidade'] . '</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align:right;">MT ' . number_format($item['preco_unitario'], 2, ',', '.') . '</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align:right;">MT ' . number_format($subtotal, 2, ',', '.') . '</td>
            </tr>';
    }

    $html .= '
            </tbody>
        </table>

        <table style="width: 100%; font-size: 0.9em; margin-top: 10px;">
            <tr>
                <td style="text-align:right;"><strong>Total:</strong></td>
                <td style="width: 120px; text-align:right;">MT ' . number_format($venda['total'], 2, ',', '.') . '</td>
            </tr>
            <tr>
                <td style="text-align:right;"><strong>Valor Pago:</strong></td>
                <td style="text-align:right;">MT ' . number_format($venda['valor_pago'], 2, ',', '.') . '</td>
            </tr>
            <tr>
                <td style="text-align:right;"><strong>Troco:</strong></td>
                <td style="text-align:right;">MT ' . number_format($venda['valor_pago'] - $venda['total'], 2, ',', '.') . '</td>
            </tr>
        </table>

        <p style="text-align:center; margin-top: 30px; font-size: 0.9em; font-style: italic; color: #555;">
            Muito obrigado pela preferência!<br />
            Volte sempre!
        </p>
    </div>
    ';

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->render();

    $pdfPath = __DIR__ . "/../../public/recibos/recibo_venda_{$vendaId}.pdf";
    file_put_contents($pdfPath, $dompdf->output());

    return "recibos/recibo_venda_{$vendaId}.pdf";
}

}
