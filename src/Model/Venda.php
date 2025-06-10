<?php
class Venda {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function salvarVenda($itens) {
        try {
            $this->pdo->beginTransaction();

            $stmtVenda = $this->pdo->prepare("INSERT INTO vendas (data_venda) VALUES (NOW())");
            $stmtVenda->execute();
            $vendaId = $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("INSERT INTO produtos_vendidos (venda_id, produto_id, quantidade, preco_unitario) VALUES (:venda_id, :produto_id, :quantidade, :preco_unitario)");

            foreach ($itens as $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':produto_id' => $item['id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco_unitario' => $item['preco'],
                ]);

                // Atualiza estoque
                $stmtAtualizaEstoque = $this->pdo->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - :quantidade WHERE id = :produto_id");
                $stmtAtualizaEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':produto_id' => $item['id'],
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
