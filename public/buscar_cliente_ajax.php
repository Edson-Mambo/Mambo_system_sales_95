<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$pdo = Database::conectar();

/* =========================
   SELECIONAR CLIENTE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {

    $id = intval($_POST['cliente_id']);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    // validar cliente existe
    $stmt = $pdo->prepare("SELECT id, nome, apelido FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        exit;
    }

    // guardar na sessão
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'] . ' ' . $cliente['apelido'];

    echo json_encode([
        'success' => true,
        'cliente' => $cliente
    ]);
    exit;
}

/* =========================
   BUSCAR CLIENTE (AJAX)
========================= */
$termo = trim($_GET['q'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, apelido, telefone, email, morada
    FROM clientes
    WHERE nome LIKE ? 
       OR telefone LIKE ?
       OR apelido LIKE ?
    ORDER BY nome ASC
    LIMIT 20
");

$like = "%$termo%";
$stmt->execute([$like, $like, $like]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));