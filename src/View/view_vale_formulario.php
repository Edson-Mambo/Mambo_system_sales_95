<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';


// Conexão
$pdo = Database::conectar();

// Exemplo: definir nome do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário não identificado';

// Gerar ou pegar o número do vale (pode ser um número incremental, UUID, etc.)
$numero_vale = $_SESSION['numero_vale'] ?? uniqid('vale_');
$_SESSION['numero_vale'] = $numero_vale;

// Inicializa carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
$carrinho = &$_SESSION['carrinho'];
$mensagem = '';

// ======================================
// Se vier ?id_vale=xx -> carregar vale
// ======================================

if (isset($_GET['id_vale'])) {
    $id_vale = intval($_GET['id_vale']);

    // Busca o vale com cliente
    $stmt = $pdo->prepare("
        SELECT v.*, c.nome AS cliente_nome, c.id AS cliente_id
        FROM vales v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ?
    ");
    $stmt->execute([$id_vale]);
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vale) {
        // Atualiza sessão cliente_id
        $_SESSION['cliente_id'] = $vale['cliente_id'];

        // Limpa carrinho atual
        $_SESSION['carrinho'] = [];

        // Busca itens do vale
        $stmtItens = $pdo->prepare("
            SELECT iv.*, p.nome 
            FROM itens_vale iv
            JOIN produtos p ON p.id = iv.produto_id
            WHERE iv.vale_id = ?
        ");
        $stmtItens->execute([$id_vale]);
        $itens_vale = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        foreach ($itens_vale as $item) {
            $_SESSION['carrinho'][$item['produto_id']] = [
                'nome' => $item['nome'],
                'quantidade' => $item['quantidade'],
                'preco' => (float)$item['preco_unitario'],
            ];
        }

        // Redireciona para limpar o GET da URL e evitar recarregar sem querer
        header("Location: view_vale_formulario.php");
        exit;
    } else {
        $mensagem = "Vale não encontrado!";
    }
}

// ===================
// Adicionar produto
// ===================
if (isset($_POST['adicionar_produto'])) {
    $busca = trim($_POST['produto_busca']);
    $quantidade = (int)($_POST['quantidade'] ?? 1);
    if ($quantidade < 1) $quantidade = 1;

    $stmt = $pdo->prepare("
        SELECT id, nome, preco FROM produtos 
        WHERE id = ? OR codigo_barra = ? OR nome LIKE ? 
        LIMIT 1
    ");
    $idBusca = is_numeric($busca) ? (int)$busca : 0;
    $stmt->execute([$idBusca, $busca, "%$busca%"]);

    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        $codigo = $produto['id'];

        if (isset($carrinho[$codigo])) {
            $carrinho[$codigo]['quantidade'] += $quantidade;
        } else {
            $carrinho[$codigo] = [
                'nome' => $produto['nome'],
                'quantidade' => $quantidade,
                'preco' => (float)$produto['preco'],
            ];
        }
        header('Location: view_vale_formulario.php');
        exit;
    } else {
        $mensagem = "Produto não encontrado.";
    }
}

// ===================
// Calcular total
// ===================
$total = 0;
foreach ($carrinho as $item) {
    $subtotal = $item['preco'] * $item['quantidade'];
    $total += $subtotal;
}

?>


<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8" />
<title>📋 Criar Novo Vale - MamboSystem95</title>
<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />
<style>
  #modalFinalizarVale input.form-control, #modalCadastrarCliente input.form-control {
    font-weight: bold;
  }
</style>
</head>
<body class="bg-light p-4">

