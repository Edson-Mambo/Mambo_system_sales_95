<?php
session_start();

// Verifica permissão
if (!isset($_SESSION['usuario_id']) || 
    ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'gerente')) {
    header("Location: ../login.php");
    exit();
}

// Configurações do banco
$host = 'localhost';
$db   = 'mambo_system_95';
$user = 'root';
$pass = '';

// Diretório onde estão os backups (relativo ao arquivo atual)
$backupDir = __DIR__ . '/../../backups/';
$message = '';

// Função para listar arquivos SQL no diretório de backup
function listarBackups(string $dir): array {
    $files = array_filter(scandir($dir), function($f) use ($dir) {
        return is_file($dir . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'sql';
    });
    return $files ?: [];
}

$arquivos = listarBackups($backupDir);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_file'])) {
    $backupFile = basename($_POST['backup_file']); // evitar path traversal
    $backupPath = $backupDir . $backupFile;

    if (file_exists($backupPath)) {
        // Ajuste este caminho conforme seu servidor (ex: 'C:\\xampp\\mysql\\bin\\mysql.exe' no Windows)
        $mysqlBin = '/usr/bin/mysql';

        // Escapar argumentos para segurança
        $backupPathEscaped = escapeshellarg($backupPath);
        $userEscaped = escapeshellarg($user);
        $passEscaped = escapeshellarg($pass);
        $hostEscaped = escapeshellarg($host);
        $dbEscaped = escapeshellarg($db);

        // Comando usando bash para redirecionar o arquivo
        $comando = "bash -c '{$mysqlBin} --user={$userEscaped} --password={$passEscaped} --host={$hostEscaped} {$dbEscaped} < {$backupPathEscaped}'";

        exec($comando . ' 2>&1', $output, $return_var);

        if ($return_var === 0) {
            $message = "✅ Backup restaurado com sucesso: <strong>{$backupFile}</strong>";
        } else {
            $message = "❌ Erro ao restaurar o backup:<br>" . nl2br(htmlspecialchars(implode("\n", $output)));
        }
    } else {
        $message = "❌ Arquivo de backup não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Restaurar Backup - Mambo System 95</title>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" />
</head>
<body>
<div class="container mt-5">
    <h2>Restaurar Backup</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <?php if (count($arquivos) === 0): ?>
        <div class="alert alert-warning">Nenhum arquivo de backup encontrado na pasta <code>backups/</code>.</div>
    <?php else: ?>
        <form method="post" onsubmit="return confirm('Tem certeza que deseja restaurar este backup? Isso pode sobrescrever os dados atuais.')">
            <div class="mb-3">
                <label for="backup_file" class="form-label">Selecione o arquivo de backup para restaurar:</label>
                <select name="backup_file" id="backup_file" class="form-select" required>
                    <option value="" disabled selected>-- Escolha um backup --</option>
                    <?php foreach ($arquivos as $arquivo): ?>
                        <option value="<?= htmlspecialchars($arquivo) ?>"><?= htmlspecialchars($arquivo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger">Restaurar Backup</button>
            <a href="configuracoes.php" class="btn btn-secondary ms-2">Voltar</a>
        </form>
    <?php endif; ?>
</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
