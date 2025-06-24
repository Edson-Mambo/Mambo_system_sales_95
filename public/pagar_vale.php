<?php
require_once '../config/database.php';
$pdo = Database::conectar();

$vale_id = $_GET['id'] ?? null;
if (!$vale_id) die('Vale inválido.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor_pago = floatval($_POST['valor_pago']);

    $stmt = $pdo->prepare("SELECT saldo FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();

    if (!$vale) die("Vale não encontrado.");

    $novo_saldo = $vale['saldo'] - $valor_pago;
    $novo_status = $novo_saldo <= 0 ? 'pago' : 'parcelado';

    $pdo->beginTransaction();

    // Registrar pagamento
    $stmt = $pdo->prepare("INSERT INTO pagamentos_vale (vale_id, valor_pago) VALUES (?, ?)");
    $stmt->execute([$vale_id, $valor_pago]);

    // Atualizar saldo e status do vale
    $stmt = $pdo->prepare("UPDATE vales SET valor_pago = valor_pago + ?, saldo = ?, status = ? WHERE id = ?");
    $stmt->execute([$valor_pago, max($novo_saldo, 0), $novo_status, $vale_id]);

    $pdo->commit();

    echo "<p>Pagamento registrado com sucesso.</p>";
    echo "<script>setTimeout(() => window.location.href = 'vales.php', 2000);</script>";
    exit;
}
?>

<form method="post">
    <label>Valor a Pagar:</label>
    <input type="number" step="0.01" name="valor_pago" required>
    <button type="submit">Salvar Pagamento</button>
</form>