<div class="container bg-white p-4 shadow rounded">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="text-primary">📋 Criar Novo Vale</h4>
    <div class="text-muted small d-flex flex-wrap align-items-center gap-3">
      <span><strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
      <span><strong>Vale nº:</strong> <?= htmlspecialchars($numero_vale) ?></span>
      <span><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s') ?></span>
    </div>
    <a href="../src/View/venda.view.php" class="btn btn-sm btn-outline-secondary">⬅️ Voltar</a>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">🔒 Terminar Sessão</a>
  </div>

  <?php if (!empty($mensagem)) : ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <div class="mb-4 d-flex gap-2 flex-wrap align-items-center">
    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalCadastrarCliente">➕ Cadastrar Cliente</button>
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalBuscarCliente">🔍 Buscar Cliente</button>
    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalBuscarValePendente">🔎 Buscar Vale Pendente</button>
    <div class="ms-auto align-self-center">
      <strong>Cliente Selecionado: </strong>
      <span id="clienteSelecionadoTexto" class="text-primary fst-italic">Nenhum cliente selecionado</span>
      <input type="hidden" id="clienteSelecionadoId" name="cliente_id" value="">
    </div>
  </div>

  <form method="post" class="row g-3 mb-4 align-items-end" id="formAdicionarProduto">
    <div class="col-md-6">
      <label for="produto_busca" class="form-label">Código ou Nome do Produto</label>
      <input type="text" id="produto_busca" name="produto_busca" class="form-control" placeholder="Digite o código ou nome" required autofocus>
    </div>
    <div class="col-md-3">
      <label for="quantidade" class="form-label">Quantidade</label>
      <input type="number" id="quantidade" name="quantidade" min="1" value="1" class="form-control" required>
    </div>
    <div class="col-md-3">
      <button type="submit" name="adicionar_produto" class="btn btn-primary w-100">➕ Adicionar Produto</button>
    </div>
  </form>

  <div class="table-responsive mb-4">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Produto</th>
          <th>Quantidade</th>
          <th>Preço Unitário (MT)</th>
          <th>Subtotal (MT)</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($carrinho)) : ?>
          <?php foreach ($carrinho as $codigo => $item) :
            $subtotal = $item['preco'] * $item['quantidade'];
          ?>
            <tr>
              <td><?= htmlspecialchars($item['nome']) ?></td>
              <td><?= $item['quantidade'] ?></td>
              <td><?= number_format($item['preco'], 2, ',', '.') ?></td>
              <td><?= number_format($subtotal, 2, ',', '.') ?></td>
              <td>
                <button type="button" class="btn btn-sm btn-danger btn-remover" data-codigo="<?= htmlspecialchars($codigo) ?>">🗑️</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center">Nenhum produto adicionado</td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="table-secondary">
          <td colspan="3" class="text-end fw-bold">Total:</td>
          <td class="fw-bold"><?= number_format($total, 2, ',', '.') ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="d-flex gap-3 mt-3 flex-wrap">
  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSalvarVale">
    💾 Salvar Vale
  </button>
  <?php if (!empty($carrinho)) : ?>
    <button
      type="button"
      class="btn btn-warning"
      data-bs-toggle="modal"
      data-bs-target="#modalFinalizarVale"
      data-id-vale="<?= htmlspecialchars($numero_vale) ?>">
      ✅ Finalizar Pagamento (F9)
    </button>
  <?php endif; ?>
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

<!-- Modal Buscar Vale Pendente -->
<div class="modal fade" id="modalBuscarValePendente" tabindex="-1" aria-labelledby="modalBuscarValePendenteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuscarValePendenteLabel">Buscar Vale Pendente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form id="formBuscarValePendente" class="mb-3 d-flex gap-2">
          <input type="text" id="buscar_vale_input" placeholder="Nome ou Telefone" class="form-control" autofocus>
          <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        <div id="resultado_busca_vale" style="max-height:300px; overflow-y:auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Autorização -->
<div class="modal fade" id="modalAutorizacao" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAutorizacao" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Autorização</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="codigoProduto" name="codigoProduto">
        <div class="mb-3">
          <label>Senha</label>
          <input type="password" id="senha_autorizacao" class="form-control">
          <div id="erro_autorizacao" class="text-danger d-none"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Confirmar</button>
      </div>
    </form>
  </div>
</div>


<!-- Modal Salvar Vale -->
<div class="modal fade" id="modalSalvarVale" tabindex="-1" aria-labelledby="modalSalvarValeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formSalvarVale" class="modal-content" method="post" action="salvar_vale.php">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSalvarValeLabel">Salvar Vale</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="numero_vale" value="<?= htmlspecialchars($numero_vale) ?>">
        <input type="hidden" name="cliente_id" id="cliente_id_salvar" value="">
        <p>Confirme que deseja salvar o vale para o cliente selecionado.</p>
        <p><strong>Cliente: </strong> <span id="clienteSelecionadoTextoSalvar" class="text-primary fst-italic">Nenhum cliente selecionado</span></p>
        <div class="mb-3">
          <label for="observacao_vale" class="form-label">Observação (opcional)</label>
          <textarea id="observacao_vale" name="observacao_vale" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Salvar Vale</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Finalizar Vale -->
