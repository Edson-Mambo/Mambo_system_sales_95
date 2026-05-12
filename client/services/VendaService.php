<?php

class VendaService {

    private $pdo;

    public function __construct() {
        $this->pdo = new PDO("sqlite:" . __DIR__ . "/../localdb/mambo_local.db");
    }

    public function finalizar($carrinho, $usuario_id) {

        if (empty($carrinho)) {
            return ["success" => false, "msg" => "Carrinho vazio"];
        }

        $this->pdo->beginTransaction();

        $total = 0;

        foreach ($carrinho as $item) {

            $preco = (float)(
                $item['preco']
                ?? $item['preco_venda']
                ?? 0
            );

            $quantidade = (float)($item['quantidade'] ?? 0);

            $total += $preco * $quantidade;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO vendas (usuario_id, total, status_sync)
            VALUES (?, ?, 'pendente')
        ");

        $stmt->execute([$usuario_id, $total]);

        $venda_id = $this->pdo->lastInsertId();

        foreach ($carrinho as $item) {

            $preco = (float)(
                $item['preco']
                ?? $item['preco_venda']
                ?? 0
            );

            $stmtItem = $this->pdo->prepare("
                INSERT INTO venda_itens (venda_id, produto_id, quantidade, preco)
                VALUES (?, ?, ?, ?)
            ");

            $stmtItem->execute([
                $venda_id,
                $item['id'],
                $item['quantidade'],
                $preco
            ]);
        }

        $this->pdo->commit();

        unset($_SESSION['carrinho']);

        return [
            "success" => true,
            "venda_id" => $venda_id,
            "total" => $total
        ];
    }
}