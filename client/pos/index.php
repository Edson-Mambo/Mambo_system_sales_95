<?php
/* =========================
   BOOTSTRAP DO SISTEMA
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

/* =========================
   AUTENTICAÇÃO
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: /Mambo_system_sales_95/client/auth/login.php");
    exit;
}

$nivel = strtolower(trim($_SESSION['nivel'] ?? ''));

if ($nivel !== 'caixa') {
    header("Location: /Mambo_system_sales_95/client/auth/login.php?erro=acesso");
    exit;
}

/* =========================
   CAIXA ABERTA
========================= */
$abertura_id = $_SESSION['abertura_id'] ?? null;

if (empty($abertura_id)) {
    header("Location: /Mambo_system_sales_95/client/pos/abrir_caixa.php");
    exit;
}

$pdo = Database::conectar();

$stmt = $pdo->prepare("SELECT id FROM abertura_caixa WHERE id = ? AND status = 'aberto'");
$stmt->execute([(int)$abertura_id]);

if (!$stmt->fetch()) {
    unset($_SESSION['abertura_id']);
    header("Location: /Mambo_system_sales_95/client/pos/abrir_caixa.php?erro=caixa_fechado");
    exit;
}

/* =========================
   USUÁRIO
========================= */
$usuario_nome = $_SESSION['nome'] ?? 'Caixa';

/* =========================
   CLIENTE SELECIONADO
========================= */
$clienteSelecionado = [
    'id'       => null,
    'nome'     => 'Cliente Geral',
    'telefone' => '',
    'email'    => '',
    'morada'   => '',
    'nuit'     => ''
];

if (!empty($_SESSION['cliente_id'])) {
    $stmt = $pdo->prepare("
        SELECT id, nome, apelido, telefone, email, morada, nuit
        FROM clientes
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['cliente_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $clienteSelecionado = [
            'id'       => $cliente['id'],
            'nome'     => trim($cliente['nome'] . ' ' . ($cliente['apelido'] ?? '')),
            'telefone' => $cliente['telefone'] ?? '',
            'email'    => $cliente['email'] ?? '',
            'morada'   => $cliente['morada'] ?? '',
            'nuit'     => $cliente['nuit'] ?? ''
        ];
    }
}

/* =========================
   CARRINHO
========================= */
$carrinho = $_SESSION['carrinho'] ?? [];

$total = array_reduce($carrinho, function ($carry, $item) {
    return $carry + ($item['preco'] * $item['quantidade']);
}, 0.0);

/* =========================
   ADICIONAR PRODUTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {

    $produto_busca = trim($_POST['busca_produto'] ?? '');
    $quantidade    = max(1, (int)($_POST['quantidade'] ?? 1));

    if ($produto_busca !== '') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM produtos
            WHERE codigo_barra = ?
               OR nome LIKE ?
            LIMIT 1
        ");
        $stmt->execute([$produto_busca, "%{$produto_busca}%"]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            $id = $produto['id'];

            if (isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id]['quantidade'] += $quantidade;
            } else {
                $_SESSION['carrinho'][$id] = [
                    'id'           => $produto['id'],
                    'nome'         => $produto['nome'],
                    'codigo_barra' => $produto['codigo_barra'],
                    'preco'        => (float)$produto['preco'],
                    'quantidade'   => $quantidade
                ];
            }
        } else {
            $_SESSION['erro'] = "Produto não encontrado.";
        }
    }

    header("Location: index.php");
    exit;
}

/* =========================
   RECIBO
========================= */
$numero_recibo = 'REC-' . date('YmdHis') . '-' . rand(100, 999);

/* =========================
   MENSAGEM DE ERRO (flash)
========================= */
$erro_flash = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>POS — Mambo System</title>

<!-- Bootstrap local -->
<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
<script src="../../bootstrap/bootstrap-5.3.3/js/jquery.min.js" defer></script>
<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js" defer></script>

