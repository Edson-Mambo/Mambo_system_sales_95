
/* =========================
   ESTADO DO PAGAMENTO
========================= */
let pagamento = {
    metodo: "cash",
    cliente_id: null,
    observacao: ""
};

/* =========================
   SELECIONAR MÉTODO
========================= */
function selecionarMetodo(metodo) {

    pagamento.metodo = metodo;

    document.querySelectorAll(".metodo-pagamento")
        .forEach(btn => btn.classList.remove("active"));

    const el = document.getElementById("metodo_" + metodo);

    if (el) {
        el.classList.add("active");
    }
}

/* =========================
   SELECIONAR CLIENTE
========================= */
function selecionarCliente(id, nome) {

    pagamento.cliente_id = id;

    document.getElementById("clienteSelecionado").innerText = nome;

    document.getElementById("clienteSelecionadoId").value = id;
}

/* =========================
   ABRIR MODAL PAGAMENTO
========================= */
function abrirPagamento() {

    if (!carrinho || carrinho.length === 0) {
        alert("Carrinho vazio");
        return;
    }

    const modal = new bootstrap.Modal(
        document.getElementById("modalPagamento")
    );

    modal.show();
}

/* =========================
   CALCULAR TROCO
========================= */
function calcularTroco() {

    const valorPago = parseFloat(
        document.getElementById("valorPago").value || 0
    );

    const total = parseFloat(
        document.getElementById("totalVenda").dataset.total || 0
    );

    const troco = valorPago - total;

    document.getElementById("troco").innerText =
        "MT " + (troco >= 0 ? troco.toFixed(2) : "0.00");
}

/* =========================
   FINALIZAR PAGAMENTO
========================= */
async function confirmarPagamento() {

    if (!carrinho || carrinho.length === 0) {
        alert("Carrinho vazio");
        return;
    }

    try {

        const response = await fetch("ajax/finalizar_venda.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                metodo_pagamento: pagamento.metodo,
                cliente_id: pagamento.cliente_id,
                observacao: pagamento.observacao
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.message || "Erro ao finalizar venda");
            return;
        }

        alert("Pagamento realizado com sucesso!");

        // 🔥 limpar carrinho global
        carrinho = [];

        if (typeof carregarCarrinho === "function") {
            await carregarCarrinho();
        }

        // fechar modal
        const modalEl = document.getElementById("modalPagamento");
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        // imprimir recibo
        if (data.venda_id) {
            window.open(
                "gerar_recibo.php?venda_id=" + data.venda_id,
                "_blank"
            );
        }

    } catch (err) {
        console.error(err);
        alert("Erro inesperado no pagamento");
    }
}

/* =========================
   OBSERVAÇÃO
========================= */
function atualizarObservacao(valor) {
    pagamento.observacao = valor;
}

/* =========================
   ATALHOS TECLADO
========================= */
document.addEventListener("keydown", function (e) {

    // F8 = abrir pagamento
    if (e.key === "F8") {
        abrirPagamento();
    }

    // F9 = confirmar pagamento
    if (e.key === "F9") {
        confirmarPagamento();
    }

});