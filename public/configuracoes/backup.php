<?php
session_start();

require_once __DIR__ . '/../../config/database.php'; // Inclui a conexão
require_once 'log.php'; // Inclui a função registrarLog

// Conexão com o banco
$pdo = Database::conectar();

// Variáveis do usuário logado
$user_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';
$user_id = $_SESSION['usuario_id'] ?? null;

// Informações do banco para o dump
$host = 'localhost';
$db   = 'mambo_system_95';
$user = 'root';
$pass = '';
$backupDir = __DIR__ . '/../../backups/';

// Cria a pasta se não existir
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Monta nome do arquivo
$dataHora = date('Y-m-d_H-i-s');
$nome_backup = "backup_{$db}_{$dataHora}.sql";
$backupFilePath = $backupDir . $nome_backup;

// Caminho do mysqldump (ajuste se precisar)
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

// Monta comando
$comando = "\"$mysqldumpPath\" --user=$user --password=\"$pass\" --host=$host $db > \"$backupFilePath\"";

// Executa o backup
exec($comando, $output, $resultCode);

// Registra log APÓS tentar gerar o backup
registrarLog(
    $pdo,
    'backup_criado',
    "Backup $nome_backup criado pelo usuário $user_nome",
    $user_id,
    $user_nome
);

// Se der certo, envia o arquivo
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
