<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$vales_selecionados = $_POST['vales_selecionados'] ?? [];

if (empty($vales_selecionados)) {
    die('Nenhum vale selecionado.');
}

try {
    $pdo->beginTransaction();

    foreach ($vales_selecionados as $vale_id) {
        // Busca o vale
        $stmtVale = $pdo->prepare("SELECT * FROM vales WHERE id = ?");
        $stmtVale->execute([$vale_id]);
        $vale = $stmtVale->fetch(PDO::FETCH_ASSOC);

        if (!$vale || $vale['status'] === 'pago') {
            continue;
        }

        // Inserir em vendas_vales
        $stmtVenda = $pdo->prepare("
            INSERT INTO vendas_vales (vale_id, cliente_nome, cliente_telefone, valor_total, status)
            VALUES (?, ?, ?, ?, 'finalizado')
        ");
        $stmtVenda->execute([
            $vale['id'],
            $vale['cliente_nome'],
            $vale['cliente_telefone'],
            $vale['valor_total']
        ]);
        $venda_vale_id = $pdo->lastInsertId();

        // Buscar os itens do vale
        $stmtItens = $pdo->prepare("SELECT produto_id, quantidade, preco_unitario FROM itens_vale WHERE vale_id = ?");
        $stmtItens->execute([$vale_id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Inserir os itens na nova tabela
        $stmtItemVenda = $pdo->prepare("
            INSERT INTO itens_vendas_vales (venda_vale_id, produto_id, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($itens as $item) {
            $stmtItemVenda->execute([
                $venda_vale_id,
                $item['produto_id'],
                $item['quantidade'],
                $item['preco_unitario']
            ]);
        }

        // Atualizar o vale
        $stmtUpdate = $pdo->prepare("
            UPDATE vales SET status = 'pago', saldo = 0, valor_pago = valor_total WHERE id = ?
        ");
        $stmtUpdate->execute([$vale_id]);
    }

    $pdo->commit();

    echo "<p style='color:green;font-weight:bold'>Vales finalizados com sucesso e salvos em vendas_vales.</p>";
    echo "<a href='buscar_vales.php'>← Voltar à Busca</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao finalizar vales: " . $e->getMessage());
}
?>
