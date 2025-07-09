<?php
function registrarLog($pdo, $tipo, $descricao, $usuario_id = null, $usuario_nome = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $rota = $_SERVER['REQUEST_URI'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $dataHora = date('Y-m-d H:i:s');

    $sql = "INSERT INTO logs_sistema 
            (usuario_id, usuario_nome, tipo_log, descricao, ip_usuario, rota, user_agent, data_hora)
            VALUES 
            (:usuario_id, :usuario_nome, :tipo_log, :descricao, :ip_usuario, :rota, :user_agent, :data_hora)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':usuario_nome' => $usuario_nome,
        ':tipo_log' => $tipo,
        ':descricao' => $descricao,
        ':ip_usuario' => $ip,
        ':rota' => $rota,
        ':user_agent' => $userAgent,
        ':data_hora' => $dataHora
    ]);
}
