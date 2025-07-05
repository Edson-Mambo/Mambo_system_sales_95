<?php
// mostrar_vale.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$pdo = Database::conectar();

// Verifica se o ID do vale foi fornecido
if (!isset($_GET['id_vale']) || empty($_GET['id_vale'])) {
    die("❌ ID do vale não fornecido.");
}

$idVale = $_GET['id_vale'];

// Buscar dados do vale com os dados do cliente
$stmtVale = $pdo->prepare("SELECT v.*, c.nome AS cliente_nome, c.telefone 
                           FROM vales v 
                           LEFT JOIN clientes c ON v.cliente_id = c.id 
                           WHERE v.id = ?");
$stmtVale->execute([$idVale]);
$vale = $stmtVale->fetch(PDO::FETCH_ASSOC);

// Verifica se encontrou o vale
if (!$vale) {
    die("❌ Vale não encontrado.");
}

// Buscar produtos associados ao vale
$stmtProdutos = $pdo->prepare("SELECT * FROM vale_produtos WHERE vale_id = ?");
$stmtProdutos->execute([$idVale]);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Inclui a view para exibir os dados do vale
include '../src/View/view_vale_formulario.php';
