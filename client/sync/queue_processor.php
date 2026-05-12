<?php

require_once __DIR__ . '/../config/database.php';

$db = new PDO("sqlite:" . __DIR__ . "/../localdb/mambo_local.db");
$server = Database::conectar();

$queue = $db->query("SELECT * FROM sync_queue WHERE status='pending'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($queue as $item) {

    $payload = json_decode($item['payload'], true);

    try {

        if ($item['tipo'] === 'venda') {

            $stmt = $server->prepare("
                INSERT INTO vendas (id, total, created_at)
                VALUES (?, ?, datetime('now'))
            ");

            $stmt->execute([
                $payload['venda_id'],
                $payload['total']
            ]);
        }

        $update = $db->prepare("UPDATE sync_queue SET status='done' WHERE id=?");
        $update->execute([$item['id']]);

    } catch (Exception $e) {
        // mantém pendente
    }
}