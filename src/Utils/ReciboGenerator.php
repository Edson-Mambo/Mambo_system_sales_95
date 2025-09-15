<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$venda_id = $_GET['venda_id'] ?? null;
if (!$venda_id) {
    http_response_code(400);
    exit(json_encode(["success" => false, "erro" => "Venda não encontrada."]));
}

// Buscar venda
$stmtVenda = $pdo->prepare("SELECT v.*, u.nome AS nome_usuario 
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.id = ?");
$stmtVenda->execute([$venda_id]);
$venda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    http_response_code(404);
    exit(json_encode(["success" => false, "erro" => "Venda não encontrada."]));
}

// Buscar produtos
$stmtProdutos = $pdo->prepare("SELECT pv.*, p.nome AS nome_produto 
    FROM produtos_vendidos pv
    LEFT JOIN produtos p ON pv.produto_id = p.id
    WHERE pv.venda_id = ?");
$stmtProdutos->execute([$venda_id]);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

try {
    // ⚠️ Nome EXATO da impressora no Windows
    $connector = new WindowsPrintConnector("BIXOLON SRP-350");
    $printer   = new Printer($connector);

    // --- Cabeçalho ---
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("Mambo System Sales 95\n");
    $printer->setEmphasis(false);
    $printer->text("Recibo de Venda\n");
    $printer->text("Nº " . $venda['numero_recibo'] . "\n");
    $printer->text(date("d/m/Y H:i:s", strtotime($venda['data_hora'])) . "\n");
    $printer->text("Operador: " . $venda['nome_usuario'] . "\n");
    $printer->text("--------------------------------\n");

    // --- Produtos ---
    foreach ($produtos as $item) {
        $nome     = substr($item['nome_produto'], 0, 20);
        $qtd      = $item['quantidade'];
        $subtotal = number_format($item['subtotal'], 2, ',', '.');

        $line = str_pad($nome, 20)
              . str_pad($qtd, 5, " ", STR_PAD_LEFT)
              . str_pad($subtotal, 10, " ", STR_PAD_LEFT) . "\n";

        $printer->text($line);
    }

    $printer->text("--------------------------------\n");

    // --- Totais ---
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("TOTAL: MT " . number_format($venda['total'], 2, ',', '.') . "\n");
    $printer->text("Recebido: MT " . number_format($venda['valor_recebido'], 2, ',', '.') . "\n");
    $printer->text("Troco: MT " . number_format($venda['troco'], 2, ',', '.') . "\n");

    // --- Mensagem final ---
    $printer->feed(2);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Obrigado pela preferência!\n");

    // --- Corte ---
    $printer->cut();
    $printer->close();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "erro" => $e->getMessage()]);
}
