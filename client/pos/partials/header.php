<?php
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Operador Caixa';
$numero_recibo = $_SESSION['numero_recibo'] ?? '---';
?>

<div class="p-3 bg-white border rounded shadow-sm mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

    <h5 class="text-primary mb-0">Mambo POS</h5>

    <div class="small text-muted">
      <strong>Usuário:</strong> <?= htmlspecialchars($usuario_nome) ?> |
      <strong>Recibo:</strong> <?= htmlspecialchars($numero_recibo) ?> |
      <strong>Data:</strong> <?= date('d/m/Y H:i') ?>
    </div>

  </div>
</div>