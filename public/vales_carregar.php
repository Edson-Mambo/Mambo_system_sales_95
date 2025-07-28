<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

// Verifica se veio o POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_vale'])) {
    $vale_id = (int)$_POST['id_vale'];

    // Limpa carrinho anterior
    $_SESSION['vale_carrinho'] = [];

    // Pega dados do vale (opcional)
    $stmtVale = $pdo->prepare("SELECT * FROM vales WHERE id = ?");
    $stmtVale->execute([$vale_id]);
    $vale = $stmtVale->fetch(PDO::FETCH_ASSOC);

    if ($vale) {
        // Pega itens do vale correto: ITENS_VALE
        $stmtItens = $pdo->prepare("
            SELECT 
                iv.produto_id,
                p.nome,
                p.preco,
                iv.quantidade
            FROM itens_vale iv
            JOIN produtos p ON iv.produto_id = p.id
            WHERE iv.vale_id = ?
        ");
        $stmtItens->execute([$vale_id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Preenche carrinho
        foreach ($itens as $item) {
            $_SESSION['vale_carrinho'][$item['produto_id']] = [
                'id'         => $item['produto_id'],
                'nome'       => $item['nome'],
                'preco'      => $item['preco'],
                'quantidade' => $item['quantidade'],
            ];
        }

        // Guarda id do cliente (opcional)
        $_SESSION['cliente_id'] = $vale['cliente_id'] ?? null;

        // Redireciona para a página do vale/venda
        header('Location: venda_vale.php');
        exit;

    } else {
        echo "Vale não encontrado.";
    }

} else {
    echo "Requisição inválida.";
}
