<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$pdo = Database::conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {
    $id = intval($_POST['cliente_id']);
    if ($id > 0) {
        $_SESSION['cliente_id'] = $id;
        echo json_encode(['success' => true, 'message' => 'Cliente selecionado com sucesso!']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
}

// --- Se não for POST, trata como GET (buscar clientes)
$termo = trim($_GET['q'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, apelido, telefone FROM clientes WHERE nome LIKE ? OR telefone LIKE ? LIMIT 20");
    $likeTermo = "%$termo%";
    $stmt->execute([$likeTermo, $likeTermo]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);
} catch (Exception $e) {
    echo json_encode([]);
}
