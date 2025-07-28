<?php
session_start();
require_once '../config/database.php';

$pdo = Database::conectar();

// Simula o número do recibo
if (!isset($_SESSION['nr_venda'])) {
  $_SESSION['nr_venda'] = rand(1000, 9999);
}

// Consulta produtos do takeaway
$stmt = $pdo->query("SELECT id, nome, preco, imagem FROM produtos_takeaway");
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-MZ">
<head>
  <meta charset="UTF-8" />
  <title>Menu Teka Away</title>
  <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .produto-img {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 8px;
    }
    .produto {
      transition: transform 0.2s;
    }
    .produto:hover {
      transform: scale(1.03);
    }
    .carrinho-sidebar {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      height: 100%;
    }
    .toast-container {
      position: fixed;
      top: 10px;
      right: 20px;
      z-index: 1050;
    }
  </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Menu Teka Away</a>
    <div class="ms-auto text-white">
      <?php echo isset($_SESSION['usuario_nome']) ? "Olá, " . htmlspecialchars($_SESSION['usuario_nome']) : "Usuário não logado"; ?>
      <span class="badge bg-warning text-dark ms-3">
        Nº Recibo: <?php echo isset($_SESSION['nr_venda']) ? $_SESSION['nr_venda'] : '---'; ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm ms-3">Terminar Sessão</a>
    </div>
  </div>
</nav>

<div class="container-fluid my-4">
  <div class="row">
    <!-- Coluna Produtos -->
    <div class="col-lg-9">
      <div class="row">
        <?php foreach ($produtos as $produto): ?>
          <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="card produto text-center p-2">
              <img src="imagens/<?= htmlspecialchars($produto['imagem']) ?>" class="produto-img mx-auto" alt="<?= htmlspecialchars($produto['nome']) ?>" />
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($produto['nome']) ?></h5>
                <p class="card-text"><?= number_format($produto['preco'], 2) ?> MZN</p>
                <button class="btn btn-success btn-sm adicionar-btn"
                  data-id="<?= $produto['id'] ?>"
                  data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                  data-preco="<?= $produto['preco'] ?>">
                  Adicionar ao Carrinho
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Coluna Carrinho -->
    <div class="col-lg-3">
      <div class="carrinho-sidebar">
        <h5 class="fw-bold mb-3">🛒 Carrinho</h5>
        <ul id="itensCarrinho" class="list-group mb-3"></ul>
        <div class="d-flex justify-content-between mb-2">
          <strong>Total:</strong>
          <strong><span id="total">0.00</span> MZN</strong>
        </div>
        <button id="finalizarVenda" class="btn btn-primary w-100">Finalizar Venda</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1" aria-labelledby="modalPagamentoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPagamento">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPagamentoLabel">Finalizar Venda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Total da compra: <strong id="totalCompraModal">0.00</strong> MZN</p>
          <div class="mb-3">
            <label for="valorPago" class="form-label">Valor pago (MZN):</label>
            <input type="number" min="0" step="0.01" class="form-control" id="valorPago" required />
          </div>
          <p>Troco: <strong id="troco">0.00</strong> MZN</p>
          <div id="msgErroPagamento" class="text-danger" style="display:none;">Valor pago insuficiente!</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Confirmar Pagamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Remover Produto -->
<div class="modal fade" id="modalRemoverProduto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Selecionar Produto para Remover</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul id="listaRemover" class="list-group"></ul>
        <div id="campoQtdRemover" class="mt-3" style="display:none;">
          <label>Quantidade a Remover:</label>
          <input type="number" id="qtdRemover" class="form-control" min="1" value="1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" id="confirmarRemover" class="btn btn-danger" disabled>Remover Selecionado</button>
      </div>
    </div>
  </div>
</div>

