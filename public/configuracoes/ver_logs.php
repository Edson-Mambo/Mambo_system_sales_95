<?php
$logPath = '../../logs/sistema.log';

if (file_exists($logPath)) {
    echo "<h2>Logs do Sistema</h2><pre>";
    echo htmlspecialchars(file_get_contents($logPath));
    echo "</pre>";
} else {
    echo "<p>Arquivo de log não encontrado.</p>";
}
?>
