<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';

require_once '../config/database.php';
include 'helpers/voltar_menu.php'; 

$pdo = Database::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar se o usuário está logado e tem permissão de acesso


// Obter o ID do usuário
$id = $_GET['id'];

// Verificar se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Receber os dados do formulário
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $nivel_acesso = $_POST['nivel'];

    // Atualizar o usuário no banco de dados
    $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, nivel = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    // Verificar se a senha foi modificada, se não, pode ser deixada como está
    $sql = "SELECT senha FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $usuarioAtual = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($senha === $usuarioAtual['senha']) {
        $senha = $usuarioAtual['senha']; // Mantém a senha atual se não for alterada
    } else {
        $senha = password_hash($senha, PASSWORD_DEFAULT); // Hash da senha para segurança
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, nivel = ? WHERE id = ?");
    if ($stmt->execute([$nome, $email, $senha, $nivel_acesso, $id])) {
        // Redirecionar de volta para a página de listar usuários
        header("Location: ../src/View/listar_usuario.php");
        exit();
    } else {
        echo "Erro ao atualizar o usuário: " . $stmt->errorInfo()[2];
    }
}

// Obter os detalhes do usuário com base no ID fornecido na URL
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se o usuário foi encontrado
if (!$usuario) {
    header("Location: listar_usuario.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container mt-5">
        <h1 class="mb-4">Editar Usuário</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . htmlspecialchars($id); ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha:</label>
                <input type="password" class="form-control" id="senha" name="senha" value="<?php echo htmlspecialchars($usuario['senha']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="nivel_acesso" class="form-label">Nível de Acesso:</label>
                <select class="form-select" id="nivel_acesso" name="nivel" required>
                    <option value="supervisor" <?php echo ($usuario['nivel'] == 'gerente') ? 'selected' : ''; ?>>Gerente</option>
                    <option value="supervisor" <?php echo ($usuario['nivel'] == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="caixa" <?php echo ($usuario['nivel'] == 'caixa') ? 'selected' : ''; ?>>Caixa</option>
                    <option value="caixa" <?php echo ($usuario['nivel'] == 'teka_away') ? 'selected' : ''; ?>>Teka Away</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
        <div class="text-center mt-4">
            <a href="../../public/voltar.php" class="btn btn-secondary">← Voltar ao Painel</a>
        </div>
    </div>

</body>
</html>
