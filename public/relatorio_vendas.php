<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

/* =========================
   SEGURANÇA / LOGIN
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../public/login.php");
    exit;
}

/* =========================
   NÍVEL DE ACESSO ERP
========================= */
$nivel = $_SESSION['nivel_acesso'] ?? '';
$permitidos = ['admin', 'gerente', 'supervisor'];

/* =========================
   CONEXÃO
========================= */
$pdo = Database::conectar();

/* =========================
   FILTRO DE DATAS
========================= */
$data_inicial = $_GET['data_inicial'] ?? null;
$data_final   = $_GET['data_final'] ?? null;

/* =========================
   CATEGORIAS
========================= */
$stmtCat = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   RELATÓRIO
========================= */
$relatorio = [];

foreach ($categorias as $categoria) {

    $sql = "
        SELECT 
            DATE(v.data_venda) AS data,
            p.nome AS nome_produto,
            SUM(pv.quantidade) AS total_quantidade,
            SUM(pv.quantidade * pv.preco_unitario) AS total_valor
        FROM produtos_vendidos pv
        JOIN produtos p ON pv.produto_id = p.id
        JOIN vendas v ON pv.venda_id = v.id
        WHERE p.categoria_id = ?
    ";

    $params = [$categoria['id']];

    if ($data_inicial) {
        $sql .= " AND DATE(v.data_venda) >= ? ";
        $params[] = $data_inicial;
    }

    if ($data_final) {
        $sql .= " AND DATE(v.data_venda) <= ? ";
        $params[] = $data_final;
    }

    $sql .= "
        GROUP BY data, p.id
        ORDER BY data DESC, total_valor DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Agrupar por data */
    $agrupado = [];
    foreach ($dados as $linha) {
        $agrupado[$linha['data']][] = $linha;
    }

    $relatorio[] = [
        'id' => $categoria['id'],
        'categoria' => $categoria['nome'],
        'dados' => $agrupado
    ];
}

/* =========================
   VOLTAR ERP POR NÍVEL
========================= */
$voltar = [
    'admin' => '../public/index_admin.php',
    'gerente' => '../public/index_gerente.php',
    'supervisor' => '../public/index_supervisor.php'
][$nivel] ?? '../public/index.php';

?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Dashboard ERP - Vendas</title>

<link rel="stylesheet" href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">

<style>
body { background:#eef2f7; }

/* SIDEBAR ERP */
.sidebar {
    width: 220px;
    position: fixed;
    height: 100vh;
    background: #111827;
    color: #fff;
    padding: 15px;
}

.sidebar h5 {
    color: #fff;
    margin-bottom: 15px;
}

.sidebar a {
    display: block;
    padding: 10px;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 6px;
}

.sidebar a:hover {
    background: #2563eb;
    color: #fff;
}

/* CONTEÚDO */
.main {
    margin-left: 230px;
    padding: 20px;
}

/* HEADER */
.header {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* CARDS */
.card-box {
    margin-top: 15px;
}

/* TABELA */
.table thead th {
    position: sticky;
    top: 0;
    background: #1f2937 !important;
    color: #fff;
}

.low { color:#dc2626; font-weight:bold; }
.ok { color:#16a34a; font-weight:bold; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h5>📁 Categorias</h5>

    <?php foreach ($relatorio as $cat): ?>
        <a href="#cat-<?= $cat['id'] ?>">
            <?= htmlspecialchars($cat['categoria']) ?>
        </a>
    <?php endforeach; ?>

    <hr style="border-color:#374151;">

    <a href="<?= $voltar ?>">← Voltar</a>

</div>

<!-- CONTEÚDO -->
<div class="main">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h4 class="mb-0">📊 Dashboard de Vendas ERP</h4>
            <small><?= count($relatorio) ?> categorias</small>
        </div>
    </div>

    <!-- FILTRO -->
    <form method="GET" class="row g-3 mt-3 mb-3">
        <div class="col-md-3">
            <label>Data Inicial</label>
            <input type="date" name="data_inicial" value="<?= htmlspecialchars($data_inicial) ?>" class="form-control">
        </div>

        <div class="col-md-3">
            <label>Data Final</label>
            <input type="date" name="data_final" value="<?= htmlspecialchars($data_final) ?>" class="form-control">
        </div>

        <div class="col-md-3 align-self-end">
            <button class="btn btn-primary">Filtrar</button>
            <a href="?" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

    <!-- RELATÓRIO -->
    <?php foreach ($relatorio as $cat): ?>

        <div id="cat-<?= $cat['id'] ?>" class="mb-4">

            <h4 class="bg-primary text-white p-2 rounded">
                <?= htmlspecialchars($cat['categoria']) ?>
            </h4>

            <?php if (!empty($cat['dados'])): ?>

                <?php foreach ($cat['dados'] as $data => $produtos): ?>

                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-dark text-white">
                            Data: <?= date('d/m/Y', strtotime($data)) ?>
                        </div>

                        <div class="card-body p-0">

                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Qtd</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $total_dia = 0;
                                    foreach ($produtos as $p):
                                        $total_dia += $p['total_valor'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['nome_produto']) ?></td>
                                        <td><?= $p['total_quantidade'] ?></td>
                                        <td><?= number_format($p['total_valor'], 2, ',', '.') ?> MZN</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr class="table-secondary">
                                        <td colspan="2"><strong>Total do Dia</strong></td>
                                        <td><strong><?= number_format($total_dia, 2, ',', '.') ?> MZN</strong></td>
                                    </tr>
                                </tfoot>
                            </table>

                        </div>
                    </div>

                <?php endforeach; ?>

            <?php else: ?>
                <div class="alert alert-warning">
                    Sem vendas nesta categoria/período.
                </div>
            <?php endif; ?>

        </div>

    <?php endforeach; ?>

    <!-- VOLTAR -->
    <div class="text-center mt-4">
        <a href="<?= $voltar ?>" class="btn btn-secondary">← Voltar ao Menu</a>
    </div>

</div>

</body>
</html>