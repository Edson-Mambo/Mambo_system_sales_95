<?php

require_once __DIR__ . '/../../config/lang.php';
?>


<script>
let userLoggedIn = <?= isset($_SESSION['usuario_id']) ? 'true' : 'false' ?>;

// quando o Electron tentar fechar
window.electronAPI.onConfirmClose(() => {

  if (userLoggedIn) {
    let ok = confirm("Existe um utilizador logado.\nFaça logout antes de fechar.");

    if (ok) {
      window.location.href = "logout.php";
    }

    return;
  }

  window.close();
});
</script>

