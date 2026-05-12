document.getElementById("formAutorizacao")?.addEventListener("submit", async (e) => {

    e.preventDefault();

    const senha = document.getElementById("senha_autorizacao").value.trim();

    const erroBox = document.getElementById("erro_autorizacao");

    // 🔥 limpar erro antes de validar
    if (erroBox) {
        erroBox.classList.add("d-none");
    }

    try {

        const response = await fetch("ajax/validar_autorizacao.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ senha })
        });

        const data = await response.json();

        if (!data.success) {

            if (erroBox) {
                erroBox.classList.remove("d-none");
                erroBox.innerText = data.message || "Senha inválida";
            }

            return;
        }

        alert("Autorizado!");

        // 🔥 opcional: fechar modal
        const modal = document.getElementById("modalAutorizacao");
        if (modal) {
            bootstrap.Modal.getInstance(modal)?.hide();
        }

        // limpar input
        document.getElementById("senha_autorizacao").value = "";

    } catch (err) {

        console.error(err);

        if (erroBox) {
            erroBox.classList.remove("d-none");
            erroBox.innerText = "Erro de conexão com servidor";
        }
    }
});