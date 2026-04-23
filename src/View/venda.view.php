<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$pdo = Database::conectar();

/* =========================
   CLIENTE SELECIONADO (COMPLETO)
========================= */
$clienteSelecionado = [
    'id' => null,
    'nome' => 'Cliente Geral',
    'telefone' => '',
    'email' => '',
    'morada' => '',
    'nuit' => ''
];

if (!empty($_SESSION['cliente_id'])) {

    $stmtCliente = $pdo->prepare("
        SELECT *
        FROM clientes
        WHERE id = ?
        LIMIT 1
    ");

    $stmtCliente->execute([$_SESSION['cliente_id']]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {

        $clienteSelecionado = [
            'id' => $cliente['id'],
            'nome' => trim($cliente['nome'] . ' ' . ($cliente['apelido'] ?? '')),
            'telefone' => $cliente['telefone'] ?? '',
            'email' => $cliente['email'] ?? '',
            'morada' => $cliente['morada'] ?? '',
            'nuit' => $cliente['nuit'] ?? ''
        ];
    }
}

/* =========================
   VENDA
========================= */
$id_venda = isset($_GET['id_venda']) ? intval($_GET['id_venda']) : 0;

$venda = null;

if ($id_venda > 0) {
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
    $stmt->execute([$id_venda]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   CARRINHO
========================= */
$carrinho = $carrinho ?? [];

$total = 0;

foreach ($carrinho as $item) {
    $total += ($item['preco'] ?? 0) * ($item['quantidade'] ?? 0);
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Venda - MamboSystem95</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../bootstrap/bootstrap-5.3.3/js/jquery.min.js" rel="script" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  


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

      <!--<a href="venda_vale.php" class="btn btn-sm btn-outline-secondary">
        🔁 Ir para Vale
      </a>-->

      <!-- Botões de Fatura e Cotação -->
      <a href="factura_cotacao.php?tipo=factura&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
        class="btn btn-sm btn-outline-primary">
        🧾 Gerar Fatura
      </a>

      <a href="../src/View/cotacao.view.php?tipo=cotacao&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
   class="btn btn-sm btn-outline-success">
   📄 Gerar Cotação
</a>
<!-- Botão que abre o modal 
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#enviarReciboModal">
  📲 Enviar Recibo via WhatsApp
</button> -->

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
document.addEventListener("DOMContentLoaded", function () {

    const formBuscar = document.getElementById("formBuscarCliente");
    const inputBuscar = document.getElementById("buscar_cliente_input");
    const resultado = document.getElementById("resultado_busca_cliente");

    const clienteTexto = document.getElementById("clienteSelecionadoTexto");
    const clienteIdInput = document.getElementById("clienteSelecionadoId");

    /* =========================
       BUSCAR CLIENTE
    ========================= */
    if (formBuscar) {
        formBuscar.addEventListener("submit", function (e) {
            e.preventDefault();

            const termo = inputBuscar.value.trim();

            if (termo.length < 2) {
                resultado.innerHTML = "<p class='text-danger'>Digite pelo menos 2 caracteres</p>";
                return;
            }

            fetch("../public/buscar_cliente_ajax.php?q=" + encodeURIComponent(termo))
                .then(async res => {

                    const text = await res.text();

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("RESPOSTA INVÁLIDA DO PHP:", text);
                        throw new Error("Resposta não é JSON válido");
                    }

                })
                .then(data => {

                    if (!Array.isArray(data)) {
                        throw new Error("Resposta inválida do servidor");
                    }

                    if (data.length === 0) {
                        resultado.innerHTML = "<p class='text-muted'>Nenhum cliente encontrado</p>";
                        return;
                    }

                    let html = "<div class='list-group'>";

                    data.forEach(cliente => {
                        html += `
                            <button type="button"
                                class="list-group-item list-group-item-action selecionar-cliente"
                                data-id="${cliente.id}"
                                data-nome="${cliente.nome} ${cliente.apelido ?? ''}">

                                <strong>${cliente.nome} ${cliente.apelido ?? ''}</strong><br>
                                📞 ${cliente.telefone}
                            </button>
                        `;
                    });

                    html += "</div>";

                    resultado.innerHTML = html;

                    ativarSelecaoCliente();
                })
                .catch(err => {
                    console.error(err);
                    resultado.innerHTML = "<p class='text-danger'>Erro ao buscar clientes</p>";
                });
        });
    }

    /* =========================
       SELECIONAR CLIENTE
    ========================= */
   function ativarSelecaoCliente() {

    document.querySelectorAll(".selecionar-cliente").forEach(btn => {

        // evita duplicar listeners
        btn.replaceWith(btn.cloneNode(true));
    });

    document.querySelectorAll(".selecionar-cliente").forEach(btn => {

        btn.addEventListener("click", async function () {

            const id = this.dataset.id;
            const nome = this.dataset.nome;

            // bloqueia clique imediatamente
            this.style.pointerEvents = "none";

            try {

                const res = await fetch("../public/buscar_cliente_ajax.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "cliente_id=" + encodeURIComponent(id)
                });

                const data = await res.json();

                if (data.success) {

                    clienteTexto.innerText = nome;
                    clienteIdInput.value = id;

                    const modalEl = document.getElementById("modalBuscarCliente");
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();

                    // garante limpeza do backdrop
                    setTimeout(() => {
                        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
                        document.body.classList.remove("modal-open");
                        document.body.style.overflow = "";
                    }, 300);

                } else {
                    alert(data.message || "Erro ao selecionar cliente");
                }

            } catch (err) {
                console.error(err);
                alert("Erro de conexão ao selecionar cliente");
            }

            // reativa clique
            setTimeout(() => {
                this.style.pointerEvents = "auto";
            }, 500);
        });
    });
}

    /* =========================
       CADASTRAR CLIENTE
    ========================= */
    const formCadastrar = document.getElementById("formCadastrarCliente");

    if (formCadastrar) {
        formCadastrar.addEventListener("submit", function (e) {
            e.preventDefault();

            fetch("../public/cadastrar_cliente_ajax.php", {
                method: "POST",
                body: new FormData(formCadastrar)
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {

                    alert("Cliente cadastrado com sucesso!");

                    formCadastrar.reset();

                    clienteTexto.innerText = data.cliente.nome;
                    clienteIdInput.value = data.cliente.id;

                    const modalEl = document.getElementById("modalBuscarCliente");
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();

                    // garante remoção de backdrop preso
                    setTimeout(() => {
                        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
                        document.body.classList.remove("modal-open");
                        document.body.style.overflow = "";
                    }, 300);

                    if (modal) modal.hide();

                } else {
                    alert(data.message || "Erro ao cadastrar cliente");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erro ao cadastrar cliente");
            });
        });
    }

});
</script>

<!-- Scripts -->
<script src="../bootstrap/bootstrap-5.3.3/jquery/jquery.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>

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
.then(response => response.json()) // 
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
    console.error(error);
    respostaDiv.innerHTML = '<div class="alert alert-danger">Erro ao enviar a mensagem.</div>';
  });
});


