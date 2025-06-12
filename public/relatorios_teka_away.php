<?php
session_start();
require_once '../config/database.php';
$pdo = Database::conectar();

$stmt = $pdo->query("SELECT id, nome, preco, imagem FROM produtos_takeaway");
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-MZ">
<head>
    <meta charset="UTF-8">
    <title>Menu Teka Away</title>
    <link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
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
        .carrinho {
            position: fixed;
            right: 20px;
            top: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            width: 340px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .toast-container {
            position: fixed;
            top: 10px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mt-4 mb-4 text-center">Menu Teka Away</h2>
    <div class="row">
        <?php foreach ($produtos as $produto): ?>
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card produto text-center p-2">
                    <img src="imagens/<?= $produto['imagem'] ?>" class="produto-img mx-auto" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($produto['nome']) ?></h5>
                        <p class="card-text"><?= number_format($produto['preco'], 2) ?> MZN</p>
                        <button class="btn btn-success btn-sm adicionar-btn"
                            data-id="<?= $produto['id'] ?>"
                            data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                            data-preco="<?= $produto['preco'] ?>">Adicionar ao Carrinho</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="carrinho" id="carrinho">
    <h5>Carrinho</h5>
    <ul id="itensCarrinho" class="list-group mb-2"></ul>
    <strong>Total: <span id="total">0.00</span> MZN</strong><br>
    <button id="finalizarVenda" class="btn btn-primary mt-2">Finalizar Venda</button>
</div>

<div class="toast-container" id="toast-container"></div>

<!-- ✅ jQuery carregado corretamente -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- ✅ Bootstrap Bundle (inclui Popper) -->
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Script da lógica do carrinho -->
<script>
$(document).ready(function() {
    console.log('jQuery carregado e DOM pronto!');

    let carrinho = [];

    function showToast(msg) {
        const toastId = Date.now();
        const toast = $(`
            <div class="toast align-items-center text-white bg-success border-0 mb-2" role="alert" data-bs-delay="1500" id="toast-${toastId}">
                <div class="d-flex">
                    <div class="toast-body">${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
        $('#toast-container').append(toast);
        const bsToast = new bootstrap.Toast(document.getElementById(`toast-${toastId}`));
        bsToast.show();
    }

    $(document).on('click', '.adicionar-btn', function() {
        console.log('Botão "Adicionar ao Carrinho" clicado.');

        const id = $(this).data('id');
        const nome = $(this).data('nome');
        const preco = parseFloat($(this).data('preco'));

        const itemExistente = carrinho.find(p => p.id === id);
        if (itemExistente) {
            itemExistente.qtd += 1;
        } else {
            carrinho.push({ id, nome, preco, qtd: 1 });
        }

        renderCarrinho();
        showToast(`${nome} adicionado ao carrinho`);
    });

    function renderCarrinho() {
        $('#itensCarrinho').empty();
        let total = 0;

        carrinho.forEach((item, index) => {
            total += item.preco * item.qtd;
            $('#itensCarrinho').append(
                `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${item.nome} x${item.qtd}
                    <div>
                        <span>${(item.preco * item.qtd).toFixed(2)} MZN</span>
                        <button class="btn btn-sm btn-danger ms-2 remover-btn" data-index="${index}">&times;</button>
                    </div>
                </li>`
            );
        });

        $('#total').text(total.toFixed(2));
    }

    $(document).on('click', '.remover-btn', function() {
        const index = $(this).data('index');
        carrinho.splice(index, 1);
        renderCarrinho();
    });

    $('#finalizarVenda').click(function() {
        if (carrinho.length === 0) {
            alert("Carrinho vazio!");
            return;
        }

        $.post('finalizar_venda_teka_away.php', { carrinho: JSON.stringify(carrinho) }, function(res) {
            alert('Venda finalizada com sucesso!');
            carrinho = [];
            renderCarrinho();
        }).fail(function() {
            alert('Erro ao finalizar a venda. Tente novamente.');
        });
    });
});
</script>

</body>
</html>
