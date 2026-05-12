
/* =========================
   BUSCA DE CLIENTES
========================= */
let timeoutCliente;

async function buscarCliente() {

    clearTimeout(timeoutCliente);

    timeoutCliente = setTimeout(async () => {

        const termo = document
            .getElementById("busca_cliente")
            .value
            .trim();

        const resultado = document.getElementById("resultado_clientes");

        if (termo.length < 1) {
            resultado.innerHTML = "";
            return;
        }

        try {

            const response = await fetch(
                "ajax/buscar_cliente.php?term=" + encodeURIComponent(termo)
            );

            const data = await response.json();

            renderClientes(data.data || []);

        } catch (err) {
            console.error(err);
        }

    }, 200);
}

/* =========================
   RENDER CLIENTES
========================= */
function renderClientes(clientes) {

    const div = document.getElementById("resultado_clientes");

    if (!clientes.length) {

        div.innerHTML = `
            <div class="text-muted p-2">
                Nenhum cliente encontrado
            </div>
        `;

        return;
    }

    let html = "";

    clientes.forEach(cli => {

        html += `
            <div class="cliente-item"
                onclick="selecionarCliente(${cli.id}, '${cli.nome}')">

                <div class="fw-bold">
                    ${cli.nome}
                </div>

                <div class="small text-muted">
                    ${cli.contacto || ''}
                </div>

            </div>
        `;
    });

    div.innerHTML = html;
}

/* =========================
   SELECIONAR CLIENTE
========================= */
function selecionarCliente(id, nome) {

    document.getElementById("clienteSelecionadoId").value = id;

    document.getElementById("clienteSelecionado").innerText = nome;

    document.getElementById("resultado_clientes").innerHTML = "";

    document.getElementById("busca_cliente").value = "";
}

/* =========================
   CRIAR CLIENTE
========================= */
async function salvarCliente() {

    const nome = document.getElementById("novo_cliente_nome").value.trim();
    const contacto = document.getElementById("novo_cliente_contacto").value.trim();

    if (!nome) {
        alert("Nome obrigatório");
        return;
    }

    try {

        const response = await fetch("ajax/salvar_cliente.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                nome,
                contacto
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.message || "Erro ao salvar cliente");
            return;
        }

        alert("Cliente criado com sucesso!");

        // selecionar automaticamente
        if (data.cliente) {
            selecionarCliente(data.cliente.id, data.cliente.nome);
        }

        // limpar form
        document.getElementById("novo_cliente_nome").value = "";
        document.getElementById("novo_cliente_contacto").value = "";

    } catch (err) {
        console.error(err);
        alert("Erro de conexão");
    }
}

/* =========================
   LIMPAR CLIENTE SELECIONADO
========================= */
function removerClienteSelecionado() {

    document.getElementById("clienteSelecionadoId").value = "";
    document.getElementById("clienteSelecionado").innerText = "Nenhum cliente";

}

/* =========================
   ENTER PARA BUSCAR
========================= */
document.getElementById("busca_cliente")?.addEventListener("keydown", function (e) {

    if (e.key === "Enter") {
        e.preventDefault();
        buscarCliente();
    }

});