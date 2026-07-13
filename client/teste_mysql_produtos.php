<?php

require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

$stmt = $pdo->query("SELECT id, nome, preco FROM produtos LIMIT 10");

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";