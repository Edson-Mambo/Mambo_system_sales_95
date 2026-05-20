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
    header("Location: /Mambo_system_sales_95/pos/abrir_caixa.php");
    exit;
}

$pdo = Database::conectar();

$stmt = $pdo->prepare("
    SELECT id 
    FROM abertura_caixa 
    WHERE id = ? 
    AND status = 'aberto'
");
$stmt->execute([(int)$abertura_id]);

if (!$stmt->fetch()) {
    unset($_SESSION['abertura_id']);
    header("Location: /Mambo_system_sales_95/pos/abrir_caixa.php?erro=caixa_fechado");
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
    'id' => null,
    'nome' => 'Cliente Geral',
    'telefone' => '',
    'email' => '',
    'morada' => '',
    'nuit' => ''
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

$id_venda = isset($_GET['id_venda']) ? (int)$_GET['id_venda'] : 0;

$venda = null;

if ($id_venda > 0) {
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
    $stmt->execute([$id_venda]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   ADICIONAR PRODUTO AO CARRINHO
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {

    $produto_busca = trim($_POST['produto_busca'] ?? '');
    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));

    if ($produto_busca !== '') {

        $stmt = $pdo->prepare("
            SELECT id, nome, codigo_barra, preco, estoque
            FROM produtos
            WHERE codigo_barra = ?
               OR nome LIKE ?
            LIMIT 1
        ");

        $stmt->execute([
            $produto_busca,
            "%{$produto_busca}%"
        ]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {

            // VERIFICAR ESTOQUE
            if ($produto['estoque'] < $quantidade) {
                $_SESSION['erro'] = "Estoque insuficiente para este produto.";
                header("Location: index.php");
                exit;
            }

            if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
                $_SESSION['carrinho'] = [];
            }

            $id = $produto['id'];

            if (isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id]['quantidade'] += $quantidade;
            } else {
                $_SESSION['carrinho'][$id] = [
                    'id' => $produto['id'],
                    'nome' => $produto['nome'],
                    'codigo_barra' => $produto['codigo_barra'],
                    'preco' => $produto['preco'],
                    'quantidade' => $quantidade
                ];
            }
        }
    }

    header("Location: index.php");
    exit;
}

/* =========================
   CARRINHO
========================= */

$carrinho = $_SESSION['carrinho'] ?? [];

if (!is_array($carrinho)) {
    $carrinho = [];
}

$total = 0;

foreach ($carrinho as $item) {
    $total += $item['preco'] * $item['quantidade'];
}

/* =========================
   RECIBO
========================= */

$numero_recibo = 'REC-' . time();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<title>POS - Mambo System</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" />

<script src="../../bootstrap/bootstrap-5.3.3/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
#finalizarModal input.form-control {
    font-weight: bold;
}
</style>
</head>

<body>

<div class="container mt-4">

<!-- =========================
     CABEÇALHO
========================= -->
<div class="p-3 bg-white border rounded shadow-sm mb-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

    <h5 class="mb-0 text-primary">Mambo System Sales</h5>

    <div class="text-muted small">
      <span class="me-3"><strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?></span>
      <span class="me-3"><strong>Recibo:</strong> <?= htmlspecialchars($numero_recibo) ?></span>
      <span><strong>Data:</strong> <?= date('d/m/Y H:i:s') ?></span>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">

      <div>
        <strong>Cliente:</strong>

        <span id="clienteSelecionadoTexto" class="text-primary fst-italic">
            <?= htmlspecialchars($clienteSelecionado['nome']) ?>
        </span>

        <input type="hidden"
                id="clienteSelecionadoId"
                value="<?= $clienteSelecionado['id'] ?>">
        </div>

      <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalCadastrarCliente">
        ➕ Cliente
      </button>

      <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#modalBuscarCliente">
        🔍 Buscar
      </button>

      <a href="factura_cotacao.php?tipo=factura&venda_id=<?= $numero_recibo ?>"
         class="btn btn-sm btn-outline-primary">
        🧾 Fatura
      </a>

      <a href="/Mambo_system_sales_95/auth/logout.php"
         class="btn btn-sm btn-outline-danger">
        🔒 Sair
      </a>

    </div>

  </div>
</div>

<!-- =========================
     FORM PRODUTO
========================= -->
<form method="post" class="row g-3 mb-4 align-items-end">

  <div class="col-md-6">
    <label class="form-label">Produto</label>
    <input type="text" id="busca_produto" name="busca_produto"
      class="form-control" placeholder="Código ou nome" required autofocus>
  </div>
 <div id="resultado_produtos"></div>

  <div class="col-md-2">
    <label class="form-label">Qtd</label>
    <input type="number" id="quantidade" name="quantidade"
      min="1" value="1" class="form-control" required>
  </div>

  <div class="col-md-4 d-flex gap-2">

    <button type="submit" name="adicionar" class="btn btn-primary">
      Adicionar
    </button>

    <?php if (!empty($carrinho)) : ?>
      <button type="button" class="btn btn-success"
        data-bs-toggle="modal" data-bs-target="#finalizarModal">
        Finalizar Venda
      </button>
    <?php endif; ?>

  </div>

</form>

<!-- =========================
     TABELA CARRINHO
========================= -->
<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">
<tr>
  <th>Produto</th>
  <th>Preço</th>
  <th>Qtd</th>
  <th>Subtotal</th>
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
        <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
        <td><?= $item['quantidade'] ?></td>
        <td>MT <?= number_format($subtotal, 2, ',', '.') ?></td>
        <td>
            <button type="button"
              class="btn btn-danger btn-sm btn-remover"
              data-codigo="<?= htmlspecialchars($codigo) ?>">
              Remover
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else : ?>
    <tr>
      <td colspan="5" class="text-center">Carrinho vazio</td>
    </tr>
<?php endif; ?>

</tbody>

<tfoot>
<tr class="table-secondary">
  <td colspan="3" class="text-end fw-bold">Total:</td>
  <td colspan="2" class="fw-bold">
    MT <?= number_format($total, 2, ',', '.') ?>
  </td>
</tr>
</tfoot>

</table>

</div>

</div>
<div class="modal fade" id="modalCadastrarCliente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formCadastrarCliente" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Cadastrar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input class="form-control mb-2" name="nome" placeholder="Nome" required>
        <input class="form-control mb-2" name="telefone" placeholder="Telefone" required>
        <input class="form-control mb-2" name="email" placeholder="Email">
        <textarea class="form-control mb-2" name="morada" placeholder="Morada"></textarea>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Salvar</button>
      </div>

    </form>
  </div>
</div>

<div class="modal fade" id="modalBuscarCliente" tabindex="-1">
  <div class="modal-dialog modal-lg">

    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Buscar Cliente</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input id="buscar_cliente_input" class="form-control mb-2"
          placeholder="Nome ou telefone">

        <button class="btn btn-primary mb-3" id="btnBuscarCliente">
          Buscar
        </button>

        <div id="resultado_busca_cliente"></div>

      </div>

    </div>

  </div>
</div>

<div class="modal fade" id="finalizarModal" tabindex="-1">
  <div class="modal-dialog">

    <form id="formFinalizarVenda" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Finalizar Venda</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <p>Total:
          <strong id="totalFinal">MT <?= number_format($total, 2, ',', '.') ?></strong>
        </p>

        <label>Valor Pago</label>
        <input type="number" step="0.01" class="form-control mb-2"
          id="valor_pago">

        <label>Desconto Colaborador</label>
        <div class="form-check mb-2">
          <input type="checkbox" id="desconto_colaborador" class="form-check-input">
          <label class="form-check-label">Aplicar 10%</label>
        </div>

        <label>Troco</label>
        <input class="form-control" id="troco" readonly>

      </div>

      <div class="modal-footer">
        <button class="btn btn-success" type="submit">Confirmar Venda</button>
      </div>

    </form>

  </div>
</div>

<div class="modal fade" id="finalizarModal" tabindex="-1">
  <div class="modal-dialog">

    <form id="formFinalizarVenda" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Finalizar Venda</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <p>Total:
          <strong id="totalFinal">MT <?= number_format($total, 2, ',', '.') ?></strong>
        </p>

        <label>Valor Pago</label>
        <input type="number" step="0.01" class="form-control mb-2"
          id="valor_pago">

        <label>Desconto Colaborador</label>
        <div class="form-check mb-2">
          <input type="checkbox" id="desconto_colaborador" class="form-check-input">
          <label class="form-check-label">Aplicar 10%</label>
        </div>

        <label>Troco</label>
        <input class="form-control" id="troco" readonly>

      </div>

      <div class="modal-footer">
        <button class="btn btn-success" type="submit">Confirmar Venda</button>
      </div>

    </form>

  </div>
</div>

<script>

/* =========================
   TROCO + DESCONTO
========================= */
function calcularTotal() {

    let total = parseFloat(<?= json_encode($total) ?>);

    const desconto = document.getElementById("desconto_colaborador");
    const valorPago = document.getElementById("valor_pago");

    if (desconto.checked) {
        total = total - (total * 0.10);
    }

    document.getElementById("totalFinal").innerText =
        "MT " + total.toFixed(2);

    const pago = parseFloat(valorPago.value || 0);
    const troco = pago - total;

    document.getElementById("troco").value =
        troco >= 0 ? troco.toFixed(2) : "0.00";
}

/* =========================
   EVENTOS
========================= */
const valorPago = document.getElementById("valor_pago");
const descontoColaborador = document.getElementById("desconto_colaborador");
const formFinalizarVenda = document.getElementById("formFinalizarVenda");

if (valorPago) {
    valorPago.addEventListener("input", calcularTotal);
}

if (descontoColaborador) {
    descontoColaborador.addEventListener("change", calcularTotal);
}

if (formFinalizarVenda) {
    formFinalizarVenda.addEventListener("submit", function(e){
        e.preventDefault();

        const data = {
            valor_pago: valorPago.value,
            desconto: descontoColaborador.checked
        };

        fetch("ajax/finalizar_venda.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert("Venda finalizada!");
                window.location.reload();
            }
        });
    });
}

