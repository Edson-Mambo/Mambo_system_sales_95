<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class VendaService {

    private $pdo;

    public function __construct() {
        $this->pdo = new PDO(
            "sqlite:" . __DIR__ . "/../localdb/mambo_local.db",
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    public function finalizar(
        $carrinho,
        $usuario_id,
        $metodo_pagamento = null,
        $status = 'pendente'
    ) {

        /* =========================
           VALIDAÇÕES BASE
        ========================= */
        if (empty($carrinho)) {
            return ["success" => false, "message" => "Carrinho vazio"];
        }

        if (!$usuario_id) {
            return ["success" => false, "message" => "Usuário inválido"];
        }

        /* =========================
           MÉTODOS VÁLIDOS
        ========================= */
        $metodosValidos = ['dinheiro', 'm-pesa', 'e-mola', 'cartao'];

        // 🔥 CORREÇÃO PRINCIPAL: fallback só aqui
        $metodo_pagamento = strtolower(trim($metodo_pagamento ?? 'dinheiro'));

        if (!in_array($metodo_pagamento, $metodosValidos, true)) {
            return [
                "success" => false,
                "message" => "Método de pagamento inválido"
            ];
        }

        try {

            $this->pdo->beginTransaction();

            /* =========================
               CALCULAR TOTAL
            ========================= */
            $total = 0;

            foreach ($carrinho as $item) {

                $preco = (float)($item['preco'] ?? $item['preco_venda'] ?? 0);
                $quantidade = (int)($item['quantidade'] ?? 0);

                $total += $preco * $quantidade;
            }

            /* =========================
               INSERIR VENDA
            ========================= */
            $stmt = $this->pdo->prepare("
                INSERT INTO vendas (
                    usuario_id,
                    total,
                    metodo_pagamento,
                    status,
                    data
                )
                VALUES (?, ?, ?, ?, datetime('now'))
            ");

            $stmt->execute([
                $usuario_id,
                $total,
                $metodo_pagamento,
                $status
            ]);

            $venda_id = $this->pdo->lastInsertId();

            /* =========================
               ITENS
            ========================= */
            foreach ($carrinho as $item) {

                $preco = (float)($item['preco'] ?? $item['preco_venda'] ?? 0);

                $stmtItem = $this->pdo->prepare("
                    INSERT INTO venda_itens (
                        venda_id,
                        produto_id,
                        quantidade,
                        preco
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmtItem->execute([
                    $venda_id,
                    $item['id'],
                    (int)$item['quantidade'],
                    $preco
                ]);
            }

            $this->pdo->commit();

            return [
                "success" => true,
                "venda_id" => $venda_id,
                "total" => $total,
                "metodo_pagamento" => $metodo_pagamento
            ];

        } catch (Throwable $e) {

            $this->pdo->rollBack();

            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }
}