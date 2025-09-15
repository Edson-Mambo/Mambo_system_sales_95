<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/configuracoes/logMiddleware.php';

// --------------------------
// Conexão com banco
$pdo = Database::conectar();

// --------------------------
// Funções utilitárias
function calcularTotal(array $itens): float {
    $soma = 0;
    foreach ($itens as $i) {
        $soma += $i['preco'] * $i['quantidade'];
    }
    return $soma;
}

function buscarProduto(PDO $pdo, string $busca) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = ? OR nome LIKE ? LIMIT 1");
    $stmt->execute([$busca, "%$busca%"]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function carregarVale(PDO $pdo, int $id_vale) {
    $stmt = $pdo->prepare("SELECT * FROM vales WHERE id = ? AND status != 'pago'");
    $stmt->execute([$id_vale]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function carregarItensVale(PDO $pdo, int $id_vale) {
    $stmt = $pdo->prepare("
        SELECT iv.produto_id, iv.quantidade, iv.preco_unitario AS preco, p.nome
        FROM itens_vale iv
        JOIN produtos p ON iv.produto_id = p.id
        WHERE iv.vale_id = ?
    ");
    $stmt->execute([$id_vale]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --------------------------
// Inicializar variáveis de sessão
if (!isset($_SESSION['numero_vale'])) {
    $_SESSION['numero_vale'] = rand(1000, 9999);
}
$numero_vale = $_SESSION['numero_vale'];

if (!isset($_SESSION['vale_carrinho'])) {
    $_SESSION['vale_carrinho'] = [];
}
$carrinho = &$_SESSION['vale_carrinho'];

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

// --------------------------
// 0. Carregar Vale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_vale'])) {
    $id_vale = (int)$_POST['id_vale'];
    if ($vale = carregarVale($pdo, $id_vale)) {
        $_SESSION['cliente_id'] = $vale['cliente_id'];
        $_SESSION['vale_carrinho'] = [];

        foreach (carregarItensVale($pdo, $id_vale) as $item) {
            $_SESSION['vale_carrinho'][$item['produto_id']] = [
                'id' => $item['produto_id'],
                'nome' => $item['nome'],
                'preco' => $item['preco'],
                'quantidade' => $item['quantidade']
            ];
        }

        $_SESSION['mensagem'] = "Vale #{$vale['numero_vale']} carregado para pagamento.";
        header('Location: venda_vale.php');
        exit;
    } else {
        $erro = "Vale não encontrado ou já finalizado.";
    }
}

// --------------------------
// 1. Salvar Vale (Ajax)
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

        // Buscar nome e telefone do cliente
        $stmtCli = $pdo->prepare("SELECT nome, telefone FROM clientes WHERE id = ?");
        $stmtCli->execute([$cliente_id_post]);
        $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

        $saldo = ($status === 'pago') ? 0 : $total_post;

        // Inserir vale
        $stmt = $pdo->prepare("
            INSERT INTO vales (cliente_id, numero_vale, cliente_nome, cliente_telefone, valor_total, status, saldo, data_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$cliente_id_post, $num, $cliente['nome'], $cliente['telefone'], $total_post, $status, $saldo]);

        $valeId = $pdo->lastInsertId();

        // Inserir itens e atualizar estoque
        $stmtItem = $pdo->prepare("INSERT INTO itens_vale (vale_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtBaixa = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");

        foreach ($carrinho as $it) {
            $stmtItem->execute([$valeId, $it['id'], $it['quantidade'], $it['preco']]);
            $stmtBaixa->execute([$it['quantidade'], $it['id']]);
        }

        $_SESSION['vale_carrinho'] = [];
        unset($_SESSION['numero_vale']);

        echo json_encode(['success' => true, 'message' => "Vale #{$num} salvo com sucesso!"]);
    } else {
        echo json_encode(['success' => false, 'error' => "Selecione cliente e adicione produtos."]);
    }
    exit;
}

// --------------------------
// 2. Buscar Cliente
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

// --------------------------
// 3. Selecionar Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selecionar_cliente_id'])) {
    $_SESSION['cliente_id'] = (int)$_POST['selecionar_cliente_id'];
    header('Location: venda_vale.php');
    exit;
}

// --------------------------
// 4. Cadastrar Cliente
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

// --------------------------
// 5. Buscar Vale pelo Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_vale_cliente'])) {
    $nomeCliente = trim($_POST['nome_cliente'] ?? '');
    if ($nomeCliente !== '') {
        $stmt = $pdo->prepare("SELECT * FROM vales WHERE cliente_nome LIKE ? AND status != 'pago'");
        $stmt->execute(["%$nomeCliente%"]);
        $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($vales) === 0) {
            $erro = "Nenhum vale encontrado para o cliente informado.";
        } elseif (count($vales) === 1) {
            $vale = $vales[0];
            $_SESSION['cliente_id'] = $vale['cliente_id'];
            $_SESSION['vale_carrinho'] = [];

            foreach (carregarItensVale($pdo, $vale['id']) as $it) {
                $_SESSION['vale_carrinho'][$it['produto_id']] = [
                    'id' => $it['produto_id'],
                    'nome' => $it['nome'],
                    'preco' => $it['preco'],
                    'quantidade' => $it['quantidade']
                ];
            }

            $_SESSION['mensagem'] = "Vale de {$vale['cliente_nome']} carregado.";
            header('Location: venda_vale.php');
            exit;
        } else {
            $_SESSION['vales_encontrados'] = $vales;
            header('Location: venda_vale.php');
            exit;
        }
    } else {
        $erro = "Digite o nome do cliente para buscar o vale.";
    }
}

// --------------------------
// 6. Adicionar Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_produto'])) {
    $busca = trim($_POST['produto_busca'] ?? '');
    $qtd = (int)($_POST['quantidade'] ?? 0);
    if ($busca && $qtd > 0) {
        if ($p = buscarProduto($pdo, $busca)) {
            $id = $p['id'];
            $estoque_disponivel = (int)$p['estoque'];
            $quantidade_no_carrinho = $carrinho[$id]['quantidade'] ?? 0;

            if (($quantidade_no_carrinho + $qtd) > $estoque_disponivel) {
                $erro = "Estoque insuficiente! Disponível: {$estoque_disponivel}";
            } else {
                if (isset($carrinho[$id])) {
                    $carrinho[$id]['quantidade'] += $qtd;
                } else {
                    $carrinho[$id] = [
                        'id' => $p['id'],
                        'nome' => $p['nome'],
                        'preco' => $p['preco'],
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

// --------------------------
// 7. Remover Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_produto'])) {
    $removerId = (int)$_POST['remover_produto'];
    if (isset($carrinho[$removerId])) {
        unset($carrinho[$removerId]);
        $_SESSION['mensagem'] = "Produto removido.";
    }
    header('Location: venda_vale.php');
    exit;
}

// --------------------------
// Atualiza total antes de carregar a view
$total = calcularTotal($carrinho);

// --------------------------
// Carregar view
include '../src/View/view_vale_formulario.php';
