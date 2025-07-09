<?php
session_start();

require_once '../../config/database.php';

$pdo = Database::conectar();

// Verificar permissão
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], ['admin', 'gerente'])) {
    header("Location: ../login.php");
    exit();
}

// Filtros opcionais
$tipoFiltro = $_GET['tipo'] ?? '';
$dataDe = $_GET['data_de'] ?? '';
$dataAte = $_GET['data_ate'] ?? '';

// Construção da query
$sql = "SELECT * FROM logs_sistema WHERE 1=1";
$params = [];

// Filtro por tipo
if (!empty($tipoFiltro)) {
    $sql .= " AND tipo_log = :tipo_log";
    $params[':tipo_log'] = $tipoFiltro;
}

// Filtro por período
if (!empty($dataDe)) {
    $sql .= " AND data_hora >= :data_de";
    $params[':data_de'] = $dataDe . ' 00:00:00';
}
if (!empty($dataAte)) {
    $sql .= " AND data_hora <= :data_ate";
    $params[':data_ate'] = $dataAte . ' 23:59:59';
}

$sql .= " ORDER BY data_hora DESC LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Relatório de Logs do Sistema</title>
    <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4 bg-light">
<div class="container">
    <h2 class="mb-4">📋 Relatório Completo de Logs do Sistema</h2>

    <!-- Filtros -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-2">
            <label for="tipo" class="form-label">Tipo</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="">Todos</option>
                <option value="INFO" <?= $tipoFiltro === 'INFO' ? 'selected' : '' ?>>INFO</option>
                <option value="WARNING" <?= $tipoFiltro === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                <option value="ERROR" <?= $tipoFiltro === 'ERROR' ? 'selected' : '' ?>>ERROR</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="data_de" class="form-label">De</label>
            <input type="date" id="data_de" name="data_de" class="form-control" value="<?= htmlspecialchars($dataDe) ?>">
        </div>
        <div class="col-md-2">
            <label for="data_ate" class="form-label">Até</label>
            <input type="date" id="data_ate" name="data_ate" class="form-control" value="<?= htmlspecialchars($dataAte) ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <div class="col-md-2 align-self-end">
            <a href="relatorio_logs.php" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

    <?php if (count($logs) === 0): ?>
        <div class="alert alert-warning">Nenhum log encontrado com os filtros aplicados.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>IP</th>
                        <th>Rota</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                    $dt = $log['data_hora'] ?? null;
                                    echo $dt ? date('d/m/Y H:i:s', strtotime($dt)) : '—';
                                ?>
                            </td>
                            <td><?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></td>
                            <td>
                                <span class="badge bg-<?= ($log['tipo_log'] === 'ERROR') ? 'danger' : (($log['tipo_log'] === 'WARNING') ? 'warning text-dark' : 'success') ?>">
                                    <?= htmlspecialchars($log['tipo_log'] ?? '') ?>
                                </span>
                            </td>
                            <td><?= nl2br(htmlspecialchars($log['descricao'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($log['ip_usuario'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($log['rota'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($log['user_agent'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="configuracoes.php" class="btn btn-secondary mt-3">← Voltar</a>
</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
