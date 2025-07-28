<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/configuracoes/logMiddleware.php';

$pdo = Database::conectar();

// Garante que tenha número do vale
if (!isset($_SESSION['numero_vale'])) {
    $_SESSION['numero_vale'] = rand(1000, 9999);
}
$numero_vale = $_SESSION['numero_vale'];

// Inicializa carrinho se não existir
if (!isset($_SESSION['vale_carrinho'])) {
    $_SESSION['vale_carrinho'] = [];
}
$carrinho = &$_SESSION['vale_carrinho'];

// Função para calcular total
function calcularTotal($itens) {
    $s = 0;
    foreach ($itens as $i) {
        $s += $i['preco'] * $i['quantidade'];
    }
    return $s;
}

$erro = '';
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$cliente_id = $_SESSION['cliente_id'] ?? null;
$clienteSelecionadoNome = 'Nenhum cliente selecionado';

if ($cliente_id) {
    $stmtCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmtCli->execute([$cliente_id]);
    if ($cli = $stmtCli->fetch(PDO::FETCH_ASSOC)) {
        $clienteSelecionadoNome = $cli['nome'];
    }
}

$total = calcularTotal($carrinho);

// 1. Salvar vale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    header('Content-Type: application/json');

    $cliente_id_post = $_POST['cliente_id'] ?? $cliente_id;
    $status = $_POST['status_pagamento'] ?? 'aberto';
    $total_post = calcularTotal($carrinho);

    if ($cliente_id_post && $total_post > 0) {
        $num = $numero_vale;

        // Confirma número não duplicado
        $chk = $pdo->prepare("SELECT 1 FROM vales WHERE numero_vale = ?");
        $chk->execute([$num]);
        if ($chk->fetch()) {
            $num = rand(1000, 9999);
        }

        $saldo = ($status === 'pago') ? 0 : $total_post;

        $stmt = $pdo->prepare("
            INSERT INTO vales (cliente_id, numero_vale, cliente_nome, cliente_telefone, valor_total, status, saldo, data_registro)
            SELECT ?, ?, nome, telefone, ?, ?, ?, NOW() FROM clientes WHERE id = ?
        ");
        $stmt->execute([$cliente_id_post, $num, $total_post, $status, $saldo, $cliente_id_post]);

        $valeId = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("
            INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)
        ");

        $stmtBaixa = $pdo->prepare("
            UPDATE produtos SET estoque = estoque - ? WHERE id = ?
        ");

        foreach ($carrinho as $it) {
            $stmtItem->execute([$valeId, $it['id'], $it['quantidade'], $it['preco']]);
            $stmtBaixa->execute([$it['quantidade'], $it['id']]);
        }

        // Limpa carrinho e número do vale
        $_SESSION['vale_carrinho'] = [];
        unset($_SESSION['numero_vale']);

        echo json_encode(['success' => true, 'message' => "Vale #{$num} salvo com sucesso!"]);
    } else {
        echo json_encode(['success' => false, 'error' => "Selecione cliente e adicione produtos."]);
    }
    exit;
}

// 2. Buscar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_cliente'])) {
    $busca = trim($_POST['cliente_nome_ou_telefone'] ?? '');
    if ($busca !== '') {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR telefone LIKE ?");
        $stmt->execute(["%$busca%", "%$busca%"]);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($clientes) === 1) {
            $_SESSION['cliente_id'] = $clientes[0]['id'];
            header('Location: venda_vale.php');
            exit;
        } elseif (count($clientes) > 1) {
            $_SESSION['clientes_encontrados'] = $clientes;
        } else {
            $erro = "Cliente não encontrado.";
        }
    } else {
        $erro = "Digite nome ou telefone.";
    }
}

// 3. Selecionar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selecionar_cliente_id'])) {
    $_SESSION['cliente_id'] = (int)$_POST['selecionar_cliente_id'];
    header('Location: venda_vale.php');
    exit;
}

// 4. Cadastrar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_cliente'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($nome && $telefone) {
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
        $stmt->execute([$telefone]);
        if ($stmt->fetch()) {
            $erro = "Telefone já cadastrado.";
        } else {
            $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)")->execute([$nome, $telefone]);
            $_SESSION['cliente_id'] = $pdo->lastInsertId();
            $_SESSION['mensagem'] = "Cliente cadastrado com sucesso!";
            header('Location: venda_vale.php');
            exit;
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}

// 5. Buscar vale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_vale'])) {
    $numero = trim($_POST['numero_vale'] ?? '');
    if ($numero !== '') {
        $stmt = $pdo->prepare("SELECT * FROM vales WHERE numero_vale = ? AND status != 'pago'");
        $stmt->execute([$numero]);
        if ($vale = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['cliente_id'] = $vale['cliente_id'];
            $carrinho = [];

            $stmtItens = $pdo->prepare("
                SELECT iv.*, p.nome, p.preco_venda 
                FROM itens_vale iv
                JOIN produtos p ON iv.produto_id = p.id
                WHERE iv.vale_id = ?
            ");
            $stmtItens->execute([$vale['id']]);
            foreach ($stmtItens->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $carrinho[$it['produto_id']] = [
                    'id' => $it['produto_id'],
                    'nome' => $it['nome'],
                    'preco' => $it['preco_venda'],
                    'quantidade' => $it['quantidade'],
                ];
            }

            $_SESSION['mensagem'] = "Vale #{$numero} carregado.";
            header('Location: venda_vale.php');
            exit;
        } else {
            $erro = "Vale não encontrado ou já finalizado.";
        }
    } else {
        $erro = "Informe número do vale.";
    }
}

// 6. Adicionar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_produto'])) {
    $busca = trim($_POST['produto_busca'] ?? '');
    $qtd = (int)($_POST['quantidade'] ?? 0);
    if ($busca && $qtd > 0) {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = ? OR nome LIKE ? LIMIT 1");
        $stmt->execute([$busca, "%$busca%"]);
        if ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $p['id'];
            $estoque_disponivel = (int)$p['estoque'];
            $quantidade_no_carrinho = isset($carrinho[$id]) ? $carrinho[$id]['quantidade'] : 0;
            $quantidade_total = $quantidade_no_carrinho + $qtd;

            if ($quantidade_total > $estoque_disponivel) {
                $erro = "Estoque insuficiente! Disponível: {$estoque_disponivel}";
            } else {
                if (isset($carrinho[$id])) {
                    $carrinho[$id]['quantidade'] += $qtd;
                } else {
                    $carrinho[$id] = [
                        'id' => $p['id'],
                        'nome' => $p['nome'],
                        'preco' => $p['preco_venda'],
                        'quantidade' => $qtd
                    ];
                }
                $_SESSION['mensagem'] = "Produto adicionado.";
                header('Location: venda_vale.php');
                exit;
            }
        } else {
            $erro = "Produto não encontrado.";
        }
    } else {
        $erro = "Informe produto e quantidade.";
    }
}

// 7. Remover produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_produto'])) {
    $removerId = (int)$_POST['remover_produto'];
    if (isset($carrinho[$removerId])) {
        unset($carrinho[$removerId]);
        $_SESSION['mensagem'] = "Produto removido.";
    }
    header('Location: venda_vale.php');
    exit;
}

// View
include '../src/View/view_vale_formulario.php';
