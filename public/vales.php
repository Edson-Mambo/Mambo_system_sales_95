
<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();


$isAjaxSave = ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_save']));
if ($isAjaxSave) {
    // executa toda a lógica de inserção do vale (sem header nem exit)
    if ($cliente_id && $total>0) {
        // ... inserir vale e itens ...
        echo json_encode(['success'=>true, 'message'=>"Vale #{$num} salvo com sucesso!"]);
    } else {
        echo json_encode(['success'=>false, 'error'=>"Selecione um cliente e adicione produtos."]);
    }
    exit;
}

// 1️⃣ Inicia carrinho de vale
if (!isset($_SESSION['vale_carrinho'])) {
    $_SESSION['vale_carrinho'] = [];
}
$carrinho = &$_SESSION['vale_carrinho'];

// Variáveis de mensagem
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
$erro = '';

// Recupera cliente selecionado para exibição
$clienteSelecionadoNome = 'Nenhum cliente selecionado';
$cliente_id = $_SESSION['cliente_id'] ?? null;
if ($cliente_id) {
    $stmtCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmtCli->execute([$cliente_id]);
    if ($cli = $stmtCli->fetch(PDO::FETCH_ASSOC)) {
        $clienteSelecionadoNome = $cli['nome'];
    }
}

// Processa ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔍 Buscar cliente
    if (isset($_POST['buscar_cliente'])) {
        $busca = trim($_POST['cliente_nome_ou_telefone'] ?? '');
        if ($busca !== '') {
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR telefone LIKE ? LIMIT 1");
            $stmt->execute(["%$busca%", "%$busca%"]);
            if ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['cliente_id'] = $cli['id'];
                $clienteSelecionadoNome = $cli['nome'];
            } else {
                $erro = "Cliente não encontrado.";
            }
        } else {
            $erro = "Digite nome ou telefone.";
        }
    }

    // ➕ Cadastrar cliente
    if (isset($_POST['cadastrar_cliente'])) {
        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        if ($nome && $telefone) {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
            $stmt->execute([$telefone]);
            if ($stmt->fetch()) {
                $erro = "Telefone já cadastrado.";
            } else {
                $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)")
                    ->execute([$nome, $telefone]);
                $_SESSION['cliente_id'] = $pdo->lastInsertId();
                $clienteSelecionadoNome = $nome;
                $mensagem = "Cliente cadastrado com sucesso!";
            }
        } else {
            $erro = "Preencha nome e telefone.";
        }
    }

    // 🛒 Adicionar produto
    if (isset($_POST['adicionar_produto'])) {
        $busca = trim($_POST['produto_busca'] ?? '');
        $qtd   = (int)($_POST['quantidade'] ?? 0);
        if ($busca && $qtd > 0) {
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barra = ? OR nome LIKE ? LIMIT 1");
            $stmt->execute([$busca, "%$busca%"]);
            if ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = $p['id'];
                if (isset($carrinho[$key])) {
                    $carrinho[$key]['quantidade'] += $qtd;
                } else {
                    $carrinho[$key] = [
                        'id'         => $p['id'],
                        'nome'       => $p['nome'],
                        'preco'      => $p['preco'],
                        'quantidade' => $qtd,
                    ];
                }
                $mensagem = "Produto adicionado: {$p['nome']}";
            } else {
                $erro = "Produto não encontrado.";
            }
        } else {
            $erro = "Informe produto e quantidade.";
        }
    }

    // ❌ Remover produto
    if (isset($_POST['remover_produto'])) {
        unset($carrinho[(int)$_POST['remover_produto']]);
        $mensagem = "Produto removido do vale.";
    }

    // Função para total
    function calcularTotal($itens) {
        $s = 0;
        foreach ($itens as $i) {
            $s += $i['preco'] * $i['quantidade'];
        }
        return $s;
    }
    $total = calcularTotal($carrinho);

    // 💾 Salvar vale (permanece na mesma página)
    if (isset($_POST['salvar_vale'])) {
        $cliente_id = $_POST['cliente_id'] ?? $_SESSION['cliente_id'] ?? null;
        $status     = $_POST['status_pagamento'] ?? 'aberto';

        if ($cliente_id && $total > 0) {
            // Gera número único
            do {
                $num = rand(1000, 9999);
                $chk = $pdo->prepare("SELECT 1 FROM vales WHERE numero_vale = ?");
                $chk->execute([$num]);
            } while ($chk->fetch());

            // Insere cabeçalho do vale
            $stmt = $pdo->prepare(
                "INSERT INTO vales
                  (cliente_id, numero_vale, cliente_nome, cliente_telefone, valor_total, status, saldo, data_registro)
                 SELECT ?, ?, nome, telefone, ?, ?, ?, NOW() FROM clientes WHERE id = ?"
            );
            $stmt->execute([$cliente_id, $num, $total, $status, $total, $cliente_id]);
            $vale_id = $pdo->lastInsertId();

            // Insere itens
            $stmtItem = $pdo->prepare(
                "INSERT INTO itens_vale
                  (vale_id, produto_id, quantidade, preco_unitario)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($carrinho as $it) {
                $stmtItem->execute([$vale_id, $it['id'], $it['quantidade'], $it['preco']]);
            }

            // Limpa carrinho, mantém cliente selecionado
            $_SESSION['vale_carrinho'] = [];
            $_SESSION['mensagem'] = "Vale #{$num} salvo com sucesso!";
            $mensagem = $_SESSION['mensagem'];
            $total = 0;
        } else {
            $erro = "Selecione um cliente e adicione produtos para salvar o vale.";
        }
    }
}


// Inclui view
include '../src/View/view_vale_formulario.php';
?>

