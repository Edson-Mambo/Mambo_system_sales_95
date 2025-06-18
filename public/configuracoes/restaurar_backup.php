<?php
session_start();

// Verifica permissão
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'gerente')) {
    header("Location: ../login.php");
    exit();
}

$host = 'localhost';
$db   = 'mambo_system_95';
$user = 'root';
$pass = '';

$backupDir = __DIR__ . '/../../backups/';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_file'])) {
    $backupFile = basename($_POST['backup_file']); // evitar path traversal
    $backupPath = $backupDir . $backupFile;

    if (file_exists($backupPath)) {
        // Comando para restaurar o banco
        $comando = "mysql --user={$user} --password=\"{$pass}\" --host={$host} {$db} < {$backupPath}";

        exec($comando, $output, $return_var);

        if ($return_var === 0) {
            $message = "Backup restaurado com sucesso: {$backupFile}";
        } else {
            $message = "Erro ao restaurar o backup.";
        }
    } else {
        $message = "Arquivo de backup não encontrado.";
    }
}

// Lista arquivos de backup
$arquivos = array_filter(scandir($backupDir), function($f) use ($backupDir) {
    return is_file($backupDir . $f) && pathinfo($f, PATHINFO_EXTENSION) === 'sql';
});
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
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (count($arquivos) === 0): ?>
        <div class="alert alert-warning">Nenhum arquivo de backup encontrado na pasta <code>backups/</code>.</div>
    <?php else: ?>
        <form method="post">
            <div class="mb-3">
                <label for="backup_file" class="form-label">Selecione o arquivo de backup para restaurar:</label>
                <select name="backup_file" id="backup_file" class="form-select" required>
                    <option value="" disabled selected>-- Escolha um backup --</option>
                    <?php foreach ($arquivos as $arquivo): ?>
                        <option value="<?= htmlspecialchars($arquivo) ?>"><?= htmlspecialchars($arquivo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja restaurar este backup? Isso pode sobrescrever os dados atuais.')">Restaurar Backup</button>
            <a href="configuracoes.php" class="btn btn-secondary ms-2">Voltar</a>
        </form>
    <?php endif; ?>
</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
