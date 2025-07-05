<?php
class Venda {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function salvarVenda($itens, $usuario_id, $metodo_pagamento, $total, $valor_pago, $troco) {
        try {
            $this->pdo->beginTransaction();

            // Pega cliente da sessão (igual no Vale!)
            $cliente_id = $_SESSION['cliente_id'] ?? null;

            // INSERE a venda com tudo!
            $stmtVenda = $this->pdo->prepare("
                INSERT INTO vendas 
                (usuario_id, cliente_id, metodo_pagamento, total, valor_pago, troco, data_venda, data_hora) 
                VALUES 
                (:usuario_id, :cliente_id, :metodo_pagamento, :total, :valor_pago, :troco, NOW(), NOW())
            ");

            $stmtVenda->execute([
                ':usuario_id' => $usuario_id,
                ':cliente_id' => $cliente_id,
                ':metodo_pagamento' => $metodo_pagamento,
                ':total' => $total,
                ':valor_pago' => $valor_pago,
                ':troco' => $troco
            ]);

            $vendaId = $this->pdo->lastInsertId();

            // Produtos vendidos
            $stmtItem = $this->pdo->prepare("
                INSERT INTO produtos_vendidos 
                (venda_id, produto_id, quantidade, preco_unitario) 
                VALUES (:venda_id, :produto_id, :quantidade, :preco_unitario)
            ");

            foreach ($itens as $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':produto_id' => $item['id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco'],
                ]);

                // Atualiza estoque
                $stmtAtualizaEstoque = $this->pdo->prepare("
                    UPDATE produtos 
                    SET quantidade_estoque = quantidade_estoque - :quantidade 
                    WHERE id = :produto_id
                ");

                $stmtAtualizaEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':produto_id' => $item['id'],
                ]);
            }

            $this->pdo->commit();
            return $vendaId; // Retorna o ID se quiser usar depois
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
        
    }
}
