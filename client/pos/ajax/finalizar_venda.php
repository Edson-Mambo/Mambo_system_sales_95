<?php

ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/VendaService.php';
require_once __DIR__ . '/../../services/CarrinhoService.php';
require_once __DIR__ . '/../../services/ReciboImpressaoService.php';

function responder(array $data, int $httpCode = 200): void
{
    ob_end_clean();
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   LOG HELPER
========================= */
function logVenda($msg, $data = null)
{
    error_log("[VENDA] " . $msg . ($data ? " | " . json_encode($data) : ""));
}

try {

    /* =========================
       AUTENTICAÇÃO
    ========================= */
    verificarCaixa();

    $usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

    if ($usuario_id <= 0) {
        responder([
            'success' => false,
            'message' => 'Sessão inválida ou expirada'
        ], 401);
    }

    /* =========================
       INPUT
    ========================= */
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        logVenda("JSON inválido", $rawInput);
        responder([
            'success' => false,
            'message' => 'Dados inválidos enviados ao servidor'
        ], 400);
    }

    /* =========================
       NORMALIZAÇÃO DO MÉTODO
    ========================= */
    $metodoRaw = $input['metodo_pagamento'] ?? '';

    $metodoPagamento = strtolower(trim($metodoRaw));
    $metodoPagamento = str_replace([' ', '-', '_'], '', $metodoPagamento);

    $mapaMetodos = [
        'dinheiro' => 'dinheiro',
        'mpesa'    => 'mpesa',
        'm-pesa'   => 'mpesa',
        'emola'    => 'emola',
        'emola'    => 'emola',
        'cartao'   => 'cartao',
        'cartão'   => 'cartao',
        'credito'  => 'credito'
    ];

    if (!isset($mapaMetodos[$metodoPagamento])) {
        logVenda("Método inválido", $metodoRaw);

        responder([
            'success' => false,
            'message' => 'Método de pagamento inválido',
            'debug'   => [
                'recebido' => $metodoRaw,
                'esperado' => array_keys($mapaMetodos)
            ]
        ], 422);
    }

    $metodoPagamento = $mapaMetodos[$metodoPagamento];

    /* =========================
       CARRINHO
    ========================= */
    $carrinhoService = new CarrinhoService();
    $carrinho = $carrinhoService->listar();

    if (!is_array($carrinho) || count($carrinho) === 0) {
        logVenda("Carrinho vazio");

        responder([
            'success' => false,
            'message' => 'Carrinho vazio — adicione produtos antes de finalizar'
        ], 422);
    }

    /* =========================
       SESSÃO SEGURA
    ========================= */
    $cliente_id = $_SESSION['cliente_id'] ?? null;
    $sessao_id  = $_SESSION['sessao_id'] ?? ($_SESSION['abertura_id'] ?? null);

    /* =========================
       MONTAR DADOS
    ========================= */
    $dados = [
        'metodo_pagamento'  => $metodoPagamento,
        'desconto'          => $input['desconto'] ?? false,
        'valor_pago'        => (float)($input['valor_pago'] ?? 0),

        'numero_referencia' => $input['numero_referencia']
                               ?? $input['numero_autorizacao']
                               ?? null,

        'observacao'        => $input['observacao'] ?? null,

        'cliente_id'        => $cliente_id,
        'sessao_id'         => $sessao_id,

        'itens'             => $carrinho,
    ];

    logVenda("Finalizando venda", [
        'usuario' => $usuario_id,
        'metodo'  => $metodoPagamento,
        'itens'   => count($carrinho)
    ]);

    /* =========================
       EXECUTAR VENDA
    ========================= */
    $pdoLocal = Database::conectarLocal();
    $vendaService = new VendaService($pdoLocal);

    $result = $vendaService->finalizar($dados, $usuario_id);

    if (!is_array($result) || empty($result['success'])) {
        logVenda("Erro no service", $result);

        responder($result ?: [
            'success' => false,
            'message' => 'Erro ao processar venda'
        ], 422);
    }

    /* =========================
       LIMPAR CARRINHO
    ========================= */
    $carrinhoService->limpar();

    /* =========================
       IMPRESSÃO (NÃO BLOQUEIA)
    ========================= */
    try {

        $config = [];

        $stmt = $pdoLocal->query("SELECT chave, valor FROM configuracoes");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $config[$row['chave']] = $row['valor'];
            }
        }

        try {
            $pdoRemoto = Database::conectarRemoto();

            $cfg = $pdoRemoto->query("SELECT * FROM configuracoes_empresa LIMIT 1")
                             ->fetch(PDO::FETCH_ASSOC);

            if (is_array($cfg)) {
                $config = array_merge($config, $cfg);
            }

        } catch (Throwable $e) {
            // offline OK
        }

        ReciboImpressaoService::imprimir(
            $result['venda_id'],
            $pdoLocal,
            $config
        );

    } catch (Throwable $e) {
        logVenda("Erro impressão", $e->getMessage());
    }

    /* =========================
       RESPOSTA FINAL
    ========================= */
    responder([
        'success'          => true,
        'venda_id'         => $result['venda_id'] ?? null,
        'uuid'             => $result['uuid'] ?? null,
        'subtotal'         => $result['subtotal'] ?? 0,
        'desconto'         => $result['desconto'] ?? 0,
        'imposto'          => $result['imposto'] ?? 0,
        'total'            => $result['total'] ?? 0,
        'troco'            => $result['troco'] ?? 0,
        'metodo_pagamento' => $metodoPagamento
    ]);

} catch (Throwable $e) {

    logVenda("ERRO FATAL", $e->getMessage());

    responder([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error'   => $e->getMessage()
    ], 500);
}