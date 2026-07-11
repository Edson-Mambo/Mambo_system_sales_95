<?php

ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/ResumoCaixaImpressaoService.php';

try {

    verificarCaixa();

    /* =========================
       SESSÃO
    ========================= */
    $abertura_id = $_SESSION['abertura_id'] ?? null;
    $usuario_id  = $_SESSION['usuario_id']  ?? null;

    if (!$abertura_id || !$usuario_id) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Sessão inválida. Faça login novamente.'
        ]);
        exit;
    }

    /* =========================
       CONEXÕES
    ========================= */
    $pdoLocal  = Database::conectarLocal();
    $pdoRemoto = Database::conectarRemoto();

    /* =========================
       VERIFICA SE CAIXA ESTÁ ABERTO
    ========================= */
    $stmt = $pdoLocal->prepare("
        SELECT id FROM abertura_caixa
        WHERE id = ? AND status = 'aberto'
        LIMIT 1
    ");
    $stmt->execute([$abertura_id]);

    if (!$stmt->fetch()) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Caixa já está fechado ou não encontrado.'
        ]);
        exit;
    }

    /* =========================
       VERIFICA SE HÁ VENDAS PENDENTES
       (opcional — comente se não quiser bloquear)
    ========================= */
    // $stmtPendente = $pdoLocal->prepare("
    //     SELECT COUNT(*) FROM vendas
    //     WHERE abertura_id = ? AND status = 'pendente'
    // ");
    // $stmtPendente->execute([$abertura_id]);
    // if ((int)$stmtPendente->fetchColumn() > 0) {
    //     ob_clean();
    //     echo json_encode(['success'=>false,'message'=>'Existem vendas pendentes.']);
    //     exit;
    // }

    /* =========================
       FECHAR CAIXA
    ========================= */
    $stmtFechar = $pdoLocal->prepare("
        UPDATE abertura_caixa
        SET status       = 'fechado',
            data_fecho   = datetime('now','localtime'),
            usuario_fecho = ?
        WHERE id = ?
    ");
    $stmtFechar->execute([$usuario_id, $abertura_id]);

    /* =========================
       LIMPAR SESSÃO DO CAIXA
    ========================= */
    unset($_SESSION['abertura_id'], $_SESSION['cliente_id']);

    /* =========================
       IMPRESSÃO DO RESUMO
       Falhar aqui NÃO desfaz o fecho — só loga o erro
    ========================= */
    try {

        $stmtCfg = $pdoRemoto->query("SELECT * FROM configuracoes_empresa LIMIT 1");
        $config  = $stmtCfg->fetch(PDO::FETCH_ASSOC) ?: [];

        ResumoCaixaImpressaoService::imprimir(
            $abertura_id,
            $usuario_id,
            $pdoLocal,
            $config
        );

    } catch (Throwable $eImp) {
        error_log("ERRO IMPRESSÃO RESUMO CAIXA abertura_id={$abertura_id}: " . $eImp->getMessage());
    }

    /* =========================
       RESPOSTA
    ========================= */
    ob_clean();
    echo json_encode([
        'success'     => true,
        'message'     => 'Caixa fechado com sucesso.',
        'abertura_id' => $abertura_id,
        'redirect'    => '/Mambo_system_sales_95/client/pos/abrir_caixa.php'
    ]);

} catch (Throwable $e) {

    ob_clean();
    error_log("ERRO FECHAR CAIXA: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao fechar caixa.',
        'debug'   => $e->getMessage() // remova em produção
    ]);
}