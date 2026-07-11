
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
$numero_recibo = 'REC-' . time();

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
    --brand:        #1a56db;
    --brand-dark:   #1240a8;
    --brand-light:  #e8f0fe;
    --success:      #0f9d58;
    --success-bg:   #e6f4ea;
    --danger:       #d93025;
    --danger-bg:    #fce8e6;
    --warning:      #f29900;
    --surface:      #f0f2f5;
    --card:         #ffffff;
    --border:       #e0e3e8;
    --text:         #1a1d23;
    --muted:        #6b7280;
    --radius:       .6rem;
    --radius-sm:    .35rem;
    --shadow:       0 1px 4px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.05);
    --shadow-sm:    0 1px 3px rgba(0,0,0,.06);
  }

  *, *::before, *::after { box-sizing: border-box; }

  body {
    background: var(--surface);
    color: var(--text);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: .92rem;
    margin: 0;
  }

  /* ── TOPBAR ── */
  .topbar {
    background: var(--brand);
    padding: .6rem 1.25rem;
    position: sticky;
    top: 0;
    z-index: 200;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    box-shadow: 0 2px 8px rgba(26,86,219,.3);
  }

  .topbar .brand {
    font-size: 1.05rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.2px;
    display: flex;
    align-items: center;
    gap: .4rem;
  }

  .topbar .brand span { opacity: .75; font-weight: 400; font-size: .8rem; }

  .topbar .meta {
    font-size: .78rem;
    color: rgba(255,255,255,.8);
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
  }

  .topbar .meta strong { color: #fff; }

  .topbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
  }

  /* ── CLIENTE BADGE ── */
  .cliente-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 2rem;
    padding: .22rem .8rem;
    font-size: .8rem;
    font-weight: 600;
    color: #fff;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: default;
    transition: background .2s;
  }

  .cliente-badge:hover { background: rgba(255,255,255,.22); }
  .cliente-badge.tem-cliente { background: rgba(255,255,255,.25); border-color: rgba(255,255,255,.5); }

  /* ── BOTÕES TOPBAR ── */
  .topbar .btn-sm {
    font-size: .78rem;
    padding: .28rem .7rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
  }

  .btn-topbar-light {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
  }

  .btn-topbar-light:hover {
    background: rgba(255,255,255,.25);
    color: #fff;
    border-color: rgba(255,255,255,.5);
  }

  .btn-topbar-danger {
    background: rgba(220,53,69,.25);
    border: 1px solid rgba(220,53,69,.4);
    color: #ffd0d4;
  }

  .btn-topbar-danger:hover {
    background: rgba(220,53,69,.4);
    color: #fff;
  }

  /* ── CARDS ── */
  .card-pos {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.1rem 1.25rem;
    margin-bottom: 1.25rem;
  }

  .card-pos.p-0 { padding: 0; }

  /* ── PRODUTO SEARCH ── */
  .search-wrap { position: relative; }

  #resultado_produtos {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 999;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow);
    max-height: 280px;
    overflow-y: auto;
    display: none;
  }

  .produto-item {
    padding: .6rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    transition: background .15s;
  }

  .produto-item:last-child { border-bottom: none; }
  .produto-item:hover { background: var(--brand-light); }
  .produto-item .prod-nome { font-weight: 600; font-size: .88rem; }
  .produto-item .prod-meta { font-size: .77rem; color: var(--muted); }
  .produto-item .prod-preco { font-weight: 700; color: var(--brand); font-size: .9rem; white-space: nowrap; }

  /* ── TABELA CARRINHO ── */
  .table thead th {
    background: #1e2530;
    color: #e8ecf0;
    font-weight: 600;
    font-size: .82rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    border: none;
    padding: .65rem 1rem;
  }

  .table tbody tr { transition: background .12s; }
  .table tbody tr:hover { background: #f4f7ff; }

  .table tbody td {
    padding: .65rem 1rem;
    border-color: var(--border);
    vertical-align: middle;
  }

  .table tfoot td {
    background: #f0f4ff;
    font-weight: 700;
    font-size: 1rem;
    padding: .75rem 1rem;
    border-color: var(--border);
    color: var(--brand-dark);
  }

  .badge-codigo {
    font-size: .7rem;
    background: #f0f2f5;
    color: var(--muted);
    border-radius: .25rem;
    padding: .1rem .4rem;
    font-family: monospace;
  }

  /* ── TOTAL DISPLAY ── */
  .total-pill {
    display: inline-flex;
    align-items: center;
    background: var(--success-bg);
    color: var(--success);
    border: 1.5px solid #a8d5b8;
    border-radius: 2rem;
    padding: .2rem .9rem;
    font-size: .9rem;
    font-weight: 700;
    gap: .3rem;
  }

  /* ── MODAL ── */
  .modal-header {
    background: #f8f9fb;
    border-bottom: 2px solid var(--brand);
    padding: .9rem 1.25rem;
  }

  .modal-header .modal-title { font-weight: 700; font-size: .98rem; }

  .modal-footer { border-top: 1px solid var(--border); padding: .75rem 1.25rem; }

  /* ── FINALIZAR ── */
  .total-display {
    font-size: 2rem;
    font-weight: 800;
    color: var(--success);
    letter-spacing: -.5px;
  }

  .pagamento-card {
    border: 2px solid var(--border);
    border-radius: var(--radius);
    padding: .8rem 1rem;
    cursor: pointer;
    transition: all .15s;
    text-align: center;
    font-size: .82rem;
    font-weight: 600;
    color: var(--muted);
    background: #fff;
    user-select: none;
  }

  .pagamento-card:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }
  .pagamento-card.active { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }
  .pagamento-card .emoji { display: block; font-size: 1.4rem; margin-bottom: .2rem; }

  #troco { font-weight: 700; font-size: 1.1rem; }
  #troco.negativo { color: var(--danger); }
  #troco.positivo { color: var(--success); }

  /* ── CLIENTE LISTA ── */
  .cliente-item-btn {
    width: 100%;
    text-align: left;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: .65rem .9rem;
    margin-bottom: .4rem;
    cursor: pointer;
    transition: all .15s;
    display: flex;
    align-items: center;
    gap: .75rem;
  }

  .cliente-item-btn:hover { border-color: var(--brand); background: var(--brand-light); }

  .cliente-item-btn .ci-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--brand);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
    flex-shrink: 0;
  }

  .cliente-item-btn .ci-nome { font-weight: 600; font-size: .88rem; }
  .cliente-item-btn .ci-tel  { font-size: .78rem; color: var(--muted); }

  /* ── EMPTY STATE ── */
  .empty-cart {
    padding: 3.5rem 1rem;
    text-align: center;
    color: var(--muted);
  }

  .empty-cart .icon { font-size: 3rem; margin-bottom: .75rem; opacity: .4; }
  .empty-cart .msg  { font-size: .95rem; font-weight: 500; }

  /* ── ALERTS ── */
  .alert { border-radius: var(--radius-sm); font-size: .88rem; }

  /* ── FORM CONTROLS ── */
  .form-control:focus, .form-select:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(26,86,219,.12);
  }

  /* ── REMOVER LINHA ── */
  @keyframes fadeOut { to { opacity: 0; transform: translateX(20px); } }
  .linha-removendo { animation: fadeOut .3s ease forwards; }

  /* ── SPINNER INLINE ── */
  .spin {
    display: inline-block;
    width: 14px; height: 14px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin .6s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── TOAST ── */
  #toast-pos {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    min-width: 260px;
    max-width: 360px;
    background: #1e2530;
    color: #fff;
    border-radius: var(--radius);
    padding: .75rem 1.1rem;
    font-size: .87rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.2);
    opacity: 0;
    transform: translateY(12px);
    transition: opacity .25s, transform .25s;
    pointer-events: none;
  }

  #toast-pos.show { opacity: 1; transform: translateY(0); }
  #toast-pos.success { border-left: 4px solid var(--success); }
  #toast-pos.error   { border-left: 4px solid var(--danger); }
  #toast-pos.info    { border-left: 4px solid var(--brand); }
