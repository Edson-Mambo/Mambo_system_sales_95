<?php

$db = __DIR__ . '/mambo_local.db';

echo "Banco: ".$db."<br>";

if(!file_exists($db)){
    die("Banco não existe");
}

$pdo = new PDO("sqlite:".$db);

$stmt = $pdo->query("SELECT * FROM produtos");

$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($resultado);
echo "</pre>";