<div class="modal fade" id="modalFinalizarVale" tabindex="-1" aria-labelledby="modalFinalizarValeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formFinalizarVale" class="modal-content" method="post" action="finalizar_vales.php">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFinalizarValeLabel">Finalizar Pagamento do Vale</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">

        <!-- ID do Vale -->
        <input type="hidden" name="id_vale" id="id_vale_modal">

        <!-- Total Atualizado -->
        <input type="hidden" name="total_atualizado" id="total_atualizado_modal">

        <!-- Cliente ID -->
        <input type="hidden" name="cliente_id" id="cliente_id_finalizar">

        <p><strong>Total a Pagar: </strong> MT <span id="total_pagar_texto"><?= number_format($total, 2, ',', '.') ?></span></p>

        <div class="mb-3">
          <label for="metodo_pagamento" class="form-label">Método de Pagamento</label>
          <select id="metodo_pagamento" name="metodo_pagamento" class="form-select" required>
            <option value="">Selecione...</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="mpesa">Mpesa</option>
            <option value="emola">Emola</option>
            <option value="cartao">Cartão</option>
          </select>
        </div>

        <div class="mb-3 d-none" id="campo_numero">
          <label for="numero_pagamento" class="form-label">Número da Transação</label>
          <input type="text" id="numero_pagamento" name="numero_pagamento" class="form-control" placeholder="Digite o número da transação">
        </div>

        <div class="mb-3">
          <label for="valor_pago" class="form-label">Valor Pago</label>
          <input type="number" step="0.01" min="0" id="valor_pago" name="valor_pago" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="troco" class="form-label">Troco</label>
          <input type="text" id="troco" name="troco" class="form-control" readonly value="0,00">
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Finalizar Pagamento</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>



