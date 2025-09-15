<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recepção de Estoque</title>
    <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Recepção de Estoque</h2>
                <a href="../../public/index_admin.php" class="btn btn-outline-secondary">← Voltar</a>
            </div>

            <form action="../Controller/processar_recepcao.php" method="post">

                <!-- BUSCA PELO NOME OU CÓDIGO -->
                <div class="mb-3">
                    <label for="buscar_produto" class="form-label">Buscar Produto (Nome ou Código)</label>
                    <div class="input-group">
                        <input type="text" id="buscar_produto" class="form-control" placeholder="Digite nome ou código">
                        <button type="button" id="btn_add_produto" class="btn btn-primary">Adicionar</button>
                    </div>
                </div>

                <!-- PRODUTO SELECIONADO -->
                <div class="mb-3">
                    <label for="produto_id" class="form-label">Produto Selecionado</label>
                    <select name="produto_id" id="produto_id" class="form-select" required>
                        <option value="">Nenhum produto selecionado</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" step="0.01" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unidade</label>
                        <select name="unidade" class="form-select" required>
                            <option value="peca">Peça</option>
                            <option value="grama">Grama</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Receber Estoque</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#btn_add_produto').click(function() {
    const termo = $('#buscar_produto').val().trim();
    if (!termo) return;

    $.post('../../public/adicionar_produto_ajax.php', { produto_busca: termo, quantidade: 1 }, function(res) {
        if (res.success) {
            $('#produto_id').empty().append(
                `<option value="${res.produto.id}">${res.produto.nome} (${res.produto.codigo_barra || '-'})</option>`
            );
        } else {
            alert(res.message || 'Produto não encontrado');
        }
    }, 'json');
});
</script>

<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
