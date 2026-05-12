let timeoutBusca;
let carrinho = [];

/* =========================
   BUSCAR PRODUTO
========================= */
async function buscarProduto() {

    clearTimeout(timeoutBusca);

    timeoutBusca = setTimeout(async () => {

        const termo = document
            .getElementById("busca_produto")
            .value
            .trim();

        if (termo.length < 1) {
            document.getElementById("resultado_produtos").innerHTML = "";
            return;
        }

        try {

            const response = await fetch(
                "ajax/buscar_produto.php?term=" + encodeURIComponent(termo)
            );

            const data = await response.json();

            renderProdutos(data.data || []);

        } catch (err) {
            console.error(err);
        }

    }, 150);
}

/* =========================
   RENDER PRODUTOS
========================= */
function renderProdutos(produtos) {

    const div = document.getElementById("resultado_produtos");

    if (!produtos.length) {

        div.innerHTML = `
            <div class="text-muted p-2">
                Nenhum produto encontrado
            </div>
        `;

        return;
    }

    let html = "";

    produtos.forEach(prod => {

        html += `
            <div class="produto-item"
                onclick="adicionarCarrinho(${prod.id})">

                <div class="fw-bold">
                    ${prod.nome}
                </div>

                <div class="small text-muted">
                    Código: ${prod.codigo || ''}
                </div>

                <div class="text-success fw-bold">
                    MT ${(parseFloat(prod.preco) || 0).toFixed(2)}
                </div>

                <div class="small">
                    Stock: ${prod.stock || 0}
                </div>

            </div>
        `;
    });

    div.innerHTML = html;
}

/* =========================
   ENTER = ADICIONAR PRIMEIRO
========================= */
document
.getElementById("busca_produto")
.addEventListener("keydown", async function(e){

    if(e.key !== "Enter") return;

    e.preventDefault();

    const termo = this.value.trim();

    if(!termo) return;

    try {

        const response = await fetch(
            "ajax/buscar_produto.php?term=" + encodeURIComponent(termo)
        );

        const data = await response.json();

        if(data.data && data.data.length > 0){
            await adicionarCarrinho(data.data[0].id);
        }

    } catch(err){
        console.error(err);
    }

});

/* =========================
   ADICIONAR AO CARRINHO
========================= */
async function adicionarCarrinho(produto_id) {

    try {

        const response = await fetch(
            "ajax/adicionar_carrinho.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    produto_id,
                    quantidade: 1
                })
            }
        );

        const data = await response.json();

        if(!data.success){
            alert(data.message || "Erro ao adicionar");
            return;
        }

        // 🔥 SEM CONFIAR EM DADOS LOCAIS
        await carregarCarrinho();

        document.getElementById("busca_produto").value = "";
        document.getElementById("resultado_produtos").innerHTML = "";

        document.getElementById("busca_produto").focus();

    } catch(err){
        console.error(err);
    }
}

/* =========================
   CARREGAR CARRINHO (FONTE ÚNICA)
========================= */
async function carregarCarrinho(){

    try {

        const response = await fetch("ajax/carregar_carrinho.php");
        const data = await response.json();

        if(!data.success){
            carrinho = [];
            renderCarrinho();
            return;
        }

        carrinho = data.items || [];

        renderCarrinho();

    } catch(err){
        console.error(err);
    }
}

/* =========================
   RENDER CARRINHO
========================= */
function renderCarrinho() {

    const div = document.getElementById("carrinhoBody");

    if(!carrinho.length){

        div.innerHTML = `
            <div class="text-muted">
                Carrinho vazio
            </div>
        `;

        atualizarTotal();
        return;
    }

    let html = "";

    carrinho.forEach(item => {

        const preco = parseFloat(item.preco) || 0;
        const qtd = parseInt(item.quantidade) || 0;
        const subtotal = preco * qtd;

        html += `
            <div class="carrinho-item">

                <div>
                    <div class="fw-bold">
                        ${item.nome}
                    </div>

                    <div class="small text-muted">
                        ${qtd} x MT ${preco.toFixed(2)}
                    </div>
                </div>

                <div class="text-end">

                    <div class="fw-bold">
                        MT ${subtotal.toFixed(2)}
                    </div>

                    <button
                        class="btn btn-sm btn-danger mt-1"
                        onclick="removerItem(${item.id})">
                        X
                    </button>

                </div>

            </div>
        `;
    });

    div.innerHTML = html;

    atualizarTotal();
}

/* =========================
   TOTAL
========================= */
function atualizarTotal(){

    let total = 0;

    carrinho.forEach(item => {

        const preco = parseFloat(item.preco) || 0;
        const qtd = parseInt(item.quantidade) || 0;

        total += preco * qtd;

    });

    document.getElementById("totalVenda").innerHTML =
        "MT " + total.toFixed(2);
}

/* =========================
   REMOVER ITEM
========================= */
async function removerItem(produto_id){

    try {

        const response = await fetch(
            "ajax/remover_item.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: produto_id
                })
            }
        );

        const data = await response.json();

        if(data.success){
            await carregarCarrinho();
        }

    } catch(err){
        console.error(err);
    }
}

/* =========================
   FINALIZAR VENDA
========================= */
async function finalizarVenda(){

    if(!carrinho.length){
        alert("Carrinho vazio");
        return;
    }

    try {

        const metodo =
            document.getElementById("metodo_pagamento").value;

        const cliente_id =
            document.getElementById("clienteSelecionadoId").value;

        const response = await fetch(
            "ajax/finalizar_venda.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    metodo_pagamento: metodo,
                    cliente_id
                })
            }
        );

        const data = await response.json();

        if(!data.success){
            alert(data.message || "Erro");
            return;
        }

        alert("Venda finalizada!");

        carrinho = [];

        renderCarrinho();

        window.open(
            "gerar_recibo.php?venda_id=" + data.venda_id,
            "_blank"
        );

    } catch(err){
        console.error(err);
    }
}

/* =========================
   ESC REMOVE ÚLTIMO
========================= */
document.addEventListener("keydown", function(e){

    if(e.key === "Escape"){

        if(carrinho.length){

            const ultimo = carrinho[carrinho.length - 1];

            removerItem(ultimo.id);
        }
    }
});

/* =========================
   INIT
========================= */
window.onload = () => {

    document.getElementById("busca_produto").focus();

    // 🔥 sincronizar carrinho ao abrir
    carregarCarrinho();

};