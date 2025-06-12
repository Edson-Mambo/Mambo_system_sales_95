<?php
session_start();
require_once '../config/database.php';  // Ajuste conforme sua estrutura

try {
    $pdo = Database::conectar();
    $pdo->beginTransaction();

    // Dados do formulário
    $cliente_nome = $_POST['cliente_nome'] ?? '';
    $cliente_telefone = $_POST['cliente_telefone'] ?? '';
    $total_vale = $_POST['total_vale'] ?? 0;
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    // Inserir o vale
    $sqlVale = "INSERT INTO vales (cliente_nome, valor_total, valor_pago, data_registro, status, usuario_id) 
                VALUES (:cliente_nome, :valor_total, 0.00, NOW(), 'aberto', :usuario_id)";
    $stmtVale = $pdo->prepare($sqlVale);
    $stmtVale->execute([
        ':cliente_nome' => $cliente_nome,
        ':valor_total' => $total_vale,
        ':usuario_id' => $usuario_id
    ]);

    $idVale = $pdo->lastInsertId();

    // Inserir os itens do vale
    $sqlItens = "INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) 
                 VALUES (:vale_id, :produto_id, :quantidade, :preco_unitario)";
    $stmtItens = $pdo->prepare($sqlItens);

    foreach ($_SESSION['carrinho'] as $item) {
        $stmtItens->execute([
            ':vale_id' => $idVale,
            ':produto_id' => $item['id_produto'],
            ':quantidade' => $item['quantidade'],
            ':preco_unitario' => $item['preco_unitario']
        ]);
    }

    $pdo->commit();

    // Limpar o carrinho após salvar
    unset($_SESSION['carrinho']);

    header('Location: venda.php?success=Vale registrado com sucesso!');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Erro ao salvar o vale: " . $e->getMessage();
}
?>