</style>
</head>
<body>

<!-- =====================
     TOPBAR
===================== -->
<div class="topbar">

  <div class="brand">
    🏪 Mambo System
    <span>/ Ponto de Venda</span>
  </div>

  <div class="meta">
    <span>👤 <strong><?= htmlspecialchars($usuario_nome) ?></strong></span>
    <span>🧾 <strong><?= htmlspecialchars($numero_recibo) ?></strong></span>
    <span>🕐 <?= date('d/m/Y H:i') ?></span>
  </div>

  <div class="topbar-actions">

    <span class="cliente-badge <?= $clienteSelecionado['id'] ? 'tem-cliente' : '' ?>"
          title="Cliente selecionado"
          id="clienteBadgeWrap">
      👤 <span id="clienteSelecionadoTexto"><?= htmlspecialchars($clienteSelecionado['nome']) ?></span>
    </span>
    <input type="hidden" id="clienteSelecionadoId" value="<?= htmlspecialchars($clienteSelecionado['id'] ?? '') ?>">

    <button class="btn btn-sm btn-topbar-light" data-bs-toggle="modal" data-bs-target="#modalBuscarCliente">
      🔍 Buscar Cliente
    </button>

    <button class="btn btn-sm btn-topbar-light" data-bs-toggle="modal" data-bs-target="#modalCadastrarCliente">
      ➕ Novo Cliente
    </button>

    <a href="factura_cotacao.php?tipo=factura&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
       class="btn btn-sm btn-topbar-light">
      🧾 Fatura
    </a>

    <a href="../src/View/cotacao.view.php?tipo=cotacao&venda_id=<?= htmlspecialchars($numero_recibo) ?>"
       class="btn btn-sm btn-topbar-light">
      📄 Cotação
    </a>

    <a href="/Mambo_system_sales_95/client/auth/logout.php" class="btn btn-sm btn-topbar-danger">
      🔒 Sair
    </a>

  </div>
