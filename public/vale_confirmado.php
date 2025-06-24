<?php
$numero = $_GET['numero'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Vale Confirmado</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <div class="alert alert-success">
            <h4>✅ Vale Salvo com Sucesso!</h4>
            <p><strong>Número do Vale:</strong> <?= htmlspecialchars($numero) ?></p>
            <a href="vales.php" class="btn btn-primary">🔙 Voltar para Vales</a>
        </div>
    </div>
</body>
</html>
