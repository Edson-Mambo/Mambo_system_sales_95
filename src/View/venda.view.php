<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$stmt = $pdo->prepare("
    SELECT v.*, c.nome AS cliente_nome, c.id AS cliente_id
    FROM vendas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
");

//$clienteSelecionado = null;
//if (isset($_SESSION['cliente_id'])) {
 //   $clienteId = $_SESSION['cliente_id'];
  ////  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
   // $stmt->execute([$clienteId]);
   // $clienteSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);
//}

$id_venda = isset($_GET['id_venda']) ? intval($_GET['id_venda']) : null;

$venda = $stmt->fetch(PDO::FETCH_ASSOC);

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

  <!-- Cabeçalho Venda -->
<div class="p-3 bg-white border rounded shadow-sm mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
    <h5 class="mb-0 text-primary">Mambo System Sales</h5>

    <div class="text-muted small">
      <span class="me-3"><strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
      <span class="me-3"><strong>Recibo nº:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
      <span><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></span>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
      <div class="d-flex align-items-center">
        <strong class="me-2">Cliente:</strong>
        <span id="clienteSelecionadoTexto" class="text-primary fst-italic">
          <?= isset($clienteSelecionado) && $clienteSelecionado ? htmlspecialchars($clienteSelecionado['nome']) : 'Nenhum cliente selecionado' ?>
        </span>
        <input type="hidden" id="clienteSelecionadoId" name="cliente_id"
          value="<?= isset($clienteSelecionado) && $clienteSelecionado ? $clienteSelecionado['id'] : '' ?>">
      </div>

      <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalCadastrarCliente">
        ➕ Cadastrar Cliente
      </button>

      <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#modalBuscarCliente">
        🔍 Buscar Cliente
      </button>

      <a href="vales.php" class="btn btn-sm btn-outline-secondary">
        🔁 Ir para Vale
      </a>

      <!-- Botões de Fatura e Cotação -->
      <a href="factura_cotacao.php?tipo=factura&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
        class="btn btn-sm btn-outline-primary">
        🧾 Gerar Fatura
      </a>

      <a href="../src/View/cotacao.view.php?tipo=cotacao&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
   class="btn btn-sm btn-outline-success">
   📄 Gerar Cotação
</a>
<!-- Botão que abre o modal -->
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enviarReciboModal">
  📲 Enviar Recibo via WhatsApp
</button>

  <a href="logout.php" class="btn btn-sm btn-outline-danger">
    🔒 Terminar Sessão
  </a>
</div>

  </div>
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
                <button 
                    type="button" 
                    class="btn btn-danger btn-sm btn-remover" 
                    data-codigo="<?= htmlspecialchars($codigo) ?>">
                    Remover
                </button>

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


<!-- Modal Cadastrar Cliente -->
<div class="modal fade" id="modalCadastrarCliente" tabindex="-1" aria-labelledby="modalCadastrarClienteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formCadastrarCliente" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCadastrarClienteLabel">Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="nome_cliente" class="form-label">Nome</label>
          <input type="text" id="nome_cliente" name="nome_cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="apelido_cliente" class="form-label">Apelido (opcional)</label>
          <input type="text" id="apelido_cliente" name="apelido_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="telefone_cliente" class="form-label">Telefone</label>
          <input type="tel" id="telefone_cliente" name="telefone_cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="telefone_alt_cliente" class="form-label">Telefone Alternativo (opcional)</label>
          <input type="tel" id="telefone_alt_cliente" name="telefone_alt_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="email_cliente" class="form-label">Email (opcional)</label>
          <input type="email" id="email_cliente" name="email_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="morada_cliente" class="form-label">Morada (opcional)</label>
          <textarea id="morada_cliente" name="morada_cliente" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Salvar Cliente</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Buscar Cliente -->
<div class="modal fade" id="modalBuscarCliente" tabindex="-1" aria-labelledby="modalBuscarClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuscarClienteLabel">Buscar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form id="formBuscarCliente" class="mb-3 d-flex gap-2">
          <input type="text" id="buscar_cliente_input" placeholder="Nome ou Telefone" class="form-control" autofocus>
          <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        <div id="resultado_busca_cliente" style="max-height:300px; overflow-y:auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
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

        <!-- Checkbox de desconto -->
        <div class="mb-3 form-check">
          <input
            type="checkbox"
            class="form-check-input"
            id="desconto_colaborador"
            name="desconto_colaborador"
            <?= !empty($_POST['desconto_colaborador']) ? 'checked' : '' ?>
          />
          <label class="form-check-label" for="desconto_colaborador">Desconto Colaborador (10%)</label>
        </div>

        <!-- Total com desconto aplicado -->
        <div class="mb-2">
          <strong>Total: </strong><span id="total_valor">MT <?= number_format($total, 2, ',', '.') ?></span>
        </div>

        <!-- Valor pago -->
        <div class="mb-3">
          <label for="valor_pago" class="form-label">Valor Pago (MT):</label>
          <input type="number" step="0.01" min="0" name="valor_pago" id="valor_pago" class="form-control" required />
        </div>

        <!-- Método de pagamento -->
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

        <div class="mb-3 d-none" id="div_numero_autorizacao">
          <label for="numero_autorizacao" class="form-label">Número do Cartão ou Autorização:</label>
          <input type="text" name="numero_autorizacao" id="numero_autorizacao" class="form-control" />
        </div>

        <!-- Troco -->
        <div class="mb-3">
          <label class="form-label"><strong>Troco (MT):</strong></label>
          <input type="text" id="troco" class="form-control fw-bold" readonly>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#finalizarEmailModal">📧 Finalizar e Enviar por Email</button>
        <button type="submit" name="finalizar_venda" class="btn btn-primary">Confirmar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Cadastrar Cliente -->
<div class="modal fade" id="modalCadastrarCliente" tabindex="-1" aria-labelledby="modalCadastrarClienteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formCadastrarCliente" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCadastrarClienteLabel">Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="nome_cliente" class="form-label">Nome</label>
          <input type="text" id="nome_cliente" name="nome_cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="apelido_cliente" class="form-label">Apelido (opcional)</label>
          <input type="text" id="apelido_cliente" name="apelido_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="telefone_cliente" class="form-label">Telefone</label>
          <input type="tel" id="telefone_cliente" name="telefone_cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="telefone_alt_cliente" class="form-label">Telefone Alternativo (opcional)</label>
          <input type="tel" id="telefone_alt_cliente" name="telefone_alt_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="email_cliente" class="form-label">Email (opcional)</label>
          <input type="email" id="email_cliente" name="email_cliente" class="form-control">
        </div>
        <div class="mb-3">
          <label for="morada_cliente" class="form-label">Morada (opcional)</label>
          <textarea id="morada_cliente" name="morada_cliente" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Salvar Cliente</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Buscar Cliente -->
<div class="modal fade" id="modalBuscarCliente" tabindex="-1" aria-labelledby="modalBuscarClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuscarClienteLabel">Buscar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form id="formBuscarCliente" class="mb-3 d-flex gap-2">
          <input type="text" id="buscar_cliente_input" placeholder="Nome ou Telefone" class="form-control" autofocus>
          <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        <div id="resultado_busca_cliente" style="max-height:300px; overflow-y:auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Finalizar e Enviar por Email -->
<div class="modal fade" id="finalizarEmailModal" tabindex="-1" aria-labelledby="finalizarEmailLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="formFinalizarEmail">
      <div class="modal-header">
        <h5 class="modal-title" id="finalizarEmailLabel">Finalizar e Enviar Recibo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <!-- Valor total exibido com desconto (se aplicado na lógica JS) -->
        <p><strong>Total: </strong><span id="total_valor">MT <?= number_format($total, 2, ',', '.') ?></span></p>

        <!-- Email do cliente -->
        <div class="mb-3">
          <label for="email_destino" class="form-label">Email do Cliente:</label>
          <input type="email" name="email_destino" id="email_destino" class="form-control" required>
        </div>

        <!-- Mensagem opcional -->
        <div class="mb-3">
          <label for="mensagem_email" class="form-label">Mensagem (opcional):</label>
          <textarea name="mensagem_email" id="mensagem_email" rows="3" class="form-control"></textarea>
        </div>

        <!-- Valor pago no modal de email -->
        <div class="mb-3">
          <label for="valor_pago_email" class="form-label">Valor Pago (MT):</label>
          <input type="number" step="0.01" min="0" name="valor_pago_email" id="valor_pago_email" class="form-control" required />
        </div>

        <!-- Método de pagamento -->
        <div class="mb-3">
          <label for="metodo_pagamento_email" class="form-label">Método de Pagamento:</label>
          <select name="metodo_pagamento_email" id="metodo_pagamento_email" class="form-select" required>
            <option value="">-- Selecione --</option>
            <option value="mpesa">M-Pesa</option>
            <option value="emola">E-Mola</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="cartao">Cartão</option>
          </select>
        </div>
        <div class="mb-3 d-none" id="div_numero_autorizacao_email">
          <label for="numero_autorizacao_email" class="form-label">Número do Cartão ou Autorização:</label>
          <input type="text" name="numero_autorizacao_email" id="numero_autorizacao_email" class="form-control" />
        </div>


        <!-- Troco para e-mail -->
        <div class="mb-3">
          <label class="form-label"><strong>Troco (MT):</strong></label>
          <input type="text" id="troco_email" class="form-control fw-bold" readonly>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" name="finalizar_enviar" class="btn btn-success">Enviar Recibo</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>


<!-- Modal de Autorização -->
<div class="modal fade" id="modalAutorizacao" tabindex="-1" aria-labelledby="autorizacaoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formAutorizacao" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="autorizacaoLabel">Autorização Necessária</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="codigoProduto" name="codigo">
        <div class="mb-3">
          <label for="senha_autorizacao" class="form-label">Senha de gerente ou supervisor:</label>
          <input type="password" class="form-control" id="senha_autorizacao" required>
        </div>
        <div id="erro_autorizacao" class="text-danger small d-none">Senha incorreta ou sem permissão.</div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Confirmar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>




<!-- Modal -->
<div class="modal fade" id="enviarReciboModal" tabindex="-1" aria-labelledby="enviarReciboModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEnviarWhatsapp" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="enviarReciboModalLabel">Enviar Recibo via WhatsApp</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="recibo_id" class="form-label">Número do Recibo/Venda</label>
          <input type="text" class="form-control" id="recibo_id" name="recibo_id" required>
        </div>
        <div class="mb-3">
          <label for="numero_whatsapp" class="form-label">Número WhatsApp para Enviar</label>
          <input type="text" class="form-control" id="numero_whatsapp" name="numero_whatsapp" placeholder="Ex: 25884xxxxxxx" required>
          <div class="form-text">Informe o número no formato internacional, sem espaços ou símbolos.</div>
        </div>
        <div id="respostaWhatsapp"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Enviar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('formEnviarWhatsapp').addEventListener('submit', function(e) {
  e.preventDefault();

  const recibo_id = this.recibo_id.value.trim();
  const numero_whatsapp = this.numero_whatsapp.value.trim();
  const respostaDiv = document.getElementById('respostaWhatsapp');
  respostaDiv.innerHTML = '';

  if (!recibo_id || !numero_whatsapp) {
    respostaDiv.innerHTML = '<div class="alert alert-warning">Por favor, preencha ambos os campos.</div>';
    return;
  }

  fetch('enviar_recibo_whatsapp.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: new URLSearchParams({
    recibo_id: recibo_id,
    numero_whatsapp: numero_whatsapp
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    respostaDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
  } else {
    respostaDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
  }
})
.catch(async (error) => {
  const text = await error.text();
  console.error('Erro no fetch:', text);
  respostaDiv.innerHTML = '<div class="alert alert-danger">Erro ao enviar a mensagem.</div>';
});

});
</script>