/* =========================
   CONTROLO DE METODO PAGAMENTO
========================= */
document.addEventListener('DOMContentLoaded', () => {

  const metodo = document.getElementById('metodo_pagamento');
  const divAuth = document.getElementById('div_numero_autorizacao');

  metodo?.addEventListener('change', () => {
    const valor = metodo.value;
    divAuth.classList.toggle('d-none', valor === 'dinheiro' || valor === '');
  });

  const metodoEmail = document.getElementById('metodo_pagamento_email');
  const divAuthEmail = document.getElementById('div_numero_autorizacao_email');

  metodoEmail?.addEventListener('change', () => {
    const valor = metodoEmail.value;
    divAuthEmail.classList.toggle('d-none', valor === 'dinheiro' || valor === '');
  });
});


/* =========================
   FINALIZAÇÃO DE VENDA + PDF (CORRIGIDO)
========================= */
document.addEventListener("DOMContentLoaded", () => {

  const totalOriginal = <?= $total ?>;

  const formVenda = document.getElementById("formFinalizarVenda");
  const formEmail = document.getElementById("formFinalizarEmail");

  const valorPagoVendaInput = document.getElementById("valor_pago");
  const trocoVendaInput = document.getElementById("troco");

  const valorPagoEmailInput = document.getElementById("valor_pago_email");
  const trocoEmailInput = document.getElementById("troco_email");

  const descontoCheckbox = document.getElementById("desconto_colaborador");
  const totalSpan = document.getElementById("total_valor");

  function formatarMT(valor) {
    return "MT " + valor.toFixed(2).replace(".", ",");
  }

  function obterTotalComDesconto() {
    return descontoCheckbox?.checked ? totalOriginal * 0.9 : totalOriginal;
  }

  function atualizarTotais() {
    const total = obterTotalComDesconto();
    if (totalSpan) totalSpan.textContent = formatarMT(total);

    if (valorPagoVendaInput && trocoVendaInput) {
      const pago = parseFloat(valorPagoVendaInput.value) || 0;
      trocoVendaInput.value = formatarMT(Math.max(pago - total, 0));
    }

    if (valorPagoEmailInput && trocoEmailInput) {
      const pago = parseFloat(valorPagoEmailInput.value) || 0;
      trocoEmailInput.value = (Math.max(pago - total, 0)).toFixed(2).replace(".", ",");
    }
  }

  descontoCheckbox?.addEventListener("change", atualizarTotais);
  valorPagoVendaInput?.addEventListener("input", atualizarTotais);
  valorPagoEmailInput?.addEventListener("input", atualizarTotais);
  atualizarTotais();


  /* =========================
   FINALIZAR VENDA (CORRIGIDO PDF)
========================= */
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

    if (!data.success) {
      alert(data.mensagem || "Erro ao finalizar venda.");
      return;
    }

    if (!data.venda_id) {
      alert("Venda finalizada mas sem ID.");
      return;
    }

    alert("Venda finalizada com sucesso!");

    const pdfUrl =
      data.pdf_url ||
      `gerar_recibo.php.php?venda_id=${data.venda_id}`;

    /* =========================
       🔥 DOWNLOAD REAL (CORRIGIDO)
    ========================== */

    // ✔ Método mais confiável no Chrome / Electron
    const newWindow = window.open(pdfUrl, "_blank");

    // fallback caso popup bloqueado
    if (!newWindow || newWindow.closed || typeof newWindow.closed === "undefined") {
      window.location.href = pdfUrl;
    }

    /* reset sistema */
    setTimeout(() => {
      location.href = "venda.php";
    }, 1200);

  } catch (err) {
    console.error(err);
    alert("Erro: " + err.message);
  }
});


  /* =========================
     TECLA F9
  ========================== */
  document.addEventListener("keydown", (event) => {
    if (event.key === "F9") {
      event.preventDefault();
      const modal = new bootstrap.Modal(document.getElementById("finalizarModal"));
      modal.show();
    }
  });

});
</script>



</body>
</html>