/* =========================
   FINALIZAR VENDA
========================= */
document.getElementById("formFinalizarVenda").addEventListener("submit", function(e){
    e.preventDefault();

    const data = {
        valor_pago: document.getElementById("valor_pago").value,
        desconto: document.getElementById("desconto_colaborador").checked
    };

    fetch("ajax/finalizar_venda.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert("Venda finalizada!");
            window.location.reload();
        }
    });

});

/* =========================
   BUSCAR produto
========================= */

document.getElementById("busca_produto").addEventListener("input", function () {

    const termo = this.value.trim();

    if (termo.length < 1) {
        document.getElementById("resultado_produtos").innerHTML = "";
        return;
    }

    fetch("ajax/buscar_produto.php?termo=" + encodeURIComponent(termo))
        .then(res => res.json())
        .then(produtos => {

            let html = "";

            if (!produtos.length) {
                html = `<div class="alert alert-warning">Nenhum produto encontrado</div>`;
            } else {
                produtos.forEach(produto => {
                    html += `
                        <div class="border p-2 mb-2 rounded"
                             style="cursor:pointer"
                             onclick="selecionarProduto('${produto.codigo_barra}')">

                            <strong>${produto.nome}</strong><br>
                            Código: ${produto.codigo_barra}<br>
                            Preço: MT ${parseFloat(produto.preco).toFixed(2)}<br>
                            Estoque: ${produto.estoque}
                        </div>
                    `;
                });
            }

            document.getElementById("resultado_produtos").innerHTML = html;
        });
});
function selecionarProduto(codigoBarra) {
    const campo = document.getElementById("busca_produto");
    const resultado = document.getElementById("resultado_produtos");

    if (campo) campo.value = codigoBarra;
    if (resultado) resultado.innerHTML = "";
}

/* =========================
   BUSCAR CLIENTE
========================= */
document.getElementById("btnBuscarCliente").addEventListener("click", function(){

    const termo = document.getElementById("buscar_cliente_input").value;

    fetch("ajax/buscar_cliente.php?termo=" + termo)
    .then(res => res.text())
    .then(html => {
        document.getElementById("resultado_busca_cliente").innerHTML = html;
    });

});

/* =========================
   SELECIONAR CLIENTE
========================= */
function selecionarCliente(id, nome) {

    const elTexto = document.getElementById("clienteSelecionadoTexto");
    const elInput = document.getElementById("clienteSelecionadoId");

    if (elTexto) {
        elTexto.innerText = nome;
    } else {
        console.warn("Elemento clienteSelecionadoTexto não encontrado");
    }

    if (elInput) {
        elInput.value = id;
    }

    // fechar modal
    const modalEl = document.getElementById('modalBuscarCliente');
    const modal = bootstrap.Modal.getInstance(modalEl);

    if (modal) modal.hide();

    // sessão backend
    fetch("ajax/set_cliente.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ cliente_id: id })
    });
}

</script>

</body>
</html>