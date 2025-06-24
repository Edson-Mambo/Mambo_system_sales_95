$stmt = $pdo->prepare("
    SELECT p.nome, p.preco, vp.quantidade
    FROM vale_produtos vp
    JOIN produtos p ON vp.codigo_barra = p.codigo_barra
    WHERE vp.vale_id = ?
");
$stmt->execute([$vale_id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para exibir
foreach ($produtos as $item) {
    $subtotal = $item['preco'] * $item['quantidade'];
    echo "<tr>
        <td>{$item['nome']}</td>
        <td>MT " . number_format($item['preco'], 2, ',', '.') . "</td>
        <td>{$item['quantidade']}</td>
        <td>MT " . number_format($subtotal, 2, ',', '.') . "</td>
    </tr>";
}
