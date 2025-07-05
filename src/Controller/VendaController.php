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
            $this->responderFinalizacaoAjax();
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

    private function responderFinalizacaoAjax(): void
    {
        header('Content-Type: application/json');

        $valorPago = (float) ($_POST['valor_pago'] ?? 0);
        $metodoPagamento = $_POST['metodo_pagamento'] ?? 'dinheiro';

        $resposta = $this->finalizarVenda($valorPago, $metodoPagamento);

        echo json_encode($resposta);
    }

    public function finalizarVenda(float $valorPago, string $metodoPagamento): array
    {
        $carrinho = $_SESSION['carrinho'] ?? [];

        if (empty($carrinho)) {
            return ['success' => false, 'mensagem' => 'Carrinho vazio.'];
        }

        $total = array_reduce($carrinho, fn($soma, $item) => $soma + ($item['preco'] * $item['quantidade']), 0);

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

            $stmtItem = $this->pdo->prepare("
                INSERT INTO produtos_vendidos 
                (venda_id, produto_id, quantidade, preco_unitario) 
                VALUES 
                (:venda_id, (SELECT id FROM produtos WHERE codigo_barra = :codigo_barra), :quantidade, :preco)
            ");

            foreach ($carrinho as $codigo => $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':codigo_barra' => $codigo,
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco']
                ]);

                $stmtEstoque = $this->pdo->prepare("
                    UPDATE produtos 
                    SET quantidade = quantidade - :quantidade 
                    WHERE codigo_barra = :codigo
                ");
                $stmtEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':codigo' => $codigo
                ]);
            }

            $this->pdo->commit();

            $_SESSION['numero_recibo'] = $vendaId;
            $_SESSION['carrinho'] = [];
            unset($_SESSION['cliente_id']); // Limpa o cliente após venda

            $pdfPath = $this->gerarPdfParaArquivo($vendaId);

            if (!$pdfPath) {
                return ['success' => false, 'mensagem' => 'Erro ao gerar o recibo PDF. Verifique os logs.'];
            }

            return ['success' => true, 'venda_id' => $vendaId, 'pdfPath' => $pdfPath];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'mensagem' => 'Erro ao salvar venda: ' . $e->getMessage()];
        }
    }

    private function gerarPdfParaArquivo(int $vendaId): ?string
    {
        try {
            $stmtVenda = $this->pdo->prepare("
                SELECT v.*, u.nome AS nome_usuario, c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email, c.morada AS cliente_morada
                FROM vendas v 
                JOIN usuarios u ON v.usuario_id = u.id 
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.id = ?
            ");
            $stmtVenda->execute([$vendaId]);
            $venda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

            if (!$venda) {
                error_log("Venda não encontrada para ID: $vendaId");
                return null;
            }

            $stmtProdutos = $this->pdo->prepare("
                SELECT p.nome, pv.quantidade, pv.preco_unitario 
                FROM produtos_vendidos pv 
                JOIN produtos p ON pv.produto_id = p.id 
                WHERE pv.venda_id = ?
            ");
            $stmtProdutos->execute([$vendaId]);
            $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

            if (empty($produtos)) {
                error_log("Nenhum produto encontrado para venda ID: $vendaId");
                return null;
            }

            
            

            ob_start();
            ?>

            <!DOCTYPE html>
            <html lang="pt">
            <head>
            <meta charset="UTF-8" />
            <title>Recibo Venda #<?= htmlspecialchars($vendaId) ?></title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');

                body {
                font-family: 'Montserrat', sans-serif;
                font-size: 13px;
                margin: 30px;
                color: #2c3e50;
                background: #fff;
                }

                header {
            display: flex;
            justify-content: center;  /* Centraliza os blocos no container */
            align-items: flex-start;
            border-bottom: 2px solid #2980b9;
            padding-bottom: 20px;
            margin-bottom: 30px;
            gap: 50px;  /* Espaço entre as divs */
            }



            .empresa {
            margin-top: 5px;
            font-size: 20px;
            font-weight: 700;
            color: #2980b9;
            }

            h3 {
            margin-bottom: 5px;
            font-size: 15px;
            color: #2980b9;
            text-transform: uppercase;
            }

            .dados-recibo {
            margin-top: 20px;
            text-align: center;
            }

            .dados-recibo p {
            margin: 2px 0;
            }


                section.produtos {
                margin-bottom: 30px;
                }

                table {
                width: 100%;
                border-collapse: collapse;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                border-radius: 6px;
                overflow: hidden;
                }

                thead {
                background-color: #2980b9;
                color: white;
                }

                thead th {
                padding: 12px;
                font-weight: 700;
                text-transform: uppercase;
                font-size: 13px;
                text-align: left;
                }

                tbody tr {
                border-bottom: 1px solid #ddd;
                transition: background-color 0.3s ease;
                }

                tbody tr:hover {
                background-color: #f1f8ff;
                }

                tbody td {
                padding: 12px 15px;
                font-size: 13px;
                vertical-align: middle;
                }

                tbody td.qty, tbody td.price, tbody td.subtotal {
                text-align: right;
                font-feature-settings: "tnum";
                font-variant-numeric: tabular-nums;
                }

                .totais {
                max-width: 350px;
                margin-left: auto;
                margin-top: 20px;
                border-top: 2px solid #2980b9;
                padding-top: 15px;
                font-size: 14px;
                color: #2c3e50;
                }

                .totais div {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-weight: 600;
                }

                .totais div span.valor {
                color: #2980b9;
                }

                footer {
                margin-top: 40px;
                border-top: 1px solid #ddd;
                padding-top: 15px;
                text-align: center;
                font-style: italic;
                font-size: 12px;
                color: #7f8c8d;
                }
            </style>
            </head>

            <body>

            <header>
            <!-- DIV esquerda: logo + empresa -->
            <div class="dados-recibo">
                   <!-- <img src="https://i.imgur.com/dq4wTyf.png" alt="Logo Mambo System">-->
                <div class="empresa">Mambo System Sales</div>
                
                    <p><strong>Recibo Nº:</strong> <?= htmlspecialchars($vendaId) ?></p>
                    <p><strong>Data:</strong> <?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?></p>
                    <p><strong>Operador:</strong> <?= htmlspecialchars($venda['nome_usuario']) ?></p>
                    <p><strong>Tel:</strong> +258 84 854 1787</p>
                    <p><strong>Local:</strong> Maputo, Moçambique</p>
                
                <br>
                    <!-- DIV direita: dados do cliente -->

                    <h3>Dados do Cliente</h3>
                    <p><strong>Nome:</strong> <?= htmlspecialchars($venda['cliente_nome'] ?? '-') ?></p>
                    <p><strong>Telefone:</strong> <?= htmlspecialchars($venda['cliente_telefone'] ?? '-') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($venda['cliente_email'] ?? '-') ?></p>
                    <p><strong>Morada:</strong> <?= htmlspecialchars($venda['cliente_morada'] ?? '-') ?></p>
                </div>
            </div>
            </header>

            <section class="produtos">
            <table>
                <thead>
                <tr>
                    <th>Produto</th>
                    <th class="qty">Qtd</th>
                    <th class="price">Preço Unit.</th>
                    <th class="subtotal">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($produtos as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nome']) ?></td>
                    <td class="qty"><?= $item['quantidade'] ?></td>
                    <td class="price">MT <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td class="subtotal">MT <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </section>

            <<div class="totais">
            <div><span>Total:</span> <span class="valor">MT <?= number_format($venda['total'], 2, ',', '.') ?></span></div>
            <div><span>Pago:</span> <span class="valor">MT <?= number_format($venda['valor_pago'], 2, ',', '.') ?></span></div>
            <div><span>Troco:</span> <span class="valor">MT <?= number_format($venda['valor_pago'] - $venda['total'], 2, ',', '.') ?></span></div>
            <div><span>Método:</span> <span class="valor"><?= htmlspecialchars($venda['metodo_pagamento']) ?></span></div>

            <?php if (in_array(strtolower($venda['metodo_pagamento']), ['m-pesa', 'e-mola', 'cartao'])): ?>
                <div><span>Número:</span> <span class="valor"><?= htmlspecialchars($venda['numero_pagamento'] ?? '-') ?></span></div>
            <?php endif; ?>
            </div>


            <footer>
            <p>Obrigado pela sua preferência! Volte sempre :)</p>
            
            </footer>

            </body>
            </html>



            <?php
            $html = ob_get_clean();
            

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->render();

            $pasta = __DIR__ . '/../../public/recibos';
            if (!is_dir($pasta)) {
                mkdir($pasta, 0775, true);
            }

            $pdfPath = $pasta . "/recibo_venda_{$vendaId}.pdf";
            $pdfWebPath = "recibos/recibo_venda_{$vendaId}.pdf";

            $conteudoPdf = $dompdf->output();
            if (!$conteudoPdf) {
                error_log("Dompdf não gerou conteúdo para venda $vendaId");
                return null;
            }

            $salvo = file_put_contents($pdfPath, $conteudoPdf);
            if ($salvo === false) {
                error_log("Falha ao salvar PDF no caminho: $pdfPath");
                return null;
            }

            return $pdfWebPath;
        } catch (\Throwable $e) {
            error_log("Erro ao gerar PDF da venda $vendaId: " . $e->getMessage());
            return null;

            
        }
    }
    
}
