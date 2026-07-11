<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class VendaService {

    private $pdo;

    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: new PDO(
            "sqlite:" . __DIR__ . "/../localdb/mambo_local.db",
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->pdo->exec("PRAGMA foreign_keys = ON;");
    }

    public function finalizar(array $dados, int $usuario_id): array {

        /* =========================
           VALIDAÇÕES INICIAIS
        ========================= */
        if (empty($dados['itens']) || !is_array($dados['itens'])) {
            return ["success" => false, "message" => "Carrinho vazio"];
        }

        if (!$usuario_id) {
            return ["success" => false, "message" => "Usuário inválido"];
        }

        $metodosValidos = ['dinheiro', 'mpesa', 'emola', 'cartao', 'credito'];
        $metodo_pagamento = strtolower(trim($dados['metodo_pagamento'] ?? 'dinheiro'));

        if (!in_array($metodo_pagamento, $metodosValidos, true)) {
            return ["success" => false, "message" => "Método de pagamento inválido"];
        }

        $itens       = $dados['itens'];
        $cliente_id  = !empty($dados['cliente_id']) ? (int)$dados['cliente_id'] : null;
        $sessao_id   = !empty($dados['sessao_id']) ? (int)$dados['sessao_id'] : null;
        $numero_ref  = !empty($dados['numero_referencia']) ? trim($dados['numero_referencia']) : null;
        $observacao  = !empty($dados['observacao']) ? trim($dados['observacao']) : null;

        try {

            $this->pdo->beginTransaction();

            /* =========================
               PRODUTOS DO SISTEMA
            ========================= */
            $produto_ids = array_unique(array_map(
                fn($i) => (int)($i['produto_id'] ?? $i['id'] ?? 0),
                $itens
            ));

            $produto_ids = array_filter($produto_ids);

            if (empty($produto_ids)) {
                $this->pdo->rollBack();
                return ["success" => false, "message" => "Produtos inválidos no carrinho"];
            }

            $placeholders = implode(',', array_fill(0, count($produto_ids), '?'));

            $stmtProd = $this->pdo->prepare("
                SELECT id, nome, preco, custo, estoque
                FROM produtos
                WHERE id IN ($placeholders)
                  AND (ativo = 1 OR ativo IS NULL)
                  AND deleted_at IS NULL
            ");

            $stmtProd->execute(array_values($produto_ids));

            $produtos_map = [];
            foreach ($stmtProd->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $produtos_map[$p['id']] = $p;
            }

            /* =========================
               CALCULAR ITENS
            ========================= */
            $subtotal = 0.0;
            $itens_processados = [];

            foreach ($itens as $item) {

                // 🔥 NORMALIZAÇÃO SEGURA
                $produto_id = (int)($item['produto_id'] ?? $item['id'] ?? 0);
                $quantidade = (float)($item['quantidade'] ?? 0);
                $preco      = (float)($item['preco'] ?? 0);
                $desconto_item = (float)($item['desconto'] ?? 0);

                if ($produto_id <= 0 || $quantidade <= 0) {
                    $this->pdo->rollBack();
                    return ["success" => false, "message" => "Item inválido no carrinho"];
                }

                if (!isset($produtos_map[$produto_id])) {
                    $this->pdo->rollBack();
                    return ["success" => false, "message" => "Produto ID $produto_id não encontrado"];
                }

                $produto = $produtos_map[$produto_id];

                $preco_real = ($preco > 0)
                    ? $preco
                    : (float)$produto['preco'];

                if ($preco_real <= 0) {
                    $this->pdo->rollBack();
                    return ["success" => false, "message" => "Preço inválido no produto ID $produto_id"];
                }

                $item_sub = round(($preco_real * $quantidade) - $desconto_item, 2);

                if ($item_sub < 0) {
                    $item_sub = 0;
                }

                $subtotal += $item_sub;

                $itens_processados[] = [
                    'produto_id'   => $produto_id,
                    'nome_produto' => $produto['nome'],
                    'preco_custo'  => (float)$produto['custo'],
                    'quantidade'   => $quantidade,
                    'preco'        => $preco_real,
                    'desconto'     => $desconto_item,
                    'subtotal'     => $item_sub,
                ];
            }

            if ($subtotal <= 0) {
                $this->pdo->rollBack();
                return ["success" => false, "message" => "Subtotal inválido"];
            }

            /* =========================
               DESCONTO GLOBAL
            ========================= */
            $desconto_global = 0.0;

            if (!empty($dados['desconto'])) {
                $desc = $dados['desconto'];

                if (is_numeric($desc) && (float)$desc > 0) {
                    $desconto_global = ((float)$desc <= 100)
                        ? round($subtotal * ((float)$desc / 100), 2)
                        : round((float)$desc, 2);
                } else {
                    $desconto_global = round($subtotal * 0.10, 2);
                }
            }

            /* =========================
               IVA
            ========================= */
            $imposto = 0.0;

            try {
                $cfgIva = $this->pdo->query("
                    SELECT valor FROM configuracoes WHERE chave = 'iva_activo' LIMIT 1
                ")->fetchColumn();

                if ($cfgIva == '1') {
                    $pctIva = (float)$this->pdo->query("
                        SELECT valor FROM configuracoes WHERE chave = 'iva_percentagem' LIMIT 1
                    ")->fetchColumn();

                    $imposto = round(($subtotal - $desconto_global) * ($pctIva / 100), 2);
                }
            } catch (Throwable $e) {}

            $total = round($subtotal - $desconto_global + $imposto, 2);

            /* =========================
               PAGAMENTO
            ========================= */
            $valor_pago = (float)($dados['valor_pago'] ?? 0);

            if ($metodo_pagamento === 'credito') {
                $valor_pago = 0.0;
                $troco = 0.0;
            } else {
                if ($valor_pago < $total) {
                    $this->pdo->rollBack();
                    return ["success" => false, "message" => "Valor pago insuficiente"];
                }

                $troco = round($valor_pago - $total, 2);
            }

            /* =========================
               INSERIR VENDA
            ========================= */
            $uuid_venda = $this->gerarUuid();

            $stmt = $this->pdo->prepare("
                INSERT INTO vendas (
                    uuid,
                    usuario_id,
                    cliente_id,
                    sessao_id,
                    subtotal,
                    desconto,
                    imposto,
                    total,
                    metodo_pagamento,
                    valor_recebido,
                    troco,
                    numero_referencia,
                    observacao,
                    status,
                    sync_status,
                    created_at,
                    updated_at
                )
                VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 'concluida', 0,
                    datetime('now'), datetime('now')
                )
            ");

            $stmt->execute([
                $uuid_venda,
                $usuario_id,
                $cliente_id,
                $sessao_id,
                $subtotal,
                $desconto_global,
                $imposto,
                $total,
                $metodo_pagamento,
                $valor_pago,
                $troco,
                $numero_ref,
                $observacao
            ]);

            $venda_id = (int)$this->pdo->lastInsertId();

            /* =========================
               ITENS + STOCK
            ========================= */
            $stmtItem = $this->pdo->prepare("
                INSERT INTO venda_itens (
                    uuid,
                    venda_id,
                    produto_id,
                    nome_produto,
                    preco_custo,
                    quantidade,
                    preco,
                    desconto,
                    subtotal,
                    sync_status,
                    created_at,
                    updated_at
                )
                VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 0,
                    datetime('now'), datetime('now')
                )
            ");

            $stmtStock = $this->pdo->prepare("
                UPDATE produtos
                SET estoque = estoque - ?,
                    updated_at = datetime('now'),
                    sync_status = 0
                WHERE id = ?
            ");

            foreach ($itens_processados as $item) {

                $stmtItem->execute([
                    $this->gerarUuid(),
                    $venda_id,
                    $item['produto_id'],
                    $item['nome_produto'],
                    $item['preco_custo'],
                    $item['quantidade'],
                    $item['preco'],
                    $item['desconto'],
                    $item['subtotal']
                ]);

                $stmtStock->execute([
                    $item['quantidade'],
                    $item['produto_id']
                ]);
            }

            $this->pdo->commit();

            return [
                "success" => true,
                "venda_id" => $venda_id,
                "uuid" => $uuid_venda,
                "subtotal" => $subtotal,
                "desconto" => $desconto_global,
                "imposto" => $imposto,
                "total" => $total,
                "troco" => $troco,
                "metodo_pagamento" => $metodo_pagamento,
                "itens" => $itens_processados
            ];

        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    private function gerarUuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}