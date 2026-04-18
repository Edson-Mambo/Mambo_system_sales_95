<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once "../middleware/auth.php";

requireRole(['admin', 'gerente']);

$pdo = Database::conectar();

/* =========================
   FILTROS
========================= */
$user = $_GET['user'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';

$sql = "SELECT l.*, u.nome 
        FROM logs l 
        LEFT JOIN usuarios u ON u.id = l.user_id 
        WHERE 1=1";

$params = [];

if (!empty($user)) {
    $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%$user%";
    $params[] = "%$user%";
}

if (!empty($action)) {
    $sql .= " AND l.action LIKE ?";
    $params[] = "%$action%";
}

if (!empty($date)) {
    $sql .= " AND DATE(l.created_at) = ?";
    $params[] = $date;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Logs - Auditoria Sistema</title>

<style>
body{
    margin:0;
    font-family:Arial;
    background:#f5f6fa;
}

/* HEADER SIMPLE */
.header{
    background:#0d6efd;
    color:white;
    padding:15px 20px;
    font-weight:bold;
}

/* FILTERS */
.filters{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    padding:20px;
}

.filters input{
    padding:10px;
    border-radius:8px;
    border:1px solid #ddd;
}

/* TABLE */
.table-container{
    padding:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
}

th{
    background:#0d6efd;
    color:white;
    padding:12px;
    text-align:left;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
    font-size:14px;
}

tr:hover{
    background:#f1f5ff;
}

/* BADGES */
.badge{
    padding:4px 8px;
    border-radius:10px;
    font-size:12px;
    color:white;
}

.login{background:green;}
.logout{background:red;}
.update{background:orange;}
.create{background:blue;}
.delete{background:black;}
</style>
</head>

<body>

<div class="header">
    📊 Auditoria Completa do Sistema - Logs
</div>

<!-- FILTERS -->
<form method="GET" class="filters">

    <input type="text" name="user" placeholder="Utilizador" value="<?= htmlspecialchars($user) ?>">
    <input type="text" name="action" placeholder="Ação (login, update...)" value="<?= htmlspecialchars($action) ?>">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">

    <button type="submit">Filtrar</button>
    <a href="logs.php">Limpar</a>

</form>

<!-- TABLE -->
<div class="table-container">

<table>
    <tr>
        <th>ID</th>
        <th>Utilizador</th>
        <th>Ação</th>
        <th>Descrição</th>
        <th>Data</th>
    </tr>

    <?php foreach ($logs as $log): ?>

        <tr>
            <td><?= $log['id'] ?></td>
            <td><?= htmlspecialchars($log['nome'] ?? 'Sistema') ?></td>
            <td>
                <?php
                    $type = strtolower($log['action']);
                    $class = 'badge';

                    if (str_contains($type, 'login')) $class .= ' login';
                    elseif (str_contains($type, 'logout')) $class .= ' logout';
                    elseif (str_contains($type, 'update')) $class .= ' update';
                    elseif (str_contains($type, 'create')) $class .= ' create';
                    elseif (str_contains($type, 'delete')) $class .= ' delete';
                ?>
                <span class="<?= $class ?>">
                    <?= htmlspecialchars($log['action']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($log['description'] ?? '-') ?></td>
            <td><?= $log['created_at'] ?></td>
        </tr>

    <?php endforeach; ?>

</table>

</div>

</body>
</html>