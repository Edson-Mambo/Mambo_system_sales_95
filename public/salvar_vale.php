<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

$carrinho = $_SESSION['carrinho'] ?? [];
if (empty($carrinho)) {
    die('Carrinho está vazio.');
}

// Dados do formulário
$cliente_id = $_POST['cliente_id'] ?? null;
$cliente_nome = trim($_POST['cliente_nome'] ?? '');
$cliente_telefone = trim($_POST['cliente_telefone'] ?? '');
$valor_total = floatval($_POST['total_vale'] ?? 0);
$valor_pago = floatval($_POST['valor_pago'] ?? 0);

// Calcula saldo e status
$saldo = max($valor_total - $valor_pago, 0);
if ($valor_pago == 0) {
    $status = 'aberto';
} elseif ($saldo == 0) {
    $status = 'pago';
} else {
    $status = 'parcelado';
}

$numero_vale = 'VALE-' . date('Ymd-His') . '-' . rand(100, 999);

try {
    $pdo->beginTransaction();

    // Inserir o vale
    $stmtVale = $pdo->prepare("INSERT INTO vales 
        (numero_vale, cliente_id, cliente_nome, cliente_telefone, valor_total, valor_pago, saldo, status, data_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmtVale->execute([
        $numero_vale,
        $cliente_id,
        $cliente_nome,
        $cliente_telefone,
        $valor_total,
        $valor_pago,
        $saldo,
        $status
    ]);

    $id_vale = $pdo->lastInsertId();

    // Inserir os itens do vale
    $stmtItem = $pdo->prepare("INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");

    foreach ($carrinho as $codigo_barra => $item) {
        // Buscar produto pelo código de barras para obter o id
        $stmtProd = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barra = ?");
        $stmtProd->execute([$codigo_barra]);
        $produto = $stmtProd->fetch(PDO::FETCH_ASSOC);
    
        if (!$produto) {
            throw new Exception("Produto com código de barras '$codigo_barra' não encontrado.");
        }
    
        $produto_id = $produto['id'];
    
        $stmtItem->execute([
            $id_vale,
            $produto_id,
            $item['quantidade'],
            $item['preco']
        ]);
    }
    
    // Limpa carrinho
    unset($_SESSION['carrinho']);

    $pdo->commit();

    // Exibe mensagem e redireciona após 3 segundos para a lista de vales
    echo "<p style='font-size:18px; color:green;'>✅ Vale Salvo com Sucesso!<br>Número do Vale: <strong>$numero_vale</strong></p>";
    echo "<p>Você será redirecionado para a lista de vales em 3 segundos...</p>";
    echo "<script>
        setTimeout(function() {
            window.location.href = 'vales.php'; // ajuste aqui para a página desejada
        }, 3000);
    </script>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao salvar o vale: " . $e->getMessage());
}