<style>
  /* =====================
     VARIÁVEIS / TEMA
  ===================== */
  :root {
    --brand:      #0d6efd;
    --brand-dark: #084fbb;
    --success:    #198754;
    --danger:     #dc3545;
    --surface:    #f8f9fa;
    --border:     #dee2e6;
    --text:       #212529;
    --muted:      #6c757d;
    --radius:     .5rem;
    --shadow:     0 2px 8px rgba(0,0,0,.08);
  }

  body {
    background: var(--surface);
    color: var(--text);
    font-family: 'Segoe UI', system-ui, sans-serif;
  }

  /* =====================
     TOPBAR
  ===================== */
  .topbar {
    background: #fff;
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow);
    padding: .75rem 1.25rem;
    position: sticky;
    top: 0;
    z-index: 100;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
  }

  .topbar .brand {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--brand);
    letter-spacing: -.3px;
  }

  .topbar .meta {
    font-size: .8rem;
    color: var(--muted);
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .topbar .meta strong { color: var(--text); }

  /* =====================
     CARD WRAPPER
  ===================== */
  .card-pos {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
  }

  /* =====================
     TABELA CARRINHO
  ===================== */
  .table thead th {
    background: #1e2530;
    color: #fff;
    font-weight: 600;
    font-size: .85rem;
    letter-spacing: .03em;
    border: none;
  }

  .table tbody tr:hover { background: #f0f4ff; }

  .table tfoot td {
    background: #e9ecef;
    font-weight: 700;
  }

  /* =====================
     MODAL
  ===================== */
  .modal-header { border-bottom: 2px solid var(--brand); }
  .modal-footer { border-top: 1px solid var(--border); }

  #finalizarModal .total-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--success);
  }

  /* =====================
     TROCO positivo/negativo
  ===================== */
  #troco.negativo { color: var(--danger); font-weight: 700; }
  #troco.positivo { color: var(--success); font-weight: 700; }

  /* =====================
     BADGE CLIENTE
  ===================== */
  .cliente-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #e7f0ff;
    border: 1px solid #b6d0ff;
    border-radius: 2rem;
    padding: .2rem .75rem;
    font-size: .85rem;
    font-weight: 600;
    color: var(--brand);
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
</head>

<body>

<!-- =========================
     TOPBAR
========================= -->
<div class="topbar">

  <span class="brand">🏪 Mambo System Sales</span>

  <div class="meta">
    <span><strong>Operador:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
    <span><strong>Recibo:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
    <span><strong>Data:</strong> <?= date('d/m/Y H:i') ?></span>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">

    <!-- Cliente -->
    <span class="cliente-badge" title="Cliente selecionado">
      👤 <span id="clienteSelecionadoTexto"><?= htmlspecialchars($clienteSelecionado['nome']) ?></span>
    </span>
    <input type="hidden" id="clienteSelecionadoId" value="<?= htmlspecialchars($clienteSelecionado['id'] ?? '') ?>">

    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrarCliente">
      ➕ Novo Cliente
    </button>

    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalBuscarCliente">
      🔍 Buscar Cliente
    </button>

    <a href="/Mambo_system_sales_95/client/auth/logout.php" class="btn btn-sm btn-outline-danger">
      🔒 Sair
    </a>

  </div>
</div>

<!-- =========================
     CONTEÚDO PRINCIPAL
========================= -->
<div class="container-fluid px-4 pt-4">

  <!-- Alerta de erro (flash) -->
  <?php if ($erro_flash) : ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      ⚠️ <?= htmlspecialchars($erro_flash) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- =========================
       FORM — ADICIONAR PRODUTO
  ========================= -->
  <div class="card-pos">
    <form method="post" class="row g-3 align-items-end" autocomplete="off">

      <div class="col-md-6">
        <label for="busca_produto" class="form-label fw-semibold">Código de Barras / Nome do Produto</label>
        <input type="text"
               id="busca_produto"
               name="busca_produto"
               class="form-control form-control-lg"
               placeholder="Pesquise por código ou nome…"
               required
               autofocus>
      </div>

      <div class="col-md-2">
        <label for="quantidade" class="form-label fw-semibold">Qtd.</label>
        <input type="number"
               id="quantidade"
               name="quantidade"
               min="1"
               value="1"
               class="form-control form-control-lg"
               required>
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button type="submit" name="adicionar" class="btn btn-primary btn-lg flex-fill">
          ➕ Adicionar
        </button>

        <?php if (!empty($carrinho)) : ?>
          <button type="button"
                  class="btn btn-success btn-lg flex-fill"
                  data-bs-toggle="modal"
                  data-bs-target="#finalizarModal">
            ✅ Finalizar <kbd class="ms-1 bg-white text-success">F9</kbd>
          </button>
        <?php endif; ?>
      </div>

    </form>
  </div>

  <!-- =========================
       TABELA CARRINHO
  ========================= -->
  <div class="card-pos p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0">

        <thead>
          <tr>
            <th>#</th>
            <th>Produto</th>
            <th>Preço Unit.</th>
            <th>Qtd.</th>
            <th>Subtotal</th>
            <th class="text-center">Ação</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($carrinho)) :
            $i = 1;
            foreach ($carrinho as $codigo => $item) :
              $subtotal = $item['preco'] * $item['quantidade'];
          ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($item['nome']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($item['codigo_barra']) ?></div>
              </td>
              <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
              <td><?= (int)$item['quantidade'] ?></td>
              <td class="fw-semibold">MT <?= number_format($subtotal, 2, ',', '.') ?></td>
              <td class="text-center">
                <form method="post" action="ajax/remover_produto.php"
                      class="form-remover d-inline"
                      data-codigo="<?= htmlspecialchars($codigo) ?>">
                  <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">
                    🗑 Remover
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php else : ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                🛒 Carrinho vazio — adicione produtos acima.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>

        <tfoot>
          <tr>
            <td colspan="4" class="text-end fw-bold">TOTAL</td>
            <td colspan="2" class="fw-bold fs-5">
              MT <?= number_format($total, 2, ',', '.') ?>
            </td>
          </tr>
        </tfoot>

      </table>
    </div>
  </div>

</div><!-- /container -->


<!-- ============================================================
     MODAL — CADASTRAR CLIENTE
============================================================ -->
<div class="modal fade" id="modalCadastrarCliente" tabindex="-1" aria-labelledby="lblCadastrarCliente" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formCadastrarCliente" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblCadastrarCliente">Cadastrar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body row g-2">
        <div class="col-12">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input class="form-control" name="nome" placeholder="Nome completo" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefone <span class="text-danger">*</span></label>
          <input class="form-control" name="telefone" placeholder="8X XXX XXXX" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" placeholder="email@exemplo.com">
        </div>
        <div class="col-12">
          <label class="form-label">Morada</label>
          <textarea class="form-control" name="morada" rows="2" placeholder="Endereço"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">💾 Salvar Cliente</button>
      </div>

    </form>
  </div>
</div>


<!-- ============================================================
     MODAL — BUSCAR CLIENTE
============================================================ -->
<div class="modal fade" id="modalBuscarCliente" tabindex="-1" aria-labelledby="lblBuscarCliente" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="lblBuscarCliente">Buscar Cliente</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <div class="input-group mb-3">
          <input id="buscar_cliente_input"
                 class="form-control"
                 placeholder="Nome ou telefone…"
                 aria-label="Buscar cliente">
          <button class="btn btn-primary" id="btnBuscarCliente" type="button">
            🔍 Buscar
          </button>
        </div>
        <div id="resultado_busca_cliente"></div>
      </div>

    </div>
  </div>
</div>


<!-- ============================================================
     MODAL — FINALIZAR VENDA
============================================================ -->
<div class="modal fade" id="finalizarModal" tabindex="-1" aria-labelledby="lblFinalizarVenda" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formFinalizarVenda" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblFinalizarVenda">💳 Finalizar Venda</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">

        <!-- Total -->
        <div class="text-center mb-3">
          <div class="text-muted small text-uppercase fw-semibold mb-1">Total a Pagar</div>
          <div class="total-display" id="totalFinal">
            MT <?= number_format($total, 2, ',', '.') ?>
          </div>
        </div>

        <hr>

        <!-- Método de pagamento -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Método de Pagamento</label>
          <select class="form-select" id="metodo_pagamento" required>
            <option value="dinheiro">💵 Dinheiro</option>
            <option value="m-pesa">📱 M-Pesa</option>
            <option value="e-mola">📱 E-Mola</option>
            <option value="cartao">💳 Cartão</option>
          </select>
        </div>

        <!-- Valor pago -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Valor Entregue (MT)</label>
          <input type="number"
                 step="0.01"
                 min="0"
                 class="form-control form-control-lg"
                 id="valor_pago"
                 placeholder="0.00">
        </div>

        <!-- Desconto colaborador -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="desconto_colaborador">
          <label class="form-check-label" for="desconto_colaborador">
            🏷 Desconto de colaborador (10%)
          </label>
        </div>

        <!-- Troco -->
        <div class="mb-1">
          <label class="form-label fw-semibold">Troco</label>
          <input class="form-control form-control-lg fw-bold" id="troco" readonly placeholder="—">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success btn-lg" type="submit" id="btnConfirmarVenda">
          ✅ Confirmar Venda
        </button>
      </div>

    </form>
  </div>
</div>


<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script>
document.addEventListener("DOMContentLoaded", function () {

  /* ---------------------------
     CONSTANTES
  --------------------------- */
  const TOTAL_BASE = <?= json_encode((float)$total) ?>;

  /* ---------------------------
     ELEMENTOS
  --------------------------- */
  const formFinalizar      = document.getElementById("formFinalizarVenda");
  const valorPago          = document.getElementById("valor_pago");
  const descontoCheck      = document.getElementById("desconto_colaborador");
  const totalFinalEl       = document.getElementById("totalFinal");
  const trocoInput         = document.getElementById("troco");
  const btnConfirmar       = document.getElementById("btnConfirmarVenda");
  const btnBuscarCliente   = document.getElementById("btnBuscarCliente");
  const buscarClienteInput = document.getElementById("buscar_cliente_input");
  const resultadoCliente   = document.getElementById("resultado_busca_cliente");

  /* ---------------------------
     CÁLCULO DE TOTAL / TROCO
  --------------------------- */
  function calcularTotais() {
    const desconto = descontoCheck?.checked ? 0.10 : 0;
    const total    = TOTAL_BASE * (1 - desconto);
    const pago     = parseFloat(valorPago?.value) || 0;
    const troco    = pago - total;

    if (totalFinalEl) {
      totalFinalEl.textContent = "MT " + total.toFixed(2).replace(".", ",");
    }

    if (trocoInput) {
      trocoInput.value = troco >= 0
        ? troco.toFixed(2).replace(".", ",")
        : "Valor insuficiente";

      trocoInput.classList.toggle("negativo", troco < 0);
      trocoInput.classList.toggle("positivo", troco >= 0);
    }
  }

  valorPado?.addEventListener("input", calcularTotais);
  valorPago?.addEventListener("input", calcularTotais);
  descontoCheck?.addEventListener("change", calcularTotais);

  /* ---------------------------
     FINALIZAR VENDA
  --------------------------- */
  if (formFinalizar) {
    formFinalizar.addEventListener("submit", function (e) {
      e.preventDefault();

      const pago  = parseFloat(valorPago?.value) || 0;
      const desconto = descontoCheck?.checked ? 0.10 : 0;
      const total = TOTAL_BASE * (1 - desconto);

      if (pago < total) {
        alert("⚠️ Valor pago é insuficiente para cobrir o total da venda.");
        valorPago.focus();
        return;
      }

      if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = "⏳ Processando…";
      }

      fetch("ajax/finalizar_venda.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          metodo_pagamento: document.getElementById("metodo_pagamento")?.value,
          valor_pago:       valorPago?.value || 0,
          desconto:         descontoCheck?.checked || false
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("✅ Venda finalizada com sucesso!");
          window.location.reload();
        } else {
          alert("❌ " + (data.message || "Erro ao finalizar venda."));
          if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = "✅ Confirmar Venda";
          }
        }
      })
      .catch(err => {
        console.error("Erro ao finalizar venda:", err);
        alert("❌ Erro de comunicação com o servidor.");
        if (btnConfirmar) {
          btnConfirmar.disabled = false;
          btnConfirmar.textContent = "✅ Confirmar Venda";
        }
      });
    });
  }

  /* ---------------------------
     REMOVER PRODUTO DO CARRINHO
  --------------------------- */
  document.querySelectorAll(".form-remover").forEach(form => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const codigo = this.dataset.codigo;
      if (!codigo) return;

      fetch("ajax/remover_produto.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ codigo })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Remove a linha da tabela sem recarregar a página
          const tr = form.closest("tr");
          if (tr) {
            tr.style.transition = "opacity .3s";
            tr.style.opacity = "0";
            setTimeout(() => { tr.remove(); atualizarNumeracao(); }, 300);
          } else {
            window.location.reload();
          }
        } else {
          alert("❌ " + (data.message || "Erro ao remover produto."));
        }
      })
      .catch(err => {
        console.error("Erro ao remover produto (AJAX):", err);
        // Fallback: submit normal do form
        form.submit();
      });
    });
  });

  function atualizarNumeracao() {
    document.querySelectorAll("tbody tr").forEach((tr, i) => {
      const tdNum = tr.querySelector("td:first-child");
      if (tdNum && !isNaN(tdNum.textContent)) tdNum.textContent = i + 1;
    });
  }

  /* ---------------------------
     BUSCAR CLIENTE
  --------------------------- */
  function buscarCliente() {
    const termo = buscarClienteInput?.value.trim() || "";
    if (!termo) return;

    if (resultadoCliente) {
      resultadoCliente.innerHTML = `<div class="text-muted">⏳ Buscando…</div>`;
    }

    fetch("ajax/buscar_cliente.php?termo=" + encodeURIComponent(termo))
      .then(res => res.text())
      .then(html => {
        if (resultadoCliente) resultadoCliente.innerHTML = html;
      })
      .catch(err => {
        console.error("Erro ao buscar cliente:", err);
        if (resultadoCliente) {
          resultadoCliente.innerHTML = `<div class="alert alert-danger">Erro ao buscar cliente.</div>`;
        }
      });
  }

  btnBuscarCliente?.addEventListener("click", buscarCliente);

  buscarClienteInput?.addEventListener("keydown", function (e) {
    if (e.key === "Enter") { e.preventDefault(); buscarCliente(); }
  });

  /* ---------------------------
     CADASTRAR CLIENTE
  --------------------------- */
  const formCadastrar = document.getElementById("formCadastrarCliente");

  if (formCadastrar) {
    formCadastrar.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const payload  = Object.fromEntries(formData.entries());

      fetch("ajax/cadastrar_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("✅ Cliente cadastrado com sucesso!");
          formCadastrar.reset();
          bootstrap.Modal.getInstance(document.getElementById("modalCadastrarCliente"))?.hide();
        } else {
          alert("❌ " + (data.message || "Erro ao cadastrar cliente."));
        }
      })
      .catch(err => {
        console.error("Erro ao cadastrar cliente:", err);
        alert("❌ Erro de comunicação com o servidor.");
      });
    });
  }

  /* ---------------------------
     ATALHO F9 — ABRIR MODAL
  --------------------------- */
  document.addEventListener("keydown", function (e) {
    if (e.key === "F9") {
      e.preventDefault();
      <?php if (!empty($carrinho)) : ?>
        bootstrap.Modal.getOrCreateInstance(document.getElementById("finalizarModal")).show();
      <?php endif; ?>
    }
  });

});/* DOMContentLoaded */

/* ---------------------------
   FUNÇÃO GLOBAL — SELECIONAR CLIENTE
   (chamada pelo HTML retornado pelo AJAX)
--------------------------- */
function selecionarCliente(id, nome) {
  document.getElementById("clienteSelecionadoTexto").textContent = nome;
  document.getElementById("clienteSelecionadoId").value = id;

  bootstrap.Modal.getInstance(document.getElementById("modalBuscarCliente"))?.hide();

  fetch("ajax/set_cliente.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cliente_id: id })
  }).catch(err => console.error("Erro ao definir cliente:", err));
}
</script>

</body>
</html>