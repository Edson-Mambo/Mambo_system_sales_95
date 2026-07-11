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

$nivel = strtolower(trim($_SESSION['nivel_acesso'] ?? $_SESSION['nivel'] ?? ''));

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
    $stmtCliente = $pdo->prepare("
        SELECT id, nome, apelido, telefone, email, morada, nuit
        FROM clientes
        WHERE id = ?
        LIMIT 1
    ");
    $stmtCliente->execute([$_SESSION['cliente_id']]);
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

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
    return $carry + (($item['preco'] ?? 0) * ($item['quantidade'] ?? 0));
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

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
<script src="../../bootstrap/bootstrap-5.3.3/js/jquery.min.js" defer></script>
<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js" defer></script>

<style>
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

  .topbar .brand { font-size: 1.1rem; font-weight: 700; color: var(--brand); letter-spacing: -.3px; }
  .topbar .meta  { font-size: .8rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: 1rem; }
  .topbar .meta strong { color: var(--text); }

  .card-pos {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .table thead th {
    background: #1e2530;
    color: #fff;
    font-weight: 600;
    font-size: .85rem;
    letter-spacing: .03em;
    border: none;
  }

  .table tbody tr:hover { background: #f0f4ff; }
  .table tfoot td { background: #e9ecef; font-weight: 700; }

  .modal-header { border-bottom: 2px solid var(--brand); }
  .modal-footer { border-top: 1px solid var(--border); }

  #finalizarModal .total-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--success);
  }

  #troco.negativo { color: var(--danger); font-weight: 700; }
  #troco.positivo { color: var(--success); font-weight: 700; }

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
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
</head>

<body>

<!-- =====================
     TOPBAR
===================== -->
<div class="topbar">

  <span class="brand">🏪 Mambo System Sales</span>

  <div class="meta">
    <span><strong>Operador:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
    <span><strong>Recibo:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
    <span><strong>Data:</strong> <?= date('d/m/Y H:i') ?></span>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">

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

    <a href="factura_cotacao.php?tipo=factura&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
       class="btn btn-sm btn-outline-primary">
      🧾 Gerar Fatura
    </a>

    <a href="../src/View/cotacao.view.php?tipo=cotacao&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
       class="btn btn-sm btn-outline-success">
      📄 Gerar Cotação
    </a>

    <a href="/Mambo_system_sales_95/client/auth/logout.php" class="btn btn-sm btn-outline-danger">
      🔒 Sair
    </a>

  </div>
</div>


<!-- =====================
     CONTEÚDO PRINCIPAL
===================== -->
<div class="container-fluid px-4 pt-4">

  <?php if ($erro_flash) : ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      ⚠️ <?= htmlspecialchars($erro_flash) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Adicionar Produto -->
  <div class="card-pos">
    <form method="post" class="row g-3 align-items-end" autocomplete="off">

      <div class="col-md-6">
        <label for="busca_produto" class="form-label fw-semibold">Código de Barras / Nome do Produto</label>
        <input type="text"
               id="busca_produto"
               name="busca_produto"
               class="form-control form-control-lg"
               placeholder="Pesquise por código ou nome…"
               required autofocus>
      </div>

      <div class="col-md-2">
        <label for="quantidade" class="form-label fw-semibold">Qtd.</label>
        <input type="number" id="quantidade" name="quantidade"
               min="1" value="1" class="form-control form-control-lg" required>
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

  <!-- Tabela Carrinho -->
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
                <button type="button"
                        class="btn btn-outline-danger btn-sm btn-remover"
                        data-codigo="<?= htmlspecialchars($codigo) ?>">
                  🗑 Remover
                </button>
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
        <h5 class="modal-title" id="lblCadastrarCliente">Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body row g-2">
        <div class="col-md-8">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input class="form-control" name="nome_cliente" id="nome_cliente" placeholder="Nome" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Apelido</label>
          <input class="form-control" name="apelido_cliente" id="apelido_cliente" placeholder="Apelido">
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefone <span class="text-danger">*</span></label>
          <input class="form-control" name="telefone_cliente" id="telefone_cliente" type="tel" placeholder="8X XXX XXXX" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefone Alternativo</label>
          <input class="form-control" name="telefone_alt_cliente" id="telefone_alt_cliente" type="tel">
        </div>
        <div class="col-12">
          <label class="form-label">Email</label>
          <input class="form-control" name="email_cliente" id="email_cliente" type="email" placeholder="email@exemplo.com">
        </div>
        <div class="col-12">
          <label class="form-label">Morada</label>
          <textarea class="form-control" name="morada_cliente" id="morada_cliente" rows="2" placeholder="Endereço"></textarea>
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
        <form id="formBuscarCliente" class="d-flex gap-2 mb-3">
          <input id="buscar_cliente_input"
                 class="form-control"
                 placeholder="Nome ou telefone…"
                 minlength="2">
          <button class="btn btn-primary" type="submit">🔍 Buscar</button>
        </form>
        <div id="resultado_busca_cliente" style="max-height:320px; overflow-y:auto;"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>

    </div>
  </div>
</div>


<!-- ============================================================
     MODAL — AUTORIZAÇÃO PARA REMOVER
============================================================ -->
<div class="modal fade" id="modalAutorizacao" tabindex="-1" aria-labelledby="lblAutorizacao" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formAutorizacao" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblAutorizacao">🔐 Autorização Necessária</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="codigoProdutoRemover">
        <p class="text-muted small mb-3">Para remover um produto do carrinho é necessária a senha de gerente ou supervisor.</p>
        <label class="form-label fw-semibold">Senha de autorização</label>
        <input type="password" class="form-control" id="senha_autorizacao" placeholder="••••••" required>
        <div id="erro_autorizacao" class="text-danger small mt-2 d-none">
          ❌ Senha incorreta ou sem permissão.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="btnConfirmarRemover">Confirmar Remoção</button>
      </div>

    </form>
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

        <!-- Total em destaque -->
        <div class="text-center mb-3">
          <div class="text-muted small text-uppercase fw-semibold mb-1">Total a Pagar</div>
          <div class="total-display" id="totalFinal">
            MT <?= number_format($total, 2, ',', '.') ?>
          </div>
        </div>

        <hr>

        <!-- Desconto colaborador -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="desconto_colaborador">
          <label class="form-check-label fw-semibold" for="desconto_colaborador">
            🏷 Desconto Colaborador (10%)
          </label>
        </div>

        <!-- Método de pagamento -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Método de Pagamento</label>
          <select class="form-select" id="metodo_pagamento" required>
            <option value="">-- Selecione --</option>
            <option value="dinheiro">💵 Dinheiro</option>
            <option value="mpesa">📱 M-Pesa</option>
            <option value="emola">📱 E-Mola</option>
            <option value="cartao">💳 Cartão</option>
          </select>
        </div>

        <!-- Nº autorização (cartão/mpesa/emola) -->
        <div class="mb-3 d-none" id="div_numero_autorizacao">
          <label class="form-label fw-semibold">Nº Cartão / Autorização</label>
          <input type="text" class="form-control" id="numero_autorizacao" placeholder="Ref. de autorização">
        </div>

        <!-- Valor pago -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Valor Entregue (MT)</label>
          <input type="number" step="0.01" min="0"
                 class="form-control form-control-lg"
                 id="valor_pago" placeholder="0,00">
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

  const TOTAL_BASE = <?= json_encode((float)$total) ?>;

  /* ----------------------------------------
     ELEMENTOS GERAIS
  ---------------------------------------- */
  const formFinalizar      = document.getElementById("formFinalizarVenda");
  const valorPago          = document.getElementById("valor_pago");
  const descontoCheck      = document.getElementById("desconto_colaborador");
  const totalFinalEl       = document.getElementById("totalFinal");
  const trocoInput         = document.getElementById("troco");
  const btnConfirmar       = document.getElementById("btnConfirmarVenda");
  const metodoPagamento    = document.getElementById("metodo_pagamento");
  const divNumAuth         = document.getElementById("div_numero_autorizacao");

  /* ----------------------------------------
     MÉTODO DE PAGAMENTO — mostrar/ocultar nº auth
  ---------------------------------------- */
  metodoPagamento?.addEventListener("change", function () {
    const val = this.value;
    const precisaAuth = val !== "" && val !== "dinheiro";
    divNumAuth.classList.toggle("d-none", !precisaAuth);
  });

  /* ----------------------------------------
     CÁLCULO DE TOTAL / TROCO
  ---------------------------------------- */
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

  valorPago?.addEventListener("input", calcularTotais);
  descontoCheck?.addEventListener("change", calcularTotais);
  calcularTotais();

  /* ----------------------------------------
     FINALIZAR VENDA (AJAX + PDF download)
  ---------------------------------------- */
  formFinalizar?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const desconto = descontoCheck?.checked ? 0.10 : 0;
    const total    = TOTAL_BASE * (1 - desconto);
    const pago     = parseFloat(valorPago?.value) || 0;
    const metodo   = metodoPagamento?.value || "";

    if (!metodo) {
      alert("⚠️ Selecione o método de pagamento.");
      metodoPagamento.focus();
      return;
    }

    if (pago < total) {
      alert("⚠️ Valor pago é insuficiente para cobrir o total da venda.");
      valorPago.focus();
      return;
    }

    if (btnConfirmar) {
      btnConfirmar.disabled = true;
      btnConfirmar.textContent = "⏳ Processando…";
    }

    const payload = {
      metodo_pagamento:   metodo,
      numero_autorizacao: document.getElementById("numero_autorizacao")?.value || "",
      valor_pago:         pago,
      desconto:           descontoCheck?.checked || false
    };

    try {
      const res  = await fetch("ajax/finalizar_venda.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload)
      });

      const data = await res.json();

      if (!data.success) {
        alert("❌ " + (data.message || "Erro ao finalizar venda."));
        if (btnConfirmar) {
          btnConfirmar.disabled = false;
          btnConfirmar.textContent = "✅ Confirmar Venda";
        }
        return;
      }

      alert("✅ Venda finalizada com sucesso!");

      // Download do PDF/recibo se disponível
      if (data.venda_id) {
        const pdfUrl = data.pdf_url || `gerar_recibo.php?venda_id=${data.venda_id}`;
        window.location.href = pdfUrl;
        setTimeout(() => { location.href = "index.php"; }, 1200);
      } else {
        location.reload();
      }

    } catch (err) {
      console.error("Erro ao finalizar venda:", err);
      alert("❌ Erro de comunicação com o servidor.");
      if (btnConfirmar) {
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = "✅ Confirmar Venda";
      }
    }
  });

  /* ----------------------------------------
     REMOVER PRODUTO — abre modal de autorização
  ---------------------------------------- */
  document.querySelectorAll(".btn-remover").forEach(btn => {
    btn.addEventListener("click", function () {
      const codigo = this.dataset.codigo;
      document.getElementById("codigoProdutoRemover").value = codigo;
      document.getElementById("senha_autorizacao").value = "";
      document.getElementById("erro_autorizacao").classList.add("d-none");

      bootstrap.Modal.getOrCreateInstance(
        document.getElementById("modalAutorizacao")
      ).show();
    });
  });

  /* ----------------------------------------
     AUTORIZAÇÃO CONFIRMADA → REMOVER
  ---------------------------------------- */
  document.getElementById("formAutorizacao")?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const codigo = document.getElementById("codigoProdutoRemover").value;
    const senha  = document.getElementById("senha_autorizacao").value;
    const erroDiv = document.getElementById("erro_autorizacao");
    const btnRem  = document.getElementById("btnConfirmarRemover");

    erroDiv.classList.add("d-none");
    btnRem.disabled = true;
    btnRem.textContent = "⏳ Verificando…";

    try {
      // 1. Valida senha
      const resAuth = await fetch("validar_autorizacao.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ codigo, senha })
      });

      const auth = await resAuth.json();

      if (!auth.success) {
        erroDiv.classList.remove("d-none");
        btnRem.disabled = false;
        btnRem.textContent = "Confirmar Remoção";
        return;
      }

      // 2. Remove do carrinho
      const resRem = await fetch("ajax/remover_produto.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ codigo })
      });

      const rem = await resRem.json();

      if (rem.success) {
        // Fecha modal
        bootstrap.Modal.getInstance(
          document.getElementById("modalAutorizacao")
        )?.hide();

        // Anima remoção da linha
        const btn = document.querySelector(`.btn-remover[data-codigo="${codigo}"]`);
        const tr  = btn?.closest("tr");
        if (tr) {
          tr.style.transition = "opacity .3s";
          tr.style.opacity    = "0";
          setTimeout(() => { tr.remove(); atualizarNumeracao(); }, 300);
        } else {
          location.reload();
        }

      } else {
        alert("❌ " + (rem.message || "Erro ao remover produto."));
      }

    } catch (err) {
      console.error("Erro na autorização/remoção:", err);
      alert("❌ Erro de comunicação com o servidor.");
    }

    btnRem.disabled = false;
    btnRem.textContent = "Confirmar Remoção";
  });

  function atualizarNumeracao() {
    document.querySelectorAll("tbody tr td:first-child").forEach((td, i) => {
      if (!isNaN(td.textContent.trim())) td.textContent = i + 1;
    });
  }

  /* ----------------------------------------
     BUSCAR CLIENTE
  ---------------------------------------- */
  const formBuscar    = document.getElementById("formBuscarCliente");
  const inputBuscar   = document.getElementById("buscar_cliente_input");
  const resultadoBox  = document.getElementById("resultado_busca_cliente");

  function buscarCliente() {
    const termo = inputBuscar?.value.trim() || "";

    if (termo.length < 2) {
      if (resultadoBox) resultadoBox.innerHTML = "<p class='text-danger small'>Digite pelo menos 2 caracteres.</p>";
      return;
    }

    if (resultadoBox) resultadoBox.innerHTML = "<div class='text-muted'>⏳ Buscando…</div>";

    fetch("ajax/buscar_cliente.php?q=" + encodeURIComponent(termo))
      .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); }
        catch { throw new Error("Resposta inválida do servidor."); }
      })
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          resultadoBox.innerHTML = "<p class='text-muted'>Nenhum cliente encontrado.</p>";
          return;
        }

        let html = "<div class='list-group'>";
        data.forEach(c => {
          const nome = (c.nome + " " + (c.apelido ?? "")).trim();
          html += `
            <button type="button"
                    class="list-group-item list-group-item-action selecionar-cliente"
                    data-id="${c.id}"
                    data-nome="${nome}">
              <strong>${nome}</strong><br>
              <small class="text-muted">📞 ${c.telefone}</small>
            </button>`;
        });
        html += "</div>";
        resultadoBox.innerHTML = html;
        ativarSelecaoCliente();
      })
      .catch(err => {
        console.error(err);
        if (resultadoBox) resultadoBox.innerHTML = "<div class='alert alert-danger'>Erro ao buscar clientes.</div>";
      });
  }

  formBuscar?.addEventListener("submit", e => { e.preventDefault(); buscarCliente(); });
/* ----------------------------------------
   SELECIONAR CLIENTE DA LISTA (REFATORADO)
---------------------------------------- */
function ativarSelecaoCliente() {

  document.querySelectorAll(".selecionar-cliente").forEach(btn => {

    // evita duplicação de listeners de forma segura
    if (btn.dataset.bound === "1") return;
    btn.dataset.bound = "1";

    btn.addEventListener("click", async function () {

      const id = this.dataset.id;
      const nome = this.dataset.nome;

      if (!id) return;

      const originalText = this.innerText;
      this.style.pointerEvents = "none";
      this.innerText = "A selecionar...";

      try {

        const res = await fetch("/ajax/set_cliente.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            cliente_id: id
          })
        });

        const data = await res.json();

        if (!data.success) {
          throw new Error(data.message || "Falha ao selecionar cliente");
        }

        const texto = document.getElementById("clienteSelecionadoTexto");
        const input = document.getElementById("clienteSelecionadoId");

        if (texto) texto.textContent = nome;
        if (input) input.value = id;

        const modalEl = document.getElementById("modalBuscarCliente");
        const modal = bootstrap.Modal.getInstance(modalEl);

        if (modal) {
          modal.hide();
        }

        limparBackdrop();

      } catch (err) {
        console.error(err);
        alert("Erro ao selecionar cliente.");
      }

      this.style.pointerEvents = "auto";
      this.innerText = originalText;
    });
  });
}


/* ----------------------------------------
   LIMPAR MODAL (VERSÃO SEGURA)
---------------------------------------- */
function limparBackdrop() {

  const modalEl = document.getElementById("modalBuscarCliente");

  // espera Bootstrap terminar animação de forma natural
  if (!modalEl) return;

  modalEl.addEventListener("hidden.bs.modal", function () {
    document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
    document.body.classList.remove("modal-open");
    document.body.style.overflow = "";
  }, { once: true });
}

  /* ----------------------------------------
     CADASTRAR CLIENTE
  ---------------------------------------- */
  const formCadastrar = document.getElementById("formCadastrarCliente");

  formCadastrar?.addEventListener("submit", function (e) {
    e.preventDefault();

    fetch("ajax/cadastrar_cliente.php", {
      method: "POST",
      body:   new FormData(formCadastrar)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("✅ Cliente cadastrado com sucesso!");
        formCadastrar.reset();

        document.getElementById("clienteSelecionadoTexto").textContent = data.cliente.nome;
        document.getElementById("clienteSelecionadoId").value = data.cliente.id;

        bootstrap.Modal.getInstance(document.getElementById("modalCadastrarCliente"))?.hide();
        limparBackdrop();
      } else {
        alert("❌ " + (data.message || "Erro ao cadastrar cliente."));
      }
    })
    .catch(err => {
      console.error(err);
      alert("❌ Erro de comunicação com o servidor.");
    });
  });

  /* ----------------------------------------
     ATALHO F9
  ---------------------------------------- */
  document.addEventListener("keydown", function (e) {
    if (e.key === "F9") {
      e.preventDefault();
      <?php if (!empty($carrinho)) : ?>
        bootstrap.Modal.getOrCreateInstance(document.getElementById("finalizarModal")).show();
      <?php endif; ?>
    }
  });

});/* /DOMContentLoaded */
</script>

</body>
</html>