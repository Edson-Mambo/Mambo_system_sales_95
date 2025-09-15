<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF

use TCPDF;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valorRecebido = floatval($_POST['valor_recebido'] ?? 0);

    if ($valorRecebido <= 0 || !isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Carrinho vazio ou valor inválido.']);
        exit;
    }

    $produtosVendidos = $_SESSION['carrinho'];
    $usuario_id = $_SESSION['usuario']['id'] ?? 0;
    $numero_recibo = 'R' . time();
    $totalVenda = 0;

    foreach ($produtosVendidos as $produto) {
        $totalVenda += $produto['quantidade'] * $produto['preco'];
    }

    if ($valorRecebido < $totalVenda) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Valor recebido é menor que o total da venda.']);
        exit;
    }

    $troco = $valorRecebido - $totalVenda;
    $dataHora = date('Y-m-d H:i:s');

    // Grava a venda
    $stmt = $pdo->prepare("INSERT INTO vendas (usuario_id, numero_recibo, total, valor_recebido, troco, data_hora) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $numero_recibo, $totalVenda, $valorRecebido, $troco, $dataHora]);
    $venda_id = $pdo->lastInsertId();

    // Grava os produtos vendidos e atualiza estoque
    foreach ($produtosVendidos as $produto) {
        $produto_id = $produto['id'];
        $quantidade = $produto['quantidade'];
        $preco = $produto['preco'];
        $subtotal = $quantidade * $preco;

        $stmtItem = $pdo->prepare("INSERT INTO produtos_vendidos (venda_id, produto_id, quantidade, preco_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)");
        $stmtItem->execute([$venda_id, $produto_id, $quantidade, $preco, $subtotal]);

        $stmtEstoque = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmtEstoque->execute([$quantidade, $produto_id]);

        $stmtMov = $pdo->prepare("INSERT INTO movimento_estoque (produto_id, tipo, quantidade, data_hora, usuario_id, observacao)
            VALUES (?, 'saida', ?, ?, ?, ?)");
        $stmtMov->execute([$produto_id, $quantidade, $dataHora, $usuario_id, "Venda Nº $numero_recibo"]);
    }

    unset($_SESSION['cliente_id']);
    unset($_SESSION['carrinho']);

    // ========================
    // 1) GERAR PDF DO RECIBO
    // ========================
    $pdf = new TCPDF('P', 'mm', [80, 200], true, 'UTF-8', false);
    $pdf->SetMargins(2, 2, 2);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $html = "<h3 style='text-align:center;'>RECIBO</h3>";
    $html .= "<p>Nº Recibo: $numero_recibo<br>Data: $dataHora</p>";
    $html .= "<table width='100%' border='1' cellspacing='0' cellpadding='3'>";
    $html .= "<tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Total</th></tr>";

    foreach ($produtosVendidos as $produto) {
        $nome = $produto['nome'];
        $qtd = $produto['quantidade'];
        $preco = number_format($produto['preco'], 2, ',', '.');
        $sub = number_format($qtd * $produto['preco'], 2, ',', '.');
        $html .= "<tr><td>$nome</td><td>$qtd</td><td>$preco</td><td>$sub</td></tr>";
    }

    $html .= "</table>";
    $html .= "<p><b>Total: " . number_format($totalVenda, 2, ',', '.') . " MZN</b></p>";
    $html .= "<p>Recebido: " . number_format($valorRecebido, 2, ',', '.') . " MZN</p>";
    $html .= "<p>Troco: " . number_format($troco, 2, ',', '.') . " MZN</p>";

    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Salvar PDF em pasta temporária
    $pdfPath = __DIR__ . "/../public/recibos/recibo_$venda_id.pdf";
    $pdf->Output($pdfPath, 'F');

    // ========================
    // 2) IMPRIMIR NO WINDOWS
    // ========================
    $nomeImpressora = "BIXOLON SRP-350"; // verifique nome exato no Painel de Controle
    $acrobatPath = '"C:\\Program Files (x86)\\Adobe\\Acrobat Reader DC\\Reader\\AcroRd32.exe"';
    $comando = "START /MIN $acrobatPath /t \"$pdfPath\" \"$nomeImpressora\"";

    exec($comando);

    // Resposta em JSON
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Venda finalizada e recibo enviado para impressão',
        'pdf' => "recibos/recibo_$venda_id.pdf"
    ]);
    exit;
}
