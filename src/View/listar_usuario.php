<?php
session_start();
require_once '../../config/database.php';

$pdo = Database::conectar();




// Função para listar os usuários
function listarUsuarios($pdo) {
    $sql = "SELECT * FROM usuarios";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$usuarios = listarUsuarios($pdo);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <title>Listagem de Usuários | Mambo System</title>
  <!-- Bootstrap CSS -->
  <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

<div class="container mt-5">
    <h1 class="mb-4">Listagem de Usuários</h1>

    <!-- Tabela de usuários -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Nível de Acesso</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario) { ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['id']) ?></td>
                    <td><?= htmlspecialchars($usuario['nome']) ?></td>
                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                    <td><?= htmlspecialchars($usuario['nivel']) ?></td>
                    <td>
                        <a href="../../public/editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="../../public/deletar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Tem certeza que deseja deletar este usuário?')">
                            <i class="bi bi-trash"></i> Deletar
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Botão voltar -->
    <div class="text-center mt-4">
        <a href="../../public/index_admin.php" class="btn btn-secondary">
            ← Voltar ao Painel
        </a>
    </div>
</div>

<!-- Bootstrap JS + Popper -->
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
