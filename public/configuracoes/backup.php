<?php
session_start();
require_once 'log.php';

registrarLog($pdo, 'backup_criado', "Backup $nome_backup criado pelo usuário $user_nome", $user_id, $user_nome);


// Permissão e autenticação omitidos aqui

$host = 'localhost';
$db   = 'mambo_system_95';
$user = 'root';
$pass = '';
$backupDir = __DIR__ . '/../../backups/';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$dataHora = date('Y-m-d_H-i-s');
$backupFileName = "backup_{$db}_{$dataHora}.sql";
$backupFilePath = $backupDir . $backupFileName;

// Caminho completo do mysqldump no XAMPP Windows
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

// Monta comando com aspas e caminho correto
$comando = "\"$mysqldumpPath\" --user=$user --password=\"$pass\" --host=$host $db > \"$backupFilePath\"";

// Executa
exec($comando, $output, $resultCode);

if ($resultCode === 0) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($backupFilePath) . '"');
    header('Content-Length: ' . filesize($backupFilePath));
    readfile($backupFilePath);
    exit;
} else {
    echo "<h3>Erro ao gerar o backup. Verifique as permissões, o caminho do mysqldump ou a senha.</h3>";
    echo "<pre>Comando executado:\n$comando\n</pre>";
    echo "<pre>Output:\n" . print_r($output, true) . "</pre>";
}