<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
  let carrinho = [];
  let indexParaRemover = null;
  let qtdParaRemover = 1;
  let modalRemover = new bootstrap.Modal(document.getElementById('modalRemoverProduto'));

  function showToast(msg) {
    const toastId = Date.now();
    const toast = $(`
      <div class="toast text-white bg-success mb-2" role="alert" data-bs-delay="1500" id="toast-${toastId}">
        <div class="d-flex">
          <div class="toast-body">${msg}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `);
    $('#toast-container').append(toast);
    const bsToast = new bootstrap.Toast(document.getElementById(`toast-${toastId}`));
    bsToast.show();
    toast.on('hidden.bs.toast', () => toast.remove());
  }

  function renderCarrinho() {
    $('#itensCarrinho').empty();
    let total = 0;
    carrinho.forEach((item, index) => {
      total += item.preco * item.qtd;
      $('#itensCarrinho').append(`
        <li class="list-group-item d-flex justify-content-between align-items-center">
          ${item.nome} x${item.qtd}
          <div>
            ${ (item.preco * item.qtd).toFixed(2) } MZN
            <button class="btn btn-sm btn-danger ms-2 remover-btn" data-index="${index}">&times;</button>
          </div>
        </li>
      `);
    });
    $('#total').text(total.toFixed(2));
  }

  $(document).on('click', '.adicionar-btn', function() {
    const id = $(this).data('id');
    const nome = $(this).data('nome');
    const preco = parseFloat($(this).data('preco'));
    const existente = carrinho.find(p => p.id === id);
    if (existente) {
      existente.qtd++;
    } else {
      carrinho.push({ id, nome, preco, qtd: 1 });
    }
    renderCarrinho();
    showToast(`${nome} adicionado ao carrinho`);
  });

  $('#finalizarVenda').click(() => abrirModalPagamento());

  function abrirModalPagamento() {
    if (!carrinho.length) {
      alert("Carrinho vazio!");
      return;
    }
    const total = carrinho.reduce((acc, i) => acc + i.preco * i.qtd, 0);
    $('#totalCompraModal').text(total.toFixed(2));
    $('#valorPago').val('');
    $('#troco').text('0.00');
    $('#msgErroPagamento').hide();
    new bootstrap.Modal(document.getElementById('modalPagamento')).show();
  }

  $('#modalPagamento').on('shown.bs.modal', function () {
    $('#valorPago').trigger('focus');
  });

  $('#valorPago').on('input', function() {
    const total = parseFloat($('#totalCompraModal').text());
    const pago = parseFloat($(this).val());
    const troco = pago - total;
    if (troco < 0) {
      $('#msgErroPagamento').show();
      $('#troco').text('0.00');
    } else {
      $('#msgErroPagamento').hide();
      $('#troco').text(troco.toFixed(2));
    }
  });

  $('#formPagamento').submit(function(e) {
    e.preventDefault();
    const total = parseFloat($('#totalCompraModal').text());
    const pago = parseFloat($('#valorPago').val());
    if (isNaN(pago) || pago < total) {
      alert('Valor pago insuficiente.');
      return;
    }
    $.post('finalizar_venda_teka_away.php', {
      carrinho: JSON.stringify(carrinho),
      valor_pago: pago
    }, function(response) {
      try {
        const res = JSON.parse(response);
        if (res.status === 'success') {
          alert('Venda finalizada! ID: ' + res.id_venda);
          carrinho = [];
          renderCarrinho();
          bootstrap.Modal.getInstance(document.getElementById('modalPagamento')).hide();
        } else {
          alert('Erro: ' + res.mensagem);
        }
      } catch {
        alert('Erro na resposta do servidor.');
      }
    });
  });

  function abrirModalRemover() {
    const lista = $('#listaRemover');
    lista.empty();
    if (!carrinho.length) {
      lista.append('<li class="list-group-item">Carrinho vazio.</li>');
      $('#confirmarRemover').prop('disabled', true);
      return;
    }
    carrinho.forEach((item, index) => {
      lista.append(`
        <li class="list-group-item remover-opcao" data-index="${index}" style="cursor:pointer;">
          ${item.nome} x${item.qtd} - ${(item.preco * item.qtd).toFixed(2)} MZN
        </li>
      `);
    });
    $('#confirmarRemover').prop('disabled', false);
    $('#campoQtdRemover').hide();
    indexParaRemover = 0;
    $('.remover-opcao').removeClass('active').eq(indexParaRemover).addClass('active');
    atualizarQtdInput();
    modalRemover.show();
  }

  function atualizarQtdInput() {
    if (indexParaRemover !== null && carrinho[indexParaRemover].qtd > 1) {
      $('#campoQtdRemover').show();
      $('#qtdRemover').val(1).attr('max', carrinho[indexParaRemover].qtd);
    } else {
      $('#campoQtdRemover').hide();
    }
  }

  $(document).on('click', '.remover-opcao', function() {
    $('.remover-opcao').removeClass('active');
    $(this).addClass('active');
    indexParaRemover = $(this).data('index');
    atualizarQtdInput();
  });

  $('#qtdRemover').on('input', function() {
    qtdParaRemover = parseInt($(this).val());
  });

  $('#confirmarRemover').click(removerSelecionado);

  function removerSelecionado() {
    if (indexParaRemover !== null) {
      let item = carrinho[indexParaRemover];
      let qtdRemover = $('#qtdRemover').val() ? parseInt($('#qtdRemover').val()) : 1;
      if (item.qtd <= 1 || qtdRemover >= item.qtd) {
        carrinho.splice(indexParaRemover, 1);
      } else {
        item.qtd -= qtdRemover;
      }
      renderCarrinho();
      modalRemover.hide();
    }
  }

  $(document).keydown(function(e) {
    if (e.key === "F9") {
      e.preventDefault();
      abrirModalPagamento();
    }
    if (e.key === "F5") {
      e.preventDefault();
      abrirModalRemover();
    }

    if ($('#modalRemoverProduto').hasClass('show')) {
      if (e.key === "ArrowDown") {
        moverSelecao(1);
      }
      if (e.key === "ArrowUp") {
        moverSelecao(-1);
      }
      if (e.key === "Enter") {
        removerSelecionado();
      }
    }
  });

  function moverSelecao(direcao) {
    const opcoes = $('.remover-opcao');
    if (!opcoes.length) return;
    let atual = opcoes.index($('.remover-opcao.active'));
    atual = (atual + direcao + opcoes.length) % opcoes.length;
    $('.remover-opcao').removeClass('active');
    $(opcoes[atual]).addClass('active');
    indexParaRemover = $(opcoes[atual]).data('index');
    atualizarQtdInput();
  }

  renderCarrinho();
});
</script>
</body>
</html>
