<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Conexão
$pdo = Database::conectar();

// Verifica se o ID foi passado corretamente (GET ou POST)
$id = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        echo "ID inválido.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = $_POST['id'];

        // 1️⃣ Apagar os logs_login primeiro para respeitar o FOREIGN KEY
        $sqlLogs = "DELETE FROM logs_login WHERE usuario_id = ?";
        $stmtLogs = $pdo->prepare($sqlLogs);
        $stmtLogs->execute([$id]);

        // 2️⃣ Agora pode excluir o usuário
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // 3️⃣ Redireciona de volta para a listagem
        header("Location: ../src/View/listar_usuario.php?mensagem=Usuario deletado com sucesso");
        exit;
    } else {
        echo "ID inválido.";
        exit;
    }
}

// Busca os detalhes do usuário (só se for GET)
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: ../src/View/listar_usuario.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Deletar Usuário</title>
    <!-- Bootstrap CSS local -->
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-danger">Deletar Usuário</h1>

        <p class="lead">Tem certeza que deseja deletar o usuário <strong>"<?= htmlspecialchars($usuario['nome']) ?>"</strong>?</p>

        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']); ?>">
            <button type="submit" class="btn btn-danger">Sim, Deletar</button>
            <!-- Link corrigido -->
            <a href="../src/View/listar_usuario.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
