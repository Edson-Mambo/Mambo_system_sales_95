<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once __DIR__ . '/../helpers/voltar_menu.php';

$pdo = Database::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================
   SEGURANÇA ERP
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$mensagem = '';
$prod = null;
$produto_encontrado = false;

/* =========================
   BUSCAR PRODUTO
========================= */
if (isset($_POST['buscar'])) {

    $busca = trim($_POST['busca'] ?? '');

    if ($busca !== '') {

        $stmt = $pdo->prepare("
            SELECT * FROM produtos 
            WHERE nome LIKE ? OR codigo_barra = ? 
            LIMIT 1
        ");

        $stmt->execute(["%$busca%", $busca]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $produto_encontrado = true;
        } else {
            $mensagem = "⚠️ Produto não encontrado.";
        }
    }
}

/* =========================
   AJUSTE DE ESTOQUE
========================= */
if (isset($_POST['ajustar'])) {

    $id_produto = (int)($_POST['id_produto'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco      = (float)($_POST['preco'] ?? 0);
    $motivo     = trim($_POST['motivo'] ?? '');

    if ($id_produto <= 0 || $motivo === '') {
        $mensagem = "❌ Preencha todos os campos obrigatórios.";
    } else {

        try {

            /* LOG (AUDITORIA) */
            $stmt = $pdo->prepare("
                INSERT INTO ajustes_estoque 
                (produto_id, quantidade_ajustada, motivo, ajustado_por)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_produto, $quantidade, $motivo, $usuario_id]);

            /* ATUALIZA STOCK */
            $stmt = $pdo->prepare("
                UPDATE produtos 
                SET estoque = estoque + ?, preco = ? 
                WHERE id = ?
            ");
            $stmt->execute([$quantidade, $preco, $id_produto]);

            $mensagem = "✅ Ajuste realizado com sucesso!";

            /* RECARREGA PRODUTO */
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$id_produto]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            $produto_encontrado = true;

        } catch (PDOException $e) {
            $mensagem = "❌ Erro no sistema: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>ERP - Ajuste de Estoque</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #eef2f7;
}

.card-erp {
    max-width: 650px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.section {
    margin-top: 15px;
}

.info-box {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.voltar {
    text-align: center;
    margin-top: 20px;
}
</style>
</head>

<body>

<div class="card-erp">

    <!-- HEADER -->
    <div class="header">
        <h4>📦 Ajuste de Estoque ERP</h4>
        <small>Controle de inventário e auditoria</small>
    </div>

    <!-- MENSAGEM -->
    <?php if ($mensagem): ?>
        <div class="alert alert-info text-center">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>

    <!-- BUSCA -->
    <form method="POST" class="section">
        <label>Nome ou Código de Barras</label>
        <input type="text" name="busca" class="form-control" required>
        <button class="btn btn-primary w-100 mt-2" name="buscar">
            🔎 Buscar Produto
        </button>
    </form>

    <!-- RESULTADO -->
    <?php if ($produto_encontrado && $prod): ?>

        <div class="section info-box">
            <b>Produto:</b> <?= htmlspecialchars($prod['nome']) ?><br>
            <b>Código:</b> <?= htmlspecialchars($prod['codigo_barra']) ?><br>
            <b>Estoque Atual:</b> <?= $prod['estoque'] ?><br>
            <b>Preço Atual:</b> MT <?= number_format($prod['preco'],2,',','.') ?>
        </div>

        <!-- FORM AJUSTE -->
        <form method="POST">

            <input type="hidden" name="id_produto" value="<?= $prod['id'] ?>">

            <div class="mb-3">
                <label>Quantidade (+ / -)</label>
                <input type="number" name="quantidade" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Novo Preço</label>
                <input type="number" step="0.01" name="preco"
                       class="form-control"
                       value="<?= $prod['preco'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Motivo do Ajuste</label>
                <input type="text" name="motivo" class="form-control" required>
            </div>

            <button class="btn btn-success w-100" name="ajustar">
                💾 Confirmar Ajuste
            </button>

        </form>

    <?php endif; ?>

</div>

<!-- VOLTAR -->
<div class="voltar">
    <a href="<?= voltarMenu() ?>" class="btn btn-outline-secondary">
        ← Voltar ao Painel
    </a>
</div>

</body>
</html>