<?php
// log.php
function registrarLog($pdo, $tipo, $descricao, $usuario_id = null, $usuario_nome = null, $ip = null) {
    $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, tipo_log, descricao, ip_usuario) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $usuario_nome, $tipo, $descricao, $ip]);
}
