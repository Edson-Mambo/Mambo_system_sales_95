<?php
require_once '../config/database.php';
$pdo = Database::conectar();

$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

$condicoes = [];
$params = [];

if (!empty($dataInicio)) {
    $condicoes[] = 'v.data_venda >= :inicio';
    $params[':inicio'] = $dataInicio . ' 00:00:00';
}
if (!empty($dataFim)) {
    $condicoes[] = 'v.data_venda <= :fim';
    $params[':fim'] = $dataFim . ' 23:59:59';
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$sql = "
SELECT 
    v.id AS venda_id,
    v.data_venda,
    i.nome_produto,
    i.preco_unitario,
    i.quantidade,
    i.total
FROM vendas_teka_away v
JOIN itens_venda_teka_away i ON v.id = i.id_venda
$where
ORDER BY v.data_venda DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cabeçalhos para download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=relatorio_teka_away.xls");

echo "Venda ID\tData Venda\tProduto\tQuantidade\tPreço Unitário\tTotal\n";

foreach ($dados as $linha) {
    echo "{$linha['venda_id']}\t{$linha['data_venda']}\t{$linha['nome_produto']}\t{$linha['quantidade']}\t{$linha['preco_unitario']}\t{$linha['total']}\n";
}
exit;