</div>


<!-- =====================
     CONTEÚDO PRINCIPAL
===================== -->
<div class="container-fluid px-4 pt-3" style="max-width:1400px;">

  <?php if ($erro_flash) : ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      ⚠️ <?= htmlspecialchars($erro_flash) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Adicionar Produto -->
  <div class="card-pos">
    <form method="post" class="row g-3 align-items-end" autocomplete="off" id="formAdicionarProduto">

      <div class="col-md-6">
        <label for="busca_produto" class="form-label fw-semibold mb-1" style="font-size:.85rem;">
          Código de Barras / Nome do Produto
        </label>
        <div class="search-wrap">
          <input type="text"
                 id="busca_produto"
                 name="busca_produto"
                 class="form-control form-control-lg"
                 placeholder="Pesquise por código ou nome…"
                 required autofocus
                 autocomplete="off">
          <div id="resultado_produtos"></div>
        </div>
      </div>

      <div class="col-md-2">
        <label for="quantidade" class="form-label fw-semibold mb-1" style="font-size:.85rem;">Quantidade</label>
        <input type="number" id="quantidade" name="quantidade"
               min="1" value="1" class="form-control form-control-lg" required>
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button type="submit" name="adicionar" class="btn btn-primary btn-lg flex-fill fw-semibold">
          ➕ Adicionar
        </button>
        <?php if (!empty($carrinho)) : ?>
          <button type="button"
                  class="btn btn-lg flex-fill fw-semibold"
                  style="background:var(--success);color:#fff;border:none;"
                  data-bs-toggle="modal"
                  data-bs-target="#finalizarModal">
            ✅ Finalizar <kbd class="ms-1" style="background:rgba(255,255,255,.25);color:#fff;border-radius:4px;">F9</kbd>
          </button>
        <?php endif; ?>
      </div>

    </form>
  </div>

  <!-- Tabela Carrinho -->
  <div class="card-pos p-0 overflow-hidden">

    <!-- Cabeçalho da tabela com total -->
    <?php if (!empty($carrinho)) : ?>
    <div class="d-flex align-items-center justify-content-between px-3 py-2"
         style="border-bottom:1px solid var(--border); background:#fafbfc;">
      <span style="font-size:.8rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">
        <?= count($carrinho) ?> <?= count($carrinho) === 1 ? 'item' : 'itens' ?> no carrinho
      </span>
      <span class="total-pill">
        MT <?= number_format($total, 2, ',', '.') ?>
      </span>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0">

        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th>Produto</th>
            <th style="width:130px">Preço Unit.</th>
            <th style="width:70px" class="text-center">Qtd.</th>
            <th style="width:140px">Subtotal</th>
            <th style="width:100px" class="text-center">Ação</th>
          </tr>
        </thead>

        <tbody id="corpoCarrinho">
          <?php if (!empty($carrinho)) :
            $i = 1;
            foreach ($carrinho as $codigo => $item) :
              $subtotal = $item['preco'] * $item['quantidade'];
          ?>
            <tr>
              <td class="text-muted small text-center"><?= $i++ ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($item['nome']) ?></div>
                <span class="badge-codigo"><?= htmlspecialchars($item['codigo_barra']) ?></span>
              </td>
              <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
              <td class="text-center fw-semibold"><?= (int)$item['quantidade'] ?></td>
              <td class="fw-semibold" style="color:var(--brand-dark);">
                MT <?= number_format($subtotal, 2, ',', '.') ?>
              </td>
              <td class="text-center">
                <button type="button"
                        class="btn btn-outline-danger btn-sm btn-remover"
                        data-codigo="<?= htmlspecialchars($codigo) ?>"
                        title="Remover produto">
                  🗑
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php else : ?>
            <tr id="linhaVazia">
              <td colspan="6">
                <div class="empty-cart">
                  <div class="icon">🛒</div>
                  <div class="msg">Carrinho vazio — pesquise um produto acima para começar.</div>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>

        <tfoot>
          <tr>
            <td colspan="4" class="text-end" style="font-size:.82rem;letter-spacing:.03em;">TOTAL GERAL</td>
            <td colspan="2" style="font-size:1.1rem;">
              MT <?= number_format($total, 2, ',', '.') ?>
            </td>
          </tr>
        </tfoot>

      </table>
    </div>
  </div>

