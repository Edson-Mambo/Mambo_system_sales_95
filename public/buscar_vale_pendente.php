<?php
require_once '../config/database.php';
$pdo = Database::conectar();

$termo = trim($_GET['termo'] ?? '');

if ($termo !== '') {
    $sql = "
        SELECT 
          v.id, 
          v.numero_vale, 
          v.status_pagamento, 
          v.valor_total,               -- ✅ Incluído aqui também!
          c.nome AS cliente_nome, 
          c.telefone AS cliente_telefone
        FROM vales v
        JOIN clientes c ON c.id = v.cliente_id
        WHERE v.status_pagamento != 'pago'
          AND (c.nome LIKE ? OR c.telefone LIKE ?)
        ORDER BY v.numero_vale DESC
    ";
    $stmt = $pdo->prepare($sql);
    $likeTermo = "%$termo%";
    $stmt->execute([$likeTermo, $likeTermo]);

} else {
    $sql = "
        SELECT 
          v.id, 
          v.numero_vale, 
          v.status_pagamento,
          v.valor_total, 
          c.nome AS cliente_nome, 
          c.telefone AS cliente_telefone
        FROM vales v
        JOIN clientes c ON c.id = v.cliente_id
        WHERE v.status_pagamento != 'pago'
        ORDER BY v.numero_vale DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$vales = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$vales) {
    echo "<p class='text-muted'>Nenhum vale pendente encontrado.</p>";
    exit;
}

echo "<table class='table table-striped table-bordered'>";
echo "<thead>
        <tr>
          <th>Vale Nº</th>
          <th>Cliente</th>
          <th>Telefone</th>
          <th>Status</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>";

foreach ($vales as $vale) {
    $id = htmlspecialchars($vale['id']);
    $numero = htmlspecialchars($vale['numero_vale']);
    $clienteNome = htmlspecialchars($vale['cliente_nome']);
    $telefone = htmlspecialchars($vale['cliente_telefone']);
    $status = htmlspecialchars($vale['status_pagamento']);
    $valorTotal = htmlspecialchars(number_format($vale['valor_total'], 2, '.', ''));

    echo "<tr>
            <td>{$numero}</td>
            <td>{$clienteNome}</td>
            <td>{$telefone}</td>
            <td>{$status}</td>
            <td>
              <button 
                class='btn btn-sm btn-primary selecionar-vale' 
                data-id='{$id}' 
                data-numero='{$numero}' 
                data-valor='{$valorTotal}'>
                Selecionar
              </button>
            </td>
          </tr>";
}

echo "</tbody></table>";
