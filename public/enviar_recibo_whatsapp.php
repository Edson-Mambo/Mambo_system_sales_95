<?php
// Ativa exibição de erros para debug (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define o tipo da resposta como JSON
header('Content-Type: application/json');

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}


// Recebe via POST
$recibo_id = $_POST['recibo_id'] ?? null;
$numero_whatsapp = $_POST['numero_whatsapp'] ?? null;

if (!$recibo_id || !$numero_whatsapp) {
    echo json_encode([
        'success' => false,
        'message' => 'Número do recibo e número WhatsApp são obrigatórios.'
    ]);
    exit;
}

// Valide o número WhatsApp recebido (exemplo simples, só números)
$numero_whatsapp = preg_replace('/[^0-9]/', '', $numero_whatsapp);

if (strlen($numero_whatsapp) < 9) {
    echo json_encode([
        'success' => false,
        'message' => 'Número WhatsApp inválido.'
    ]);
    exit;
}

// Buscar dados do recibo no banco (conforme você já faz)

// Verifica se o número do recibo foi enviado
$recibo_id = $_POST['recibo_id'] ?? null;
if (!$recibo_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Número do recibo é obrigatório.'
    ]);
    exit;
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = Database::conectar();

    // Consulta os dados do recibo e cliente
    $sql = "SELECT v.id, v.data, c.nome AS cliente_nome, c.telefone, v.total
            FROM vendas v
            JOIN clientes c ON v.cliente_id = c.id
            WHERE v.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recibo_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        echo json_encode([
            'success' => false,
            'message' => 'Recibo não encontrado.'
        ]);
        exit;
    }

    // Formata telefone para WhatsApp: só números, formato internacional sem zeros à esquerda
    $telefone_cliente = preg_replace('/[^0-9]/', '', $dados['telefone']);
    if (strlen($telefone_cliente) < 9) {
        echo json_encode([
            'success' => false,
            'message' => 'Número de telefone do cliente inválido.'
        ]);
        exit;
    }

    // Monta mensagem personalizada
    $mensagem = "Olá {$dados['cliente_nome']}, obrigado pela sua compra!\n";
    $mensagem .= "Recibo Nº: {$dados['id']}\n";
    $mensagem .= "Data: {$dados['data']}\n";
    $mensagem .= "Total: MZN " . number_format($dados['total'], 2) . "\n";
    $mensagem .= "Qualquer dúvida, estamos à disposição.";

    // Aqui você deve integrar sua API de envio real.
    // Por enquanto vamos simular sucesso no envio.
    $enviar_sucesso = true; // coloque false para simular erro

    if ($enviar_sucesso) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso pelo WhatsApp!',
            'telefone' => $telefone_cliente,
            'mensagem' => $mensagem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao enviar mensagem via WhatsApp.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao consultar a base de dados: ' . $e->getMessage()
    ]);
}

exit;