<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
<script>
$(function() {

  const total = <?= json_encode($total ?? 0) ?>;
  const carrinhoExiste = <?= !empty($carrinho) ? 'true' : 'false' ?>;

  /** Atualiza o ID do cliente nos campos ocultos ao selecionar ou cadastrar */
  function setClienteSelecionado(id, nome) {
    $('#clienteSelecionadoTexto').text(nome);
    $('#clienteSelecionadoId').val(id);
    $('#clienteSelecionadoTextoSalvar').text(nome);
    $('#cliente_id_salvar').val(id);
    $('#cliente_id_finalizar').val(id);
  }

  /** Cadastrar Cliente via AJAX */
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
    }, 'json').fail(() => alert('Erro na requisição.'));
  });

  /** Buscar Cliente */
  $('#formBuscarCliente').submit(function(e) {
    e.preventDefault();
    const termo = $('#buscar_cliente_input').val().trim();
    if (termo.length < 2) {
      $('#resultado_busca_cliente').html('<p>Digite pelo menos 2 caracteres.</p>');
      return;
    }
    $('#resultado_busca_cliente').html('Buscando...');
    $.getJSON('buscar_cliente_ajax.php', { q: termo }, function(clientes) {
      if (clientes.length === 0) {
        $('#resultado_busca_cliente').html('<p>Nenhum cliente encontrado.</p>');
        return;
      }
      let html = '<table class="table table-bordered"><tr><th>Nome</th><th>Telefone</th><th>Ação</th></tr>';
      clientes.forEach(cliente => {
        const nome = cliente.nome + (cliente.apelido ? ' ' + cliente.apelido : '');
        html += `<tr>
          <td>${nome}</td>
          <td>${cliente.telefone}</td>
          <td><button class="btn btn-success selecionar-cliente" data-id="${cliente.id}" data-nome="${nome}">Selecionar</button></td>
        </tr>`;
      });
      html += '</table>';
      $('#resultado_busca_cliente').html(html);
    }).fail(() => $('#resultado_busca_cliente').html('<p>Erro ao buscar clientes.</p>'));
  });

  /** Selecionar Cliente */
  $('#resultado_busca_cliente').on('click', '.selecionar-cliente', function() {
    const id = $(this).data('id');
    const nome = $(this).data('nome');
    setClienteSelecionado(id, nome);
    $('#modalBuscarCliente').modal('hide');
  });

  /** Botão de Finalizar Vale seta o ID */
  $('.btn-warning[data-bs-target="#modalFinalizarVale"]').on('click', function() {
    const idVale = $(this).data('id-vale');
    $('#id_vale_modal').val(idVale);
    $('#total_atualizado_modal').val(total);
  });

  /** Mostrar/ocultar campo número de transação */
  $('#metodo_pagamento').on('change', function() {
    const metodo = $(this).val();
    if (['mpesa', 'emola', 'cartao'].includes(metodo)) {
      $('#campo_numero').removeClass('d-none');
      $('#numero_pagamento').attr('required', true);
    } else {
      $('#campo_numero').addClass('d-none');
      $('#numero_pagamento').removeAttr('required').val('');
    }
  });

  /** Calcular troco em tempo real */
  $('#valor_pago').on('input', function() {
    const pago = parseFloat($(this).val().replace(',', '.')) || 0;
    const totalAtual = parseFloat($('#total_atualizado_modal').val().replace(',', '.')) || 0;
    const troco = pago - totalAtual > 0 ? pago - totalAtual : 0;
    $('#troco').val(troco.toFixed(2).replace('.', ','));
  });

  /** Submeter Vale Finalizar via AJAX */
  $('#formFinalizarVale').submit(function(e) {
    e.preventDefault();
    const dados = $(this).serialize();
    $.post('finalizar_vale.php', dados, function(res) {
      if (res.success) {
        alert('Vale finalizado com sucesso!');
        location.href = 'vales.php';
      } else {
        alert(res.mensagem || 'Erro ao finalizar.');
      }
    }, 'json').fail(() => alert('Erro na requisição.'));
  });

  /** Salvar Vale */
  $('#formSalvarVale').submit(function(e) {
    e.preventDefault();
    const dados = $(this).serialize();
    $.post('salvar_vale.php', dados, function(res) {
      if (res.success) {
        alert(res.mensagem);
        location.reload();
      } else {
        alert('Erro: ' + res.mensagem);
      }
    }, 'json').fail(() => alert('Erro na requisição.'));
  });

  /** Buscar Vale Pendente (Opcional) */
  $('#formBuscarValePendente').submit(function(e) {
    e.preventDefault();
    const termo = $('#buscar_vale_input').val().trim();
    if (termo.length < 2) {
      $('#resultado_busca_vale').html('<p>Digite pelo menos 2 caracteres.</p>');
      return;
    }
    $('#resultado_busca_vale').html('Buscando...');
    $.get('buscar_vale_pendente.php', { termo }, function(html) {
      $('#resultado_busca_vale').html(html);
    }).fail(() => $('#resultado_busca_vale').html('<p>Erro ao buscar vales.</p>'));
  });

  /** Adicionar Produto */
  $('#formAdicionarProduto').submit(function(e) {
    e.preventDefault();
    const produto = $('#produto_busca').val().trim();
    const quantidade = parseInt($('#quantidade').val(), 10);
    if (produto && quantidade > 0) {
      $.post('adicionar_produto_ajax.php', { produto_busca: produto, quantidade }, function(res) {
        if (res.success) location.reload();
      }, 'json');
    }
  });

  /** Autorização Remover Produto */
  let codigoParaRemover = null;
  $('.btn-remover').click(function() {
    codigoParaRemover = $(this).data('codigo');
    $('#codigoProduto').val(codigoParaRemover);
    $('#senha_autorizacao').val('');
    $('#erro_autorizacao').addClass('d-none').text('');
    $('#modalAutorizacao').modal('show');
  });

  $('#formAutorizacao').submit(function(e) {
    e.preventDefault();
    const senha = $('#senha_autorizacao').val().trim();
    if (!senha) {
      $('#erro_autorizacao').removeClass('d-none').text('Informe a senha.');
      return;
    }
    $.post('autorizacao_remocao.php', {
      codigoProduto: codigoParaRemover,
      senha_autorizacao: senha
    }, function(res) {
      if (res.autorizado) {
        $.post('remover_produto.php', { codigo: codigoParaRemover }, function(res2) {
          if (res2.sucesso) {
            $('#modalAutorizacao').modal('hide');
            location.reload();
          } else {
            $('#erro_autorizacao').removeClass('d-none').text(res2.erro);
          }
        }, 'json');
      } else {
        $('#erro_autorizacao').removeClass('d-none').text('Senha incorreta.');
      }
    }, 'json').fail(() => $('#erro_autorizacao').removeClass('d-none').text('Erro na requisição.'));
  });

  /** Tecla F9 abre modal Finalizar */
  $(document).on('keydown', function(e) {
    if (e.key === 'F9' && carrinhoExiste) {
      e.preventDefault();
      $('.btn-warning[data-bs-target="#modalFinalizarVale"]').click();
    }
  });

});
</script>

</body>
</html>
