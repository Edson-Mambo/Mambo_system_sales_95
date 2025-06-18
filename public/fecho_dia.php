<?php
session_start(); // IMPORTANTÍSSIMO para acessar $_SESSION

// Verificar se está logado e nível de acesso:
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], ['admin', 'gerente', 'supervisor'])) {
    echo "Acesso negado.";
    exit;
}


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controller/FechoController.php';

$pdo = Database::conectar();
$fechoController = new Controller\FechoController($pdo);

$mensagem = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fechoController->realizarFecho();
    $mensagem = $fechoController->mensagem ?? '';
    $sucesso = (strpos($mensagem, 'sucesso') !== false);
}
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Fecho do Dia</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Fecho do Dia</h1>

    <?php if ($mensagem): ?>
        <div class="alert <?= $sucesso ? 'alert-success' : 'alert-danger' ?>" role="alert">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <p>Ao fazer o fecho do dia, todos os usuários serão desconectados e as transações do dia serão consolidadas.</p>
        <button type="submit" name="fechar_dia" class="btn btn-primary" onclick="return confirm('Confirma realizar o fecho do dia?')">
            Realizar Fecho do Dia
        </button>
    </form>
    <div class="text-center mt-4">
            <a href="<?= $pagina_destino ?>" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