</div><!-- /container -->


<!-- ============================================================
     MODAL — BUSCAR CLIENTE
============================================================ -->
<div class="modal fade" id="modalBuscarCliente" tabindex="-1" aria-labelledby="lblBuscarCliente" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="lblBuscarCliente">🔍 Buscar Cliente</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body" style="padding:1.1rem 1.25rem;">
        <div class="d-flex gap-2 mb-3">
          <input id="buscar_cliente_input"
                 class="form-control"
                 placeholder="Nome ou telefone (mínimo 2 caracteres)…"
                 autocomplete="off">
          <button class="btn btn-primary px-4 fw-semibold" id="btnBuscarCliente" type="button">
            Buscar
          </button>
        </div>
        <div id="resultado_busca_cliente" style="max-height:360px; overflow-y:auto;"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>

    </div>
  </div>
</div>


<!-- ============================================================
     MODAL — CADASTRAR CLIENTE
============================================================ -->
<div class="modal fade" id="modalCadastrarCliente" tabindex="-1" aria-labelledby="lblCadastrarCliente" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formCadastrarCliente" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblCadastrarCliente">➕ Cadastrar Novo Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body row g-2" style="padding:1.1rem 1.25rem;">
        <div class="col-md-8">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Nome <span class="text-danger">*</span></label>
          <input class="form-control" name="nome_cliente" id="nome_cliente" placeholder="Nome" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Apelido</label>
          <input class="form-control" name="apelido_cliente" id="apelido_cliente" placeholder="Apelido">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Telefone <span class="text-danger">*</span></label>
          <input class="form-control" name="telefone_cliente" id="telefone_cliente" type="tel" placeholder="8X XXX XXXX" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Telefone Alternativo</label>
          <input class="form-control" name="telefone_alt_cliente" id="telefone_alt_cliente" type="tel">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Email</label>
          <input class="form-control" name="email_cliente" id="email_cliente" type="email" placeholder="email@exemplo.com">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" style="font-size:.83rem;">Morada</label>
          <textarea class="form-control" name="morada_cliente" id="morada_cliente" rows="2" placeholder="Endereço"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary fw-semibold" type="submit" id="btnSalvarCliente">💾 Salvar Cliente</button>
      </div>

    </form>
  </div>
</div>


<!-- ============================================================
     MODAL — AUTORIZAÇÃO PARA REMOVER
============================================================ -->
<div class="modal fade" id="modalAutorizacao" tabindex="-1" aria-labelledby="lblAutorizacao" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <form id="formAutorizacao" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblAutorizacao">🔐 Autorização Necessária</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body" style="padding:1.25rem;">
        <input type="hidden" id="codigoProdutoRemover">
        <div class="alert alert-warning py-2 mb-3" style="font-size:.83rem;">
          Para remover um produto é necessária a senha de gerente ou supervisor.
        </div>
        <label class="form-label fw-semibold">Senha de autorização</label>
        <input type="password" class="form-control form-control-lg" id="senha_autorizacao" placeholder="••••••••" required>
        <div id="erro_autorizacao" class="text-danger small mt-2 d-none">
          ❌ Senha incorreta ou sem permissão.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-semibold" id="btnConfirmarRemover">Confirmar Remoção</button>
      </div>

    </form>
  </div>
</div>


<!-- ============================================================
     MODAL — FINALIZAR VENDA
