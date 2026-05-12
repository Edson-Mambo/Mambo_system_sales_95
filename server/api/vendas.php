<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

try {

    $pdo = Database::conectar();
    $pdo->beginTransaction();

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || empty($input['itens'])) {
        throw new Exception("Carrinho vazio ou inválido");
    }

    /* =========================
       DADOS BASE
    ========================= */
    $total = $input['total'];
    $usuario_id = $input['usuario_id'];
    $caixa_id = $input['caixa_id'];
    $abertura_id = $input['abertura_id'] ?? null;

    $metodo_pagamento = $input['metodo_pagamento'] ?? 'dinheiro';
    $valor_pago = $input['valor_pago'] ?? 0;
    $troco = $input['troco'] ?? 0;
    $numero_autorizacao = $input['numero_autorizacao'] ?? null;

    /* =========================
       1. RECIBO SEQUENCIAL (SEGURADO)
    ========================= */
    $numero_recibo = 1;

    if ($abertura_id) {

        $stmt = $pdo->prepare("
            SELECT ultimo_numero 
            FROM caixa_recibos 
            WHERE abertura_id = ?
        ");
        $stmt->execute([$abertura_id]);
        $ultimo = $stmt->fetchColumn();

        $numero_recibo = $ultimo ? $ultimo + 1 : 1;

        $pdo->prepare("
            UPDATE caixa_recibos 
            SET ultimo_numero = ?
            WHERE abertura_id = ?
        ")->execute([$numero_recibo, $abertura_id]);
    }

    /* =========================
       2. INSERIR VENDA
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO vendas (
            usuario_id,
            total,
            metodo_pagamento,
            valor_pago,
            troco,
            caixa_id,
            numero_autorizacao,
            numero_recibo,
            data_venda,
            status,
            abertura_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), 'concluida', ?)
    ");

    $stmt->execute([
        $usuario_id,
        $total,
        $metodo_pagamento,
        $valor_pago,
        $troco,
        $caixa_id,
        $numero_autorizacao,
        $numero_recibo,
        $abertura_id
    ]);

    $venda_id = $pdo->lastInsertId();

    /* =========================
       3. PREPARAÇÃO DE QUERIES
    ========================= */
    $stmtItem = $pdo->prepare("
        INSERT INTO itens_venda (
            venda_id,
            produto_id,
            quantidade,
            preco
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmtProduto = $pdo->prepare("
        SELECT nome, codigo_barra, estoque
        FROM produtos
        WHERE id = ?
    ");

    $stmtVendaProduto = $pdo->prepare("
        INSERT INTO produtos_vendidos (
            venda_id,
            produto_id,
            nome_produto,
            quantidade,
            preco_unitario,
            subtotal,
            codigo_barra,
            data_venda
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");

    /* =========================
       4. LOOP ITENS
    ========================= */
    foreach ($input['itens'] as $item) {

        $produto_id = $item['produto_id'];
        $quantidade = $item['quantidade'];
        $preco = $item['preco'];

        /* validar produto */
        $stmtProduto->execute([$produto_id]);
        $p = $stmtProduto->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            throw new Exception("Produto não encontrado ID $produto_id");
        }

        if ($p['estoque'] < $quantidade) {
            throw new Exception("Stock insuficiente: " . $p['nome']);
        }

        $subtotal = $quantidade * $preco;

        /* venda item */
        $stmtItem->execute([
            $venda_id,
            $produto_id,
            $quantidade,
            $preco
        ]);

        /* stock update */
        $pdo->prepare("
            UPDATE produtos 
            SET estoque = estoque - ?
            WHERE id = ?
        ")->execute([
            $quantidade,
            $produto_id
        ]);

        /* relatório */
        $stmtVendaProduto->execute([
            $venda_id,
            $produto_id,
            $p['nome'],
            $quantidade,
            $preco,
            $subtotal,
            $p['codigo_barra']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "venda_id" => $venda_id,
        "numero_recibo" => $numero_recibo
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "status" => "error",
        "mensagem" => $e->getMessage()
    ]);
}