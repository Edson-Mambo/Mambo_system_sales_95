<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

$carrinho = &$_SESSION['carrinho'];
if (!isset($carrinho)) {
    $_SESSION['carrinho'] = [];
    $carrinho = &$_SESSION['carrinho'];
}

$erro = '';
$clienteEncontrado = null;

function buscarProduto($pdo, $codigo) {
    $stmt = $pdo->prepare("SELECT codigo_barra, nome, preco FROM produtos WHERE codigo_barra = ? OR nome = ?");
    $stmt->execute([$codigo, $codigo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function buscarCliente($pdo, $busca) {
    $stmt = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? OR telefone LIKE ? LIMIT 1");
    $likeBusca = "%$busca%";
    $stmt->execute([$likeBusca, $likeBusca]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['adicionar'])) {
    $busca = trim($_POST['busca_produto'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 1);
    if ($busca !== '' && $quantidade > 0) {
        $produto = buscarProduto($pdo, $busca);
        if ($produto) {
            $codigo = $produto['codigo_barra'];
            if (isset($carrinho[$codigo])) {
                $carrinho[$codigo]['quantidade'] += $quantidade;
            } else {
                $carrinho[$codigo] = [
                    'nome' => $produto['nome'],
                    'preco' => (float)$produto['preco'],
                    'quantidade' => $quantidade
                ];
            }
        } else {
            $erro = "Produto não encontrado: " . htmlspecialchars($busca);
        }
    } else {
        $erro = "Informe um produto e quantidade válidos.";
    }
}

if (isset($_POST['remover_produto'])) {
    $codigoRemover = $_POST['remover_produto'];
    if (isset($carrinho[$codigoRemover])) {
        unset($carrinho[$codigoRemover]);
    }
}

$clienteNome = $_POST['cliente_nome'] ?? '';
$clienteTelefone = $_POST['cliente_telefone'] ?? '';
if (!empty($clienteNome) || !empty($clienteTelefone)) {
    $buscaCliente = $clienteNome ?: $clienteTelefone;
    $clienteEncontrado = buscarCliente($pdo, $buscaCliente);
}

$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['quantidade'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Gerenciar Vales</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">Adicionar Produtos ao Vale</h1>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="cliente_nome" class="form-label">Nome do Cliente</label>
                <input type="text" id="cliente_nome" name="cliente_nome" value="<?= htmlspecialchars($clienteNome) ?>" class="form-control" placeholder="Digite o nome do cliente">
            </div>
            <div class="col-md-4">
                <label for="cliente_telefone" class="form-label">Telefone do Cliente</label>
                <input type="text" id="cliente_telefone" name="cliente_telefone" value="<?= htmlspecialchars($clienteTelefone) ?>" class="form-control" placeholder="Digite o telefone do cliente">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-info">Buscar Cliente</button>
            </div>
        </div>
    </form>

    <!-- Buscar vale salvo -->
    <form method="get" action="buscar_vale.php" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <input type="text" name="termo_busca" class="form-control" placeholder="Buscar vale por nome ou telefone" required>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-secondary">Buscar Vale Salvo</button>
            </div>
        </div>
    </form>

    <!-- Formulário para adicionar produtos -->
    <form method="post" class="row g-3 mb-4 align-items-end">
        <div class="col-md-6">
            <label for="busca_produto" class="form-label">Código ou Nome do produto</label>
            <input type="text" id="busca_produto" name="busca_produto" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label for="quantidade" class="form-label">Quantidade</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="1" class="form-control" required>
        </div>
        <div class="col-md-4 d-grid">
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Produto</button>
        </div>
    </form>

    <!-- Tabela de produtos -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr><th>Nome</th><th>Preço Unit.</th><th>Qtd</th><th>Subtotal</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($carrinho)): ?>
                    <?php foreach ($carrinho as $codigo => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome']) ?></td>
                            <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>MT <?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?></td>
                            <td>
                                <form method="post">
                                    <button type="submit" name="remover_produto" value="<?= htmlspecialchars($codigo) ?>" class="btn btn-danger btn-sm">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Carrinho vazio</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="3" class="text-end fw-bold">Total:</td>
                    <td colspan="2">MT <?= number_format($total, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Formulário salvar/finalizar -->
    <form method="post" action="salvar_vale.php">
        <input type="hidden" name="total_vale" value="<?= $total ?>">
        <input type="hidden" name="cliente_id" value="<?= $clienteEncontrado['id'] ?? '' ?>">
        <input type="hidden" name="cliente_nome" value="<?= htmlspecialchars($clienteNome) ?>">
        <input type="hidden" name="cliente_telefone" value="<?= htmlspecialchars($clienteTelefone) ?>">

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="valor_pago" class="form-label">Valor Pago (MT)</label>
                <input type="number" step="0.01" name="valor_pago" id="valor_pago" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="tipo_pagamento" class="form-label">Tipo de Pagamento</label>
                <select name="tipo_pagamento" id="tipo_pagamento" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="total">Pagamento Total</option>
                    <option value="parcial">Pagamento Parcial</option>
                    <option value="nenhum">Ainda não pagou</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-3">
            <button type="submit" name="acao" value="salvar" class="btn btn-warning">Salvar Vale</button>
            <button type="submit" name="acao" value="finalizar" class="btn btn-success">Finalizar e Gerar Recibo</button>
        </div>
    </form>
</div>

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
