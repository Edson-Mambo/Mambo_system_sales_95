<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin', 'gerente']);

$pdo = Database::conectar();

/**
 * LOG SAFE (evita erro fatal se writeLog não existir)
 */
function safeLog($pdo, $type, $action, $description)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, type, action, description, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $type,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

    } catch (Exception $e) {
        // nunca quebrar o scan por causa do log
    }
}

$tabelas = [
    "ajustes_estoque","carrinho_temp","categorias","clientes",
    "configuracoes","cotacao_itens","cotacoes","facturas",
    "fechos","fechos_dia","inventario_fisico","itens_vale",
    "itens_vendas_vales","itens_venda_teka_away","logs",
    "logs_login","logs_password_reset","logs_security",
    "logs_sistema","movimento_estoque","pagamentos_vale",
    "password_resets","password_reset_logs","produtos",
    "produtos_takeaway","produtos_vendidos",
    "produtos_vendidos_takeaway","recepcao_estoque",
    "usuarios","vales","vale_produtos","vendas",
    "vendas_takeaway","vendas_teka_away","vendas_vales"
];

$resultados = [];

foreach ($tabelas as $tabela) {

    try {

        // 1. verificar se tabela existe
        $check = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if ($check->rowCount() == 0) {
            $resultados[] = [
                "tabela" => $tabela,
                "status" => "MISSING",
                "total" => 0,
                "ultimo" => null
            ];

            safeLog($pdo, "ERROR", "SCAN_TABLE", "Tabela inexistente: $tabela");
            continue;
        }

        // 2. total registos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabela`");
        $total = $stmt->fetch()['total'] ?? 0;

        // 3. último registo (se existir coluna id ou created_at)
        $ultimo = null;

        try {
            $stmt2 = $pdo->query("
                SELECT * FROM `$tabela`
                ORDER BY 1 DESC
                LIMIT 1
            ");
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
            $ultimo = $row ? json_encode($row) : null;
        } catch (Exception $e) {
            $ultimo = "N/A";
        }

        // 4. status inteligente
        $status = "OK";

        if ($total == 0) {
            $status = "EMPTY";
        }

        if ($total > 0 && $total < 5) {
            $status = "LOW_DATA";
        }

        // 5. resultado
        $resultados[] = [
            "tabela" => $tabela,
            "status" => $status,
            "total" => $total,
            "ultimo" => $ultimo
        ];

        safeLog($pdo, "SYSTEM", "SCAN_OK", "$tabela OK ($total registos)");

    } catch (Exception $e) {

        $resultados[] = [
            "tabela" => $tabela,
            "status" => "ERROR",
            "total" => 0,
            "ultimo" => null
        ];

        safeLog($pdo, "ERROR", "SCAN_FAIL", "$tabela erro: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Auditoria do Sistema</title>

    <style>
        body { font-family: Arial; background:#f4f6f9; padding:20px; }

        .grid {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap:15px;
            margin-bottom:20px;
        }

        .card {
            background:white;
            padding:15px;
            border-radius:12px;
            box-shadow:0 4px 10px rgba(0,0,0,0.08);
        }

        .ok { color:green; font-weight:bold; }
        .empty { color:orange; font-weight:bold; }
        .low { color:#d97706; font-weight:bold; }
        .error { color:red; font-weight:bold; }
        .missing { color:#7f1d1d; font-weight:bold; }

        table { width:100%; border-collapse: collapse; background:white; }
        th, td { padding:10px; border:1px solid #ddd; font-size:14px; }
        th { background:#0d6efd; color:white; }
    </style>
</head>

<body>

<h2>🧠 Auditoria Completa do Sistema</h2>

<!-- RESUMO -->
<div class="grid">

    <div class="card">
        <h3><?= count($resultados) ?></h3>
        <p>Tabelas analisadas</p>
    </div>

    <div class="card">
        <h3><?= count(array_filter($resultados, fn($r)=>$r['status']=='ERROR')) ?></h3>
        <p>Erros detectados</p>
    </div>

    <div class="card">
        <h3><?= count(array_filter($resultados, fn($r)=>$r['status']=='EMPTY')) ?></h3>
        <p>Tabelas vazias</p>
    </div>

    <div class="card">
        <h3><?= count(array_filter($resultados, fn($r)=>$r['status']=='LOW_DATA')) ?></h3>
        <p>Baixa atividade</p>
    </div>

</div>

<!-- TABELA -->
<table>
<tr>
    <th>Tabela</th>
    <th>Status</th>
    <th>Total</th>
</tr>

<?php foreach ($resultados as $r): ?>
<tr>
    <td><?= $r['tabela'] ?></td>
    <td>
        <?php
        if ($r['status']=='OK') echo "<span class='ok'>OK</span>";
        if ($r['status']=='EMPTY') echo "<span class='empty'>VAZIA</span>";
        if ($r['status']=='LOW_DATA') echo "<span class='low'>BAIXA</span>";
        if ($r['status']=='ERROR') echo "<span class='error'>ERRO</span>";
        if ($r['status']=='MISSING') echo "<span class='missing'>NÃO EXISTE</span>";
        ?>
    </td>
    <td><?= $r['total'] ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>