============================================================ -->
<div class="modal fade" id="finalizarModal" tabindex="-1" aria-labelledby="lblFinalizarVenda" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <form id="formFinalizarVenda" class="modal-content" novalidate>

      <div class="modal-header">
        <h5 class="modal-title" id="lblFinalizarVenda">💳 Finalizar Venda</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body" style="padding:1.25rem;">

        <!-- Total -->
        <div class="text-center mb-3 pb-3" style="border-bottom:1px solid var(--border);">
          <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;color:var(--muted);letter-spacing:.06em;margin-bottom:.3rem;">
            Total a Pagar
          </div>
          <div class="total-display" id="totalFinal">
            MT <?= number_format($total, 2, ',', '.') ?>
          </div>
        </div>

        <!-- Desconto colaborador -->
        <div class="form-check mb-3 p-3 rounded" style="background:#fffbf0;border:1px solid #fde68a;">
          <input class="form-check-input" type="checkbox" id="desconto_colaborador">
          <label class="form-check-label fw-semibold" for="desconto_colaborador" style="cursor:pointer;">
            🏷 Desconto Colaborador <span style="color:var(--success);">(−10%)</span>
          </label>
        </div>

        <!-- Método de pagamento — cards -->
        <div class="mb-3">
          <label class="form-label fw-semibold mb-2" style="font-size:.85rem;">Método de Pagamento</label>
          <div class="row g-2" id="metodosGrid">
            <div class="col-3">
              <div class="pagamento-card" data-metodo="dinheiro">
                <span class="emoji">💵</span>Dinheiro
              </div>
            </div>
            <div class="col-3">
              <div class="pagamento-card" data-metodo="mpesa">
                <span class="emoji">📱</span>M-Pesa
              </div>
            </div>
            <div class="col-3">
              <div class="pagamento-card" data-metodo="emola">
                <span class="emoji">📲</span>E-Mola
              </div>
            </div>
            <div class="col-3">
              <div class="pagamento-card" data-metodo="cartao">
                <span class="emoji">💳</span>Cartão
              </div>
            </div>
          </div>
          <input type="hidden" id="metodo_pagamento">
          <div id="erroMetodo" class="text-danger small mt-1 d-none">Selecione um método de pagamento.</div>
        </div>

        <!-- Nº autorização -->
        <div class="mb-3 d-none" id="div_numero_autorizacao">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Nº Cartão / Referência</label>
          <input type="text" class="form-control" id="numero_autorizacao" placeholder="Ref. de autorização">
        </div>

        <!-- Valor pago -->
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Valor Entregue (MT)</label>
          <input type="number" step="0.01" min="0"
                 class="form-control form-control-lg fw-semibold"
                 id="valor_pago" placeholder="0,00">
        </div>

        <!-- Troco -->
        <div class="p-3 rounded" style="background:#f8f9fb;border:1px solid var(--border);">
          <div class="d-flex justify-content-between align-items-center">
            <span style="font-size:.85rem;font-weight:600;color:var(--muted);">TROCO</span>
            <span class="fs-5 fw-bold" id="troco" style="letter-spacing:-.3px;">—</span>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-lg fw-bold px-4" type="submit" id="btnConfirmarVenda"
                style="background:var(--success);color:#fff;border:none;">
          ✅ Confirmar Venda
        </button>
      </div>

    </form>
  </div>
</div>

<!-- Toast global -->
<div id="toast-pos"></div>