<!-- Scripts -->
<script src="../bootstrap/bootstrap-5.3.3/jquery/jquery.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const metodo = document.getElementById('metodo_pagamento');
  const divAuth = document.getElementById('div_numero_autorizacao');

  metodo.addEventListener('change', () => {
    const valor = metodo.value;
    if (valor === 'dinheiro' || valor === '') {
      divAuth.classList.add('d-none');
    } else {
      divAuth.classList.remove('d-none');
    }
  });

  const metodoEmail = document.getElementById('metodo_pagamento_email');
  const divAuthEmail = document.getElementById('div_numero_autorizacao_email');

  metodoEmail.addEventListener('change', () => {
    const valor = metodoEmail.value;
    if (valor === 'dinheiro' || valor === '') {
      divAuthEmail.classList.add('d-none');
    } else {
      divAuthEmail.classList.remove('d-none');
    }
  });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const totalOriginal = <?= $total ?>;

  // --- ELEMENTOS DO MODAL DE VENDA NORMAL ---
  const formVenda = document.getElementById("formFinalizarVenda");
  const valorPagoVendaInput = document.getElementById("valor_pago");
  const trocoVendaInput = document.getElementById("troco");

  // --- ELEMENTOS DO MODAL DE EMAIL ---
  const formEmail = document.getElementById("formFinalizarEmail");
  const valorPagoEmailInput = document.getElementById("valor_pago_email");
  const trocoEmailInput = document.getElementById("troco_email");

  // --- ELEMENTOS COMUNS ---
  const descontoCheckbox = document.getElementById("desconto_colaborador");
  const totalSpan = document.getElementById("total_valor");

  // --- FORMATAÇÃO ---
  function formatarMT(valor) {
    return "MT " + valor.toFixed(2).replace(".", ",");
  }

  function obterTotalComDesconto() {
    return descontoCheckbox?.checked ? totalOriginal * 0.9 : totalOriginal;
  }

  // --- ATUALIZAÇÃO DO TOTAL E TROCO ---
  function atualizarTotais() {
    const total = obterTotalComDesconto();
    totalSpan.textContent = formatarMT(total);

    // Troco da venda normal
    if (valorPagoVendaInput && trocoVendaInput) {
      const pagoVenda = parseFloat(valorPagoVendaInput.value) || 0;
      const trocoVenda = Math.max(pagoVenda - total, 0);
      trocoVendaInput.value = formatarMT(trocoVenda);
    }

    // Troco da venda por email
    if (valorPagoEmailInput && trocoEmailInput) {
      const pagoEmail = parseFloat(valorPagoEmailInput.value) || 0;
      const trocoEmail = Math.max(pagoEmail - total, 0);
      trocoEmailInput.value = trocoEmail.toFixed(2).replace(".", ",");
    }
  }

  descontoCheckbox?.addEventListener("change", atualizarTotais);
  valorPagoVendaInput?.addEventListener("input", atualizarTotais);
  valorPagoEmailInput?.addEventListener("input", atualizarTotais);
  atualizarTotais();

  

  // --- FINALIZAR VENDA NORMAL ---
  formVenda?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(formVenda);
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

        setTimeout(() => location.href = "venda.php", 1000);
      } else {
        alert(data.mensagem || "Erro ao finalizar venda.");
      }
    } catch (err) {
      alert("Erro de requisição: " + err.message);
    }
  });

  // --- FINALIZAR VENDA POR EMAIL ---
  formEmail?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(formEmail);
    formData.append("finalizar_enviar", "1");

    try {
      const response = await fetch("venda.php", {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      const data = await response.json();

      if (data.success) {
        alert("Recibo enviado com sucesso para " + formData.get("email_destino"));
        setTimeout(() => location.href = "venda.php", 1000);
      } else {
        alert(data.mensagem || "Erro ao enviar e finalizar a venda.");
      }
    } catch (err) {
      alert("Erro de requisição: " + err.message);
    }
  });

  // --- ATALHO F9 PARA FINALIZAR ---
  document.addEventListener("keydown", (event) => {
    if (event.key === "F9") {
      event.preventDefault();
      const finalizarModal = new bootstrap.Modal(document.getElementById("finalizarModal"));
      finalizarModal.show();
    }
  });

  // --- FOCO AO ABRIR MODAIS ---
  document.getElementById("finalizarModal")?.addEventListener("shown.bs.modal", () => {
    valorPagoVendaInput?.focus();
  });

  document.getElementById("finalizarEmailModal")?.addEventListener("shown.bs.modal", () => {
    document.getElementById("email_destino")?.focus();
  });

  // --- AUTORIZAÇÃO PARA REMOVER PRODUTO ---
