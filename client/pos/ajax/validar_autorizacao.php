<?php

require_once '../../helpers/auth.php';
require_once '../../helpers/response.php';

verificarCaixa();

$data = json_decode(file_get_contents("php://input"), true);

$senha = $data['senha'] ?? '';

$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($senha !== '1234') { // depois ligas à DB
    jsonResponse(false, 'Senha inválida');
}

jsonResponse(true, 'Autorizado');