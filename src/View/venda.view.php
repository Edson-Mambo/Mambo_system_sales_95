<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$carrinho = $_SESSION['carrinho'] ?? [];
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$numero_recibo = $_SESSION['numero_recibo'] ?? 'N/A';

$total = 0;
foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['quantidade'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Venda - MamboSystem95</title>
    <!-- Bootstrap CSS local -->
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">


<div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded shadow-sm mb-3 flex-wrap gap-2">
    <h5 class="mb-0 text-primary">Mambo System Sales</h5>
    <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
        <span><strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
        <span><strong>Recibo nº:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
        <span><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></span>
        <span><a href="../public/vales.php" class="btn btn-primary">Ir para Venda</a></span>
        <span><a href="../public/teka_away_menu.php" class="btn btn-primary">Menu Take Away</a></span>
        

    </div>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">🔒 Terminar Sessão</a>
</div>
    

    <!-- Form adicionar produto -->
    <form method="post" class="row g-3 mb-4 align-items-end">
        <div class="col-md-6">
            <label for="busca_produto" class="form-label">Código de barras ou Nome do produto</label>
            <input type="text" id="busca_produto" name="busca_produto" class="form-control" placeholder="Digite o código de barras ou o nome do produto" required>
        </div>
        <div class="col-md-2">
            <label for="quantidade" class="form-label">Quantidade</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="1" class="form-control" required>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Produto</button>
            <?php if (!empty($carrinho)): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalizarModal">Finalizar Venda</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Carrinho -->
    <h2></h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>Preço Unit.</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($carrinho)) : ?>
                    <?php foreach ($carrinho as $codigo => $item) : 
                        $subtotal = $item['preco'] * $item['quantidade'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome']) ?></td>
                        <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td>MT <?= number_format($subtotal, 2, ',', '.') ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <button type="submit" name="remover_produto" value="<?= htmlspecialchars($codigo) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remover produto?');">Remover</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Carrinho vazio</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="3" class="text-end fw-bold">Total:</td>
                    <td class="fw-bold" colspan="2">MT <?= number_format($total, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal finalizar venda -->
<div class="modal fade" id="finalizarModal" tabindex="-1" aria-labelledby="finalizarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="finalizarLabel">Finalizar Venda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>Total a pagar: <strong>MT <?= number_format($total, 2, ',', '.') ?></strong></p>
        <div class="mb-3">
            <label for="valor_pago" class="form-label">Valor Pago (MT):</label>
            <input type="number" step="0.01" min="0" name="valor_pago" id="valor_pago" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label"><strong>Troco (MT):</strong></label>
            <input type="text" id="troco" class="form-control fw-bold" readonly>
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" name="finalizar_venda" class="btn btn-primary">Confirmar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- jQuery local -->
<script src="../bootstrap/bootstrap-5.3.3/jquery/jquery.min.js"></script>
<!-- Bootstrap JS Bundle local -->
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

<script>
// Ativa modal Finalizar venda ao pressionar F9
document.addEventListener('keydown', function(e) {
    if (e.key === 'F9') {
        e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('finalizarModal'));
        modal.show();
    }
});
</script>

<?php if (!empty($mensagem) && $mensagem === 'Venda finalizada com sucesso' && !empty($_SESSION['pdfPath'])) : ?>
    <script>
        // Baixa o recibo PDF automaticamente
        const link = document.createElement('a');
        link.href = '<?= htmlspecialchars($_SESSION['pdfPath']) ?>';
        link.download = 'recibo.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Redireciona para venda limpa após 1s
        setTimeout(() => {
            window.location.href = 'venda.php';
        }, 1000);
    </script>
    <?php unset($_SESSION['pdfPath']); ?>
<?php endif; ?>

<script>
document.querySelector("#formFinalizarVenda").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const response = await fetch("venda.php", {
        method: "POST",
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        window.open("gerar_recibo.php?venda_id=" + data.venda_id, "_blank");
        setTimeout(() => {
            location.href = "venda.php";
        }, 1000); // 1 segundo para dar tempo de abrir o PDF
    } else {
        alert(data.mensagem);
    }
});

fetch('finalizar_venda.php', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    // Abrir o recibo em nova aba
    window.open('gerar_recibo.php?venda_id=' + data.venda_id, '_blank');
    
    // Esperar um pouco e recarregar a página para uma nova venda
    setTimeout(() => {
      window.location.href = 'venda.php'; // ou o caminho correto da sua tela de venda
    }, 500); // tempo suficiente para abrir o PDF antes de mudar a página
  } else {
    alert(data.mensagem); // mostra erro apenas se houver falha
  }
});


</script>
<script>
document.getElementById('form-venda').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('finalizar_venda', '1');

    fetch('venda.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Abrir o recibo
            window.open('gerar_recibo.php?venda_id=' + data.venda_id, '_blank');

            // Esperar e recarregar página limpa
            setTimeout(() => {
                window.location.href = 'venda.php';
            }, 500);
        } else {
            alert(data.mensagem || 'Erro ao finalizar venda.');
        }
    });
});
</script>
<script>
document.getElementById('btnFinalizarVenda').addEventListener('click', function () {
    fetch('venda.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'finalizar_venda=1'
    })
    .then(response => {
        // Redireciona após finalizar
        window.location.href = 'venda.php';
    })
    .catch(error => {
        alert('Erro ao finalizar a venda.');
        console.error(error);
    });
});
</script>

        
<script>
    // Logica do calculo do troco
document.getElementById('valor_pago').addEventListener('input', function () {
    const valorPago = parseFloat(this.value);
    const totalVenda = <?= $total ?>;
    
    if (!isNaN(valorPago)) {
        const troco = valorPago - totalVenda;
        document.getElementById('troco').value = troco >= 0
            ? troco.toFixed(2).replace('.', ',')
            : '0,00';
    } else {
        document.getElementById('troco').value = '';
    }
});

// Focar no campo de busca ao carregar a página
window.onload = function() {
    const campoBusca = document.getElementById('busca_produto');
    if (campoBusca) campoBusca.focus();
};

// Re-focar no campo após cada ação com AJAX (se houver no futuro)
// e ao pressionar Enter no campo de quantidade, por exemplo
document.getElementById('quantidade')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        setTimeout(() => {
            document.getElementById('busca_produto')?.focus();
        }, 100);
    }
});

</script>





</body>
</html>


