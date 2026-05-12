<div class="table-responsive bg-white p-3 border rounded">

<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
  <th>Produto</th>
  <th>Preço</th>
  <th>Qtd</th>
  <th>Total</th>
  <th></th>
</tr>
</thead>

<tbody id="listaCarrinho">

<?php if (!empty($carrinho)): ?>
    <?php foreach ($carrinho as $codigo => $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['nome']) ?></td>
            <td>MT <?= number_format($item['preco'], 2, ',', '.') ?></td>
            <td><?= $item['quantidade'] ?></td>
            <td>MT <?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?></td>
            <td>
                <button class="btn btn-sm btn-danger btn-remover" data-codigo="<?= $codigo ?>">
                    X
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
  <td colspan="5" class="text-center text-muted">Carrinho vazio</td>
</tr>
<?php endif; ?>

</tbody>

<tfoot>
<tr class="table-secondary">
  <td>
    MT <?= number_format((float)($item['preco'] ?? 0), 2, ',', '.') ?>
</td>

<td><?= (int)($item['quantidade'] ?? 0) ?></td>

<td>
    MT <?= number_format(
        (float)($item['preco'] ?? 0) * (int)($item['quantidade'] ?? 0),
        2,
        ',',
        '.'
    ) ?>
</td>
</tr>
</tfoot>

</table>

</div>