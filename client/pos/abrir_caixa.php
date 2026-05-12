<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

/* =========================
   AUTENTICAÇÃO
========================= */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Mambo_system_sales_95/auth/login.php");
    exit;
}

$pdo = Database::conectar();

$usuario_id = $_SESSION['usuario_id'];

/* =========================
   VERIFICAR SE JÁ EXISTE CAIXA ABERTA
========================= */
$stmt = $pdo->prepare("
    SELECT id 
    FROM abertura_caixa 
    WHERE usuario_id = ? AND status = 'aberto'
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$caixaAberta = $stmt->fetch(PDO::FETCH_ASSOC);

if ($caixaAberta) {
    $_SESSION['abertura_id'] = $caixaAberta['id'];

    header("Location: /Mambo_system_sales_95/pos/index.php");
    exit;
}

/* =========================
   PROCESSAR ABERTURA
========================= */
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $valor_inicial = floatval($_POST['valor_inicial'] ?? 0);

    if ($valor_inicial < 0) {
        $erro = "Valor inicial inválido";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO abertura_caixa 
            (usuario_id, valor_inicial, status, data_abertura)
            VALUES (?, ?, 'aberto', datetime('now'))
        ");

        $stmt->execute([$usuario_id, $valor_inicial]);

        $abertura_id = $pdo->lastInsertId();

        $_SESSION['abertura_id'] = $abertura_id;

        header("Location: /Mambo_system_sales_95/pos/index.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Abrir Caixa</title>
</head>
<body>

<h2>Abrir Caixa</h2>

<p>Utilizador: <?= htmlspecialchars($_SESSION['nome'] ?? '') ?></p>

<form method="POST">

    <label>Valor inicial do caixa:</label><br>
    <input type="number" step="0.01" name="valor_inicial" value="0" required><br><br>

    <button type="submit">Abrir Caixa</button>

</form>

<?php if ($erro): ?>
    <p style="color:red;"><?= $erro ?></p>
<?php endif; ?>

</body>
</html>