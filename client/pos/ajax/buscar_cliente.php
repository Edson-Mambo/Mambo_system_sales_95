<?php

require_once __DIR__ . '/../../../server/services/ClienteService.php';

$service = new ClienteService();

$termo = $_GET['termo'] ?? '';

$clientes = $service->buscar($termo);

if (!$clientes) {
    echo "<p class='text-muted'>Nenhum cliente encontrado</p>";
    exit;
}

foreach ($clientes as $c) {

    $nome = htmlspecialchars($c['nome'] . ' ' . ($c['apelido'] ?? ''));

    echo "
        <div class='border p-2 mb-1 rounded cliente-item'
             style='cursor:pointer'
             onclick='selecionarCliente({$c['id']}, \"$nome\")'>
            <strong>{$nome}</strong><br>
            <small>{$c['telefone']}</small>
        </div>
    ";
}