const authModal = new bootstrap.Modal(document.getElementById("modalAutorizacao"));
const senhaInput = document.getElementById("senha_autorizacao");
const codigoInput = document.getElementById("codigoProduto");
const authError = document.getElementById("erro_autorizacao");

document.querySelectorAll(".btn-remover").forEach(btn => {
  btn.addEventListener("click", () => {
    authError.classList.add("d-none");
    senhaInput.value = "";
    codigoInput.value = btn.dataset.codigo;
    authModal.show();
    senhaInput.focus();
  });
});

document.getElementById("formAutorizacao")?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const senha = senhaInput.value.trim();
  const codigo = codigoInput.value;
  if (!senha) return;

  try {
    const response = await fetch("validar_autorizacao.php", {
      method: "POST",
      body: JSON.stringify({ senha, codigo }),
      headers: { "Content-Type": "application/json" }
    });

    const result = await response.json();

    if (result.autorizado) {
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "venda.php";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "remover_produto";
      input.value = codigo;

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    } else {
      authError.classList.remove("d-none");
    }
  } catch (err) {
    alert("Erro ao validar senha: " + err.message);
  }
});



$(document).ready(function() {

  // --- UTIL: Atualiza cliente selecionado em todos os campos ---
  function setClienteSelecionado(id, nome) {
    $('#clienteSelecionadoTexto').text(nome);
    $('#clienteSelecionadoId').val(id);
    $('#clienteSelecionadoTextoSalvar').text(nome);
    $('#cliente_id_salvar').val(id);
    $('#cliente_id_finalizar').val(id);
  }

  // --- CADASTRAR CLIENTE ---
  $('#formCadastrarCliente').submit(function(e) {
    e.preventDefault();
    const dados = $(this).serialize();
    $.post('salvar_cliente_ajax.php', dados, function(res) {
      if (res.success) {
        alert('Cliente cadastrado!');
        setClienteSelecionado(res.cliente.id, res.cliente.nome);
        $('#modalCadastrarCliente').modal('hide');
        $('#formCadastrarCliente')[0].reset();
      } else {
        alert('Erro: ' + res.mensagem);
      }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
      console.error('Erro AJAX:', textStatus, errorThrown);
      console.error('Resposta do servidor:', jqXHR.responseText);
      alert('Erro na requisição: ' + textStatus);
    });
  });

  // --- BUSCAR CLIENTE ---
  $('#formBuscarCliente').submit(function(e) {
    e.preventDefault();
    const termo = $('#buscar_cliente_input').val().trim();
    if (termo.length < 2) {
      alert('Digite pelo menos 2 caracteres para buscar.');
      return;
    }

    $.get('buscar_cliente_ajax.php', { q: termo }, function(res) {
      if (res.length === 0) {
        $('#resultado_busca_cliente').html('<p class="text-danger">Nenhum cliente encontrado.</p>');
      } else {
        let html = '<ul class="list-group">';
        res.forEach(cliente => {
          html += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong>${cliente.nome}</strong> (${cliente.telefone})
              </div>
              <button class="btn btn-sm btn-success selecionar-cliente" data-id="${cliente.id}" data-nome="${cliente.nome}">Selecionar</button>
            </li>
          `;
        });
        html += '</ul>';
        $('#resultado_busca_cliente').html(html);
      }
    }, 'json').fail(function() {
      $('#resultado_busca_cliente').html('<p class="text-danger">Erro na requisição.</p>');
    });
  });

  // --- CLICAR EM SELECIONAR CLIENTE ---
  $('#resultado_busca_cliente').on('click', '.selecionar-cliente', function() {
    const id = $(this).data('id');
    const nome = $(this).data('nome');

    $.post('buscar_cliente_ajax.php', { cliente_id: id }, function(res) {
      if (res.success) {
        setClienteSelecionado(id, nome);
        $('#modalBuscarCliente').modal('hide');
        $('#resultado_busca_cliente').empty();
        $('#buscar_cliente_input').val('');
      } else {
        alert('Erro ao selecionar cliente.');
      }
    }, 'json').fail(function() {
      alert('Erro na requisição.');
    });
  });

});



});
</script>



</body>
</html>