<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script>
document.addEventListener("DOMContentLoaded", function () {

  const TOTAL_BASE = <?= json_encode((float)$total) ?>;

  /* ─────────────────────────────────────────
     UTILITÁRIO: TOAST
  ───────────────────────────────────────── */
  const toastEl = document.getElementById("toast-pos");
  let toastTimer;

  function toast(msg, tipo = "info") {
    clearTimeout(toastTimer);
    toastEl.textContent = msg;
    toastEl.className   = "show " + tipo;
    toastTimer = setTimeout(() => toastEl.classList.remove("show"), 3200);
  }

  /* ─────────────────────────────────────────
     BUSCA DE PRODUTO — autocomplete AJAX
  ───────────────────────────────────────── */
  const campoProduto  = document.getElementById("busca_produto");
  const resultadoDiv  = document.getElementById("resultado_produtos");
  let debounceTimer;

  campoProduto?.addEventListener("input", function () {
    const termo = this.value.trim();
    clearTimeout(debounceTimer);

    if (termo.length < 2) {
      resultadoDiv.style.display = "none";
      resultadoDiv.innerHTML = "";
      return;
    }

    debounceTimer = setTimeout(() => {
      fetch("ajax/buscar_produto.php?termo=" + encodeURIComponent(termo))
        .then(r => r.json())
        .then(data => {
          if (!Array.isArray(data) || data.length === 0) {
            resultadoDiv.innerHTML = "<div style='padding:.7rem 1rem;color:var(--muted);font-size:.85rem;'>Nenhum produto encontrado.</div>";
            resultadoDiv.style.display = "block";
            return;
          }

          let html = "";
          data.forEach(p => {
            html += `
              <div class="produto-item" data-codigo="${escHtml(p.codigo_barra)}">
                <div>
                  <div class="prod-nome">${escHtml(p.nome)}</div>
                  <div class="prod-meta">${escHtml(p.codigo_barra)}</div>
                </div>
                <div class="prod-preco">MT ${parseFloat(p.preco).toFixed(2).replace(".", ",")}</div>
              </div>`;
          });

          resultadoDiv.innerHTML = html;
          resultadoDiv.style.display = "block";

          resultadoDiv.querySelectorAll(".produto-item").forEach(item => {
            item.addEventListener("click", function () {
              campoProduto.value = this.dataset.codigo;
              resultadoDiv.style.display = "none";
              campoProduto.focus();
            });
          });
        })
        .catch(() => {
          resultadoDiv.style.display = "none";
        });
    }, 280);
  });

  // Fecha autocomplete ao clicar fora
  document.addEventListener("click", function (e) {
    if (!campoProduto?.contains(e.target) && !resultadoDiv?.contains(e.target)) {
      resultadoDiv.style.display = "none";
    }
  });

  // Navegar com teclado no autocomplete
  campoProduto?.addEventListener("keydown", function (e) {
    const items = resultadoDiv.querySelectorAll(".produto-item");
    const active = resultadoDiv.querySelector(".produto-item:focus, .produto-item.focused");
    if (!items.length || resultadoDiv.style.display === "none") return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      const next = active ? active.nextElementSibling : items[0];
      if (next) { active?.classList.remove("focused"); next.classList.add("focused"); next.focus(); }
    } else if (e.key === "Escape") {
      resultadoDiv.style.display = "none";
    }
  });

  /* ─────────────────────────────────────────
     MÉTODO DE PAGAMENTO — cards clicáveis
  ───────────────────────────────────────── */
  const metodoInput = document.getElementById("metodo_pagamento");
  const divNumAuth  = document.getElementById("div_numero_autorizacao");

  document.querySelectorAll(".pagamento-card").forEach(card => {
    card.addEventListener("click", function () {
      document.querySelectorAll(".pagamento-card").forEach(c => c.classList.remove("active"));
      this.classList.add("active");
      metodoInput.value = this.dataset.metodo;
      document.getElementById("erroMetodo").classList.add("d-none");

      const precisaAuth = ["mpesa", "emola", "cartao"].includes(this.dataset.metodo);
      divNumAuth.classList.toggle("d-none", !precisaAuth);
    });
  });

  /* ─────────────────────────────────────────
     CÁLCULO TOTAL / TROCO
  ───────────────────────────────────────── */
  const descontoCheck = document.getElementById("desconto_colaborador");
  const valorPago     = document.getElementById("valor_pago");
  const totalFinalEl  = document.getElementById("totalFinal");
  const trocoSpan     = document.getElementById("troco");

  function calcularTotais() {
    const desconto = descontoCheck?.checked ? 0.10 : 0;
    const total    = TOTAL_BASE * (1 - desconto);
    const pago     = parseFloat(valorPago?.value) || 0;
    const troco    = pago - total;

    if (totalFinalEl) {
      totalFinalEl.textContent = "MT " + total.toFixed(2).replace(".", ",");
    }

    if (trocoSpan) {
      if (pago === 0) {
        trocoSpan.textContent = "—";
        trocoSpan.className   = "";
      } else if (troco < 0) {
        trocoSpan.textContent = "Insuficiente (−MT " + Math.abs(troco).toFixed(2).replace(".", ",") + ")";
        trocoSpan.className   = "negativo fs-5 fw-bold";
      } else {
        trocoSpan.textContent = "MT " + troco.toFixed(2).replace(".", ",");
        trocoSpan.className   = "positivo fs-5 fw-bold";
      }
    }
  }

  valorPago?.addEventListener("input", calcularTotais);
  descontoCheck?.addEventListener("change", calcularTotais);
  calcularTotais();

  // Reset ao abrir modal finalizar
  document.getElementById("finalizarModal")?.addEventListener("show.bs.modal", function () {
    document.querySelectorAll(".pagamento-card").forEach(c => c.classList.remove("active"));
    metodoInput.value = "";
    if (valorPago) valorPago.value = "";
    if (divNumAuth) divNumAuth.classList.add("d-none");
    calcularTotais();
  });

  /* ─────────────────────────────────────────
     FINALIZAR VENDA (AJAX)
  ───────────────────────────────────────── */
  const formFinalizar = document.getElementById("formFinalizarVenda");
  const btnConfirmar  = document.getElementById("btnConfirmarVenda");

  formFinalizar?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const desconto = descontoCheck?.checked ? 0.10 : 0;
    const total    = TOTAL_BASE * (1 - desconto);
    const pago     = parseFloat(valorPago?.value) || 0;
    const metodo   = metodoInput?.value || "";

    if (!metodo) {
      document.getElementById("erroMetodo").classList.remove("d-none");
      return;
    }

    if (pago < total) {
      toast("⚠️ Valor pago insuficiente para cobrir o total.", "error");
      valorPago?.focus();
      return;
    }

    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spin"></span> Processando…';

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
        toast("❌ " + (data.message || "Erro ao finalizar venda."), "error");
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = "✅ Confirmar Venda";
        return;
      }

      toast("✅ Venda finalizada com sucesso!", "success");

      if (data.venda_id) {
        const pdfUrl = data.pdf_url || `gerar_recibo.php?venda_id=${data.venda_id}`;
        window.location.href = pdfUrl;
        setTimeout(() => { location.href = "index.php"; }, 1200);
      } else {
        setTimeout(() => location.reload(), 900);
      }

    } catch (err) {
      console.error("Erro ao finalizar venda:", err);
      toast("❌ Erro de comunicação com o servidor.", "error");
      btnConfirmar.disabled = false;
      btnConfirmar.innerHTML = "✅ Confirmar Venda";
    }
  });

  /* ─────────────────────────────────────────
     REMOVER PRODUTO
  ───────────────────────────────────────── */
  document.querySelectorAll(".btn-remover").forEach(btn => {
    btn.addEventListener("click", function () {
      const codigo = this.dataset.codigo;
      document.getElementById("codigoProdutoRemover").value = codigo;
      document.getElementById("senha_autorizacao").value    = "";
      document.getElementById("erro_autorizacao").classList.add("d-none");
      bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAutorizacao")).show();
    });
  });

  document.getElementById("formAutorizacao")?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const codigo  = document.getElementById("codigoProdutoRemover").value;
    const senha   = document.getElementById("senha_autorizacao").value;
    const erroDiv = document.getElementById("erro_autorizacao");
    const btnRem  = document.getElementById("btnConfirmarRemover");

    erroDiv.classList.add("d-none");
    btnRem.disabled    = true;
    btnRem.innerHTML   = '<span class="spin"></span> Verificando…';

    try {
      const resAuth = await fetch("validar_autorizacao.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ codigo, senha })
      });

      const auth = await resAuth.json();

      if (!auth.success) {
        erroDiv.classList.remove("d-none");
        btnRem.disabled  = false;
        btnRem.innerHTML = "Confirmar Remoção";
        return;
      }

      const resRem = await fetch("ajax/remover_produto.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ codigo })
      });

      const rem = await resRem.json();

      if (rem.success) {
        bootstrap.Modal.getInstance(document.getElementById("modalAutorizacao"))?.hide();

        const btn = document.querySelector(`.btn-remover[data-codigo="${CSS.escape(codigo)}"]`);
        const tr  = btn?.closest("tr");

        if (tr) {
          tr.classList.add("linha-removendo");
          setTimeout(() => { tr.remove(); atualizarNumeracao(); }, 320);
        } else {
          location.reload();
        }

        toast("🗑 Produto removido.", "info");
      } else {
        toast("❌ " + (rem.message || "Erro ao remover produto."), "error");
      }

    } catch (err) {
      console.error("Erro:", err);
      toast("❌ Erro de comunicação.", "error");
    }

    btnRem.disabled  = false;
    btnRem.innerHTML = "Confirmar Remoção";
  });

  function atualizarNumeracao() {
    document.querySelectorAll("#corpoCarrinho tr").forEach((tr, i) => {
      const td = tr.querySelector("td:first-child");
      if (td && !isNaN(td.textContent.trim())) td.textContent = i + 1;
    });
  }

  /* ─────────────────────────────────────────
     BUSCAR CLIENTE — modal
     O ajax/buscar_cliente.php deve retornar JSON
  ───────────────────────────────────────── */
  const inputBuscar  = document.getElementById("buscar_cliente_input");
  const resultadoBox = document.getElementById("resultado_busca_cliente");
  const btnBuscar    = document.getElementById("btnBuscarCliente");

  function executarBuscaCliente() {
    const termo = inputBuscar?.value.trim() || "";

    if (termo.length < 2) {
      resultadoBox.innerHTML = "<p class='text-danger small mb-0'>⚠️ Digite pelo menos 2 caracteres.</p>";
      return;
    }

    resultadoBox.innerHTML = "<div class='text-muted' style='font-size:.85rem;'><span class='spin'></span> Buscando…</div>";

    fetch("ajax/buscar_cliente.php?termo=" + encodeURIComponent(termo))
      .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); }
        catch { throw new Error("Resposta inválida do servidor."); }
      })
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          resultadoBox.innerHTML = `
            <div class="text-center py-4" style="color:var(--muted);">
              <div style="font-size:2rem;margin-bottom:.5rem;">👤</div>
              <div style="font-size:.88rem;">Nenhum cliente encontrado para "<strong>${escHtml(termo)}</strong>".</div>
            </div>`;
          return;
        }

        let html = "";
        data.forEach(c => {
          const nome    = (c.nome + " " + (c.apelido ?? "")).trim();
          const inicial = nome.charAt(0).toUpperCase();
          html += `
            <button type="button"
                    class="cliente-item-btn"
                    data-id="${c.id}"
                    data-nome="${escHtml(nome)}">
              <div class="ci-avatar">${inicial}</div>
              <div>
                <div class="ci-nome">${escHtml(nome)}</div>
                <div class="ci-tel">📞 ${escHtml(c.telefone ?? '')}</div>
              </div>
            </button>`;
        });
        resultadoBox.innerHTML = html;

        resultadoBox.querySelectorAll(".cliente-item-btn").forEach(btn => {
          btn.addEventListener("click", function () {
            const id   = this.dataset.id;
            const nome = this.dataset.nome;
            definirCliente(id, nome);
          });
        });
      })
      .catch(err => {
        console.error(err);
        resultadoBox.innerHTML = "<div class='alert alert-danger py-2' style='font-size:.85rem;'>Erro ao buscar clientes.</div>";
      });
  }

  btnBuscar?.addEventListener("click", executarBuscaCliente);

  inputBuscar?.addEventListener("keydown", function (e) {
    if (e.key === "Enter") { e.preventDefault(); executarBuscaCliente(); }
  });

  // Limpa ao abrir modal
  document.getElementById("modalBuscarCliente")?.addEventListener("show.bs.modal", function () {
    if (inputBuscar)  inputBuscar.value = "";
    if (resultadoBox) resultadoBox.innerHTML = "";
  });

  /* ─────────────────────────────────────────
     DEFINIR CLIENTE (único ponto de set)
  ───────────────────────────────────────── */
  async function definirCliente(id, nome) {
    try {
      const res  = await fetch("ajax/set_cliente.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ cliente_id: id })
      });
      const data = await res.json();

      if (!data.success) {
        toast("❌ " + (data.message || "Erro ao selecionar cliente."), "error");
        return;
      }
    } catch (err) {
      console.error("set_cliente:", err);
      toast("❌ Erro de conexão.", "error");
      return;
    }

    // Atualiza badge
    document.getElementById("clienteSelecionadoTexto").textContent = nome;
    document.getElementById("clienteSelecionadoId").value = id;
    document.getElementById("clienteBadgeWrap")?.classList.add("tem-cliente");

    // Fecha modais abertos
    ["modalBuscarCliente", "modalCadastrarCliente"].forEach(id => {
      const el = document.getElementById(id);
      if (el) bootstrap.Modal.getInstance(el)?.hide();
    });

    limparBackdrop();
    toast("👤 Cliente selecionado: " + nome, "success");
  }

  /* ─────────────────────────────────────────
     CADASTRAR CLIENTE
  ───────────────────────────────────────── */
  const formCadastrar    = document.getElementById("formCadastrarCliente");
  const btnSalvarCliente = document.getElementById("btnSalvarCliente");

  formCadastrar?.addEventListener("submit", function (e) {
    e.preventDefault();

    btnSalvarCliente.disabled = true;
    btnSalvarCliente.innerHTML = '<span class="spin"></span> Salvando…';

    fetch("ajax/cadastrar_cliente.php", {
      method: "POST",
      body:   new FormData(formCadastrar)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        formCadastrar.reset();
        definirCliente(data.cliente.id, data.cliente.nome);
      } else {
        toast("❌ " + (data.message || "Erro ao cadastrar cliente."), "error");
      }
    })
    .catch(() => { toast("❌ Erro de comunicação com o servidor.", "error"); })
    .finally(() => {
      btnSalvarCliente.disabled = false;
      btnSalvarCliente.innerHTML = "💾 Salvar Cliente";
    });
  });

  /* ─────────────────────────────────────────
     LIMPAR BACKDROP DE MODAIS
  ───────────────────────────────────────── */
  function limparBackdrop() {
    setTimeout(() => {
      document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());
      document.body.classList.remove("modal-open");
      document.body.style.overflow   = "";
      document.body.style.paddingRight = "";
    }, 380);
  }

  /* ─────────────────────────────────────────
     ATALHO F9
  ───────────────────────────────────────── */
  document.addEventListener("keydown", function (e) {
    if (e.key === "F9") {
      e.preventDefault();
      <?php if (!empty($carrinho)) : ?>
        bootstrap.Modal.getOrCreateInstance(document.getElementById("finalizarModal")).show();
      <?php endif; ?>
    }
  });

  /* ─────────────────────────────────────────
     HELPER: escapar HTML (para innerHTML dinâmico)
  ───────────────────────────────────────── */
  function escHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

});/* /DOMContentLoaded */
</script>

</body>
</html>