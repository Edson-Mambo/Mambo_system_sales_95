<?php
require_once __DIR__ . '/../../config/database.php';

$pdo = Database::conectar();

$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';

$sql = "SELECT re.*, p.nome AS nome_produto
        FROM recepcao_estoque re
        JOIN produtos p ON re.produto_id = p.id";

$params = [];

if ($data_inicial && $data_final) {
    $sql .= " WHERE DATE(re.data_recebimento) BETWEEN :inicio AND :fim";
    $params[':inicio'] = $data_inicial;
    $params[':fim'] = $data_final;
}

$sql .= " ORDER BY re.data_recebimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recepcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Recepção</title>
    <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <h2 class="mb-4">📦 Relatório de Recepção de Estoque</h2>

            <a href="../../public/index_admin.php" class="btn btn-secondary mb-3">← Voltar ao Painel</a>

            <form class="row g-3 mb-4" method="GET">
                <div class="col-md-4">
                    <label for="data_inicial" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?= htmlspecialchars($data_inicial) ?>">
                </div>
                <div class="col-md-4">
                    <label for="data_final" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="data_final" name="data_final" value="<?= htmlspecialchars($data_final) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="relatorio_recepcao.php" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <?php if ($recepcoes): ?>
            <div class="mb-3 text-end">
                <button id="btnExportExcel" class="btn btn-success me-2">⬇️ Exportar Excel</button>
                <button id="btnExportPDF" class="btn btn-danger">⬇️ Exportar PDF</button>
            </div>
            <?php endif ?>

            <div class="table-responsive" id="tabelaRelatorio">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Unidade</th>
                            <th>Data</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recepcoes as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($r['nome_produto']) ?></td>
                                <td><?= number_format($r['quantidade_recebida'], 2, ',', '.') ?></td>
                                <td><?= $r['unidade'] === 'peca' ? 'Peça(s)' : 'Grama(s)' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($r['observacao'])) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($recepcoes)): ?>
                <div class="alert alert-warning text-center mt-3">
                    Nenhuma recepção de estoque registrada no período selecionado.
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-table2excel/1.1.2/jquery.table2excel.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    $('#btnExportExcel').click(function () {
        $(".table").table2excel({
            name: "RecepcaoEstoque",
            filename: "relatorio_recepcao",
            fileext: ".xls"
        });
    });

    $('#btnExportPDF').click(function () {
        const elemento = document.getElementById('tabelaRelatorio');
        const opt = {
            margin:       0.3,
            filename:     'relatorio_recepcao.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(elemento).save();
    });
</script>

</body>
</html>
