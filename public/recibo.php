<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/empresa.php';
require_once __DIR__ . '/../impressao/ImpressoraFactory.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = Database::conectar();
    $config = getConfigEmpresa($pdo);

    $venda_id = $_GET['venda_id'] ?? null;
    if (!$venda_id) {
        throw new Exception("Venda inválida");
    }

    /* =========================
       FUNÇÃO ENDEREÇO EMPRESA
    ========================= */
   function enderecoEmpresa($config)
{
    $partes = [];

    if (!empty($config['rua_avenida'])) {
        $partes[] = $config['rua_avenida'];
    }

    if (!empty($config['bairro'])) {
        $partes[] = $config['bairro'];
    }

    if (!empty($config['cidade'])) {
        $partes[] = $config['cidade'];
    }

    if (!empty($config['provincia'])) {
        $partes[] = $config['provincia'];
    }

    // fallback para campo antigo
    if (empty($partes) && !empty($config['endereco'])) {
        $partes[] = $config['endereco'];
    }

    return implode(", ", $partes);
}

    /* =========================
       VENDA + CLIENTE + OPERADOR
    ========================= */
    $stmt = $pdo->prepare("
        SELECT 
            v.*,

            c.nome AS cliente_nome,
            c.apelido AS cliente_apelido,
            c.telefone AS cliente_telefone,
            c.email AS cliente_email,
            c.morada AS cliente_morada,
            c.nuit AS cliente_nuit,

            u.nome AS operador_nome

        FROM vendas v
        LEFT JOIN clientes c ON c.id = v.cliente_id
        LEFT JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.id = ?
    ");

    $stmt->execute([$venda_id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        throw new Exception("Venda não encontrada");
    }

    /* =========================
       PRODUTOS
    ========================= */
    $stmt = $pdo->prepare("
        SELECT pv.*, p.nome
        FROM produtos_vendidos pv
        JOIN produtos p ON p.id = pv.produto_id
        WHERE pv.venda_id = ?
    ");
    $stmt->execute([$venda_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$produtos) {
        throw new Exception("Sem produtos");
    }

    /* =========================
       CLIENTE FORMATADO
    ========================= */
    $cliente_nome = trim(
        ($venda['cliente_nome'] ?? '') . ' ' . ($venda['cliente_apelido'] ?? '')
    );

    if ($cliente_nome === '') {
        $cliente_nome = "Cliente Geral";
    }

    $cliente_tel    = $venda['cliente_telefone'] ?? '-';
    $cliente_email  = $venda['cliente_email'] ?? '-';
    $cliente_morada = $venda['cliente_morada'] ?? '-';
    $cliente_nuit   = $venda['cliente_nuit'] ?? '-';

    $operador_nome = $venda['operador_nome'] ?? 'Sistema';

   /* =========================
   IMPRESSORA
    ========================= */
    $printer = ImpressoraFactory::criar($config);

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text(($config['nome_empresa'] ?? 'Empresa') . "\n");
    $printer->setEmphasis(false);

    $printer->text(enderecoEmpresa($config) . "\n");
    $printer->text("Tel: " . ($config['telefone'] ?? '-') . "\n");
    $printer->text("Email: " . ($config['email_empresa'] ?? '-') . "\n");
    $printer->text("NUIT: " . ($config['nuit_empresa'] ?? '-') . "\n");

    $printer->text("----------------------\n");
    $printer->text("RECIBO #$venda_id\n");
    $printer->text("----------------------\n");

    /* =========================
       CLIENTE
    ========================= */
    $printer->setJustification(Printer::JUSTIFY_LEFT);

    $printer->text("CLIENTE\n");
    $printer->text("Nome: $cliente_nome\n");
    $printer->text("Tel: $cliente_tel\n");
    $printer->text("Email: $cliente_email\n");
    $printer->text("Morada: $cliente_morada\n");
    $printer->text("NUIT: $cliente_nuit\n");
    $printer->text("----------------------\n");

    /* =========================
       PRODUTOS
    ========================= */
    $total = 0;

    foreach ($produtos as $p) {

        $nome = mb_strimwidth($p['nome'], 0, 20, '...');
        $qtd = (int)$p['quantidade'];
        $preco = (float)$p['preco_unitario'];
        $sub = $qtd * $preco;

        $total += $sub;

        $printer->text("$nome\n");
        $printer->text("Qtd: $qtd | " . number_format($sub, 2, ',', '.') . "\n");
    }

    $printer->text("----------------------\n");

    /* =========================
       TOTAL + OPERADOR
    ========================= */
    $printer->setEmphasis(true);
    $printer->text("TOTAL: " . number_format($total, 2, ',', '.') . " MT\n");
    $printer->setEmphasis(false);

    $printer->text("----------------------\n");
    $printer->text("OPERADOR: $operador_nome\n");
    $printer->text("----------------------\n");

    $printer->feed(2);
    $printer->text($config['mensagem_rodape'] ?? "Obrigado pela compra!");
    $printer->feed(2);

    $printer->cut();
    $printer->close();

    echo json_encode([
        "success" => true,
        "venda_id" => $venda_id
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "erro" => $e->getMessage()
    ]);
}