<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total = array_reduce($carrinho, fn($soma, $item) => $soma + ($item['preco'] * $item['quantidade']), 0);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Venda - MamboSystem95</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    #finalizarModal input.form-control {
      font-weight: bold;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <!-- Cabeçalho -->
  <div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded shadow-sm mb-3 flex-wrap gap-2">
    <h5 class="mb-0 text-primary">Mambo System Sales</h5>
    <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
      <span><strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
      <span><strong>Recibo nº:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
      <span><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></span>
    </div>
    <a href="vales.php" class="btn btn-sm btn-outline-secondary">🔁 Ir para Vale</a>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">🔒 Terminar Sessão</a>
  </div>

  <!-- Mensagem -->
  <?php if (!empty($mensagem)) : ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <!-- Form de adicionar produto -->
  <form method="post" class="row g-3 mb-4 align-items-end">
    <div class="col-md-6">
      <label for="busca_produto" class="form-label">Código/Nome</label>
      <input type="text" id="busca_produto" name="busca_produto" class="form-control" placeholder="Digite o código ou nome do produto" required autofocus>
    </div>
    <div class="col-md-2">
      <label for="quantidade" class="form-label">Quantidade</label>
      <input type="number" id="quantidade" name="quantidade" min="1" value="1" class="form-control" required>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button type="submit" name="adicionar" class="btn btn-primary">Adicionar</button>
      <?php if (!empty($carrinho)) : ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalizarModal">Finalizar Venda</button>
      <?php endif; ?>
    </div>
  </form>

  <!-- Tabela de carrinho -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>Nome</th>
          <th>Preço Unitário</th>
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
                  <button type="submit" name="remover_produto" value="<?= htmlspecialchars($codigo) ?>" class="btn btn-danger btn-sm">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
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

<!-- Modal Finalizar Venda -->
<div class="modal fade" id="finalizarModal" tabindex="-1" aria-labelledby="finalizarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="formFinalizarVenda">
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
          <label for="metodo_pagamento" class="form-label">Método de Pagamento:</label>
          <select name="metodo_pagamento" id="metodo_pagamento" class="form-select" required>
            <option value="">-- Selecione --</option>
            <option value="mpesa">M-Pesa</option>
            <option value="emola">E-Mola</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="cartao">Cartão</option>
          </select>
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

<!-- Scripts -->
<script src="../bootstrap/bootstrap-5.3.3/jquery/jquery.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formFinalizarVenda");

    if (form) {
      form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append("finalizar_venda", "1");

        try {
          const response = await fetch("venda.php", {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" }
          });

          const data = await response.json();

          if (data.success) {
            const link = document.createElement("a");
            link.href = data.pdfPath;
            link.download = `recibo_venda_${data.venda_id}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();

            setTimeout(() => {
              location.href = "venda.php";
            }, 1000);
          } else {
            alert(data.mensagem || "Erro ao finalizar venda.");
          }
        } catch (err) {
          alert("Erro de requisição: " + err.message);
        }
      });
    }

    // Troco automático
    const valorPagoInput = document.getElementById("valor_pago");
    const trocoInput = document.getElementById("troco");
    valorPagoInput?.addEventListener("input", () => {
      const valor = parseFloat(valorPagoInput.value);
      const troco = !isNaN(valor) ? valor - <?= $total ?> : 0;
      trocoInput.value = troco >= 0 ? troco.toFixed(2).replace('.', ',') : '0,00';
    });
  });

  document.addEventListener("keydown", function(event) {
  if (event.key === "F9") {
    event.preventDefault(); // evita que o navegador faça outra ação padrão do F9
    const finalizarModal = new bootstrap.Modal(document.getElementById('finalizarModal'));
    finalizarModal.show();
  }
});

const finalizarModalEl = document.getElementById('finalizarModal');
finalizarModalEl.addEventListener('shown.bs.modal', () => {
  const valorPagoInput = document.getElementById('valor_pago');
  if (valorPagoInput) {
    valorPagoInput.focus();
  }
});

</script>
</body>
</html>
