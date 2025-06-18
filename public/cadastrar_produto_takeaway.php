<?php
session_start();
require_once '../config/database.php';
include 'helpers/voltar_menu.php'; 


if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? '';

    // Validar campos
    if (empty($nome) || empty($preco) || !is_numeric($preco)) {
        $mensagem = "Preencha o nome e um preço válido.";
    } elseif (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        $mensagem = "Erro no upload da imagem.";
    } else {
        $imagemTmp = $_FILES['imagem']['tmp_name'];
        $imagemNome = basename($_FILES['imagem']['name']);
        $extensao = strtolower(pathinfo($imagemNome, PATHINFO_EXTENSION));

        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extensao, $extensoesPermitidas)) {
            $mensagem = "Formato da imagem não permitido. Use jpg, jpeg, png ou gif.";
        } else {
            // Gerar nome único para a imagem
            $novoNome = uniqid() . '.' . $extensao;
            $destino = __DIR__ . '/imagens/' . $novoNome;

            if (move_uploaded_file($imagemTmp, $destino)) {
                // Salvar no banco
                $pdo = Database::conectar();
                $sql = "INSERT INTO produtos_takeaway (nome, preco, imagem) VALUES (:nome, :preco, :imagem)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => $preco,
                    ':imagem' => $novoNome
                ]);

                $mensagem = "Produto cadastrado com sucesso!";
            } else {
                $mensagem = "Falha ao mover a imagem.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastrar Produto Take Away</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h2>Cadastrar Produto Take Away</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Produto</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>

        <div class="mb-3">
            <label for="preco" class="form-label">Preço (MZN)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="preco" name="preco" required>
        </div>

        <div class="mb-3">
            <label for="imagem" class="form-label">Imagem do Produto</label>
            <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*" required>
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar Produto</button>
    </form>
    <div class="text-center mt-4">
            <a href="<?= $pagina_destino ?>" class="btn btn-secondary mb-3">← Voltar ao Menu</a>
        </div>
        
</div>
</body>
</html>

