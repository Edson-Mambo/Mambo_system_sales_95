<?php
session_start();

require_once '../../config/database.php';
$pdo = Database::conectar();

// Busca configurações atuais
$stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Configurações</title>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Editar Configurações do Sistema</h2>
    <form action="config_controller.php" method="POST" class="row g-3">
        <input type="hidden" name="id" value="<?= $config['id'] ?>">

        <div class="col-md-6">
            <label for="nome_admin" class="form-label">Nome do Administrador</label>
            <input type="text" name="nome_admin" id="nome_admin" class="form-control" value="<?= htmlspecialchars($config['nome_admin']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="email_admin" class="form-label">Email</label>
            <input type="email" name="email_admin" id="email_admin" class="form-control" value="<?= htmlspecialchars($config['email_admin']) ?>">
        </div>
        <div class="col-md-6">
            <label for="telefone_suporte" class="form-label">Telefone</label>
            <input type="text" name="telefone_suporte" id="telefone_suporte" class="form-control" value="<?= htmlspecialchars($config['telefone_suporte']) ?>">
        </div>
        <div class="col-md-6">
            <label for="endereco" class="form-label">Endereço</label>
            <input type="text" name="endereco" id="endereco" class="form-control" value="<?= htmlspecialchars($config['endereco']) ?>">
        </div>
        <div class="col-md-6">
            <label for="horario_atendimento" class="form-label">Horário de Atendimento</label>
            <input type="text" name="horario_atendimento" id="horario_atendimento" class="form-control" value="<?= htmlspecialchars($config['horario_atendimento']) ?>">
        </div>
        <div class="col-md-6">
            <label for="website" class="form-label">Website</label>
            <input type="url" name="website" id="website" class="form-control" value="<?= htmlspecialchars($config['website']) ?>">
        </div>
        <div class="col-md-4">
            <label for="ssl_ativado" class="form-label">SSL Ativado</label>
            <select name="ssl_ativado" id="ssl_ativado" class="form-select">
                <option value="1" <?= $config['ssl_ativado'] ? 'selected' : '' ?>>Sim</option>
                <option value="0" <?= !$config['ssl_ativado'] ? 'selected' : '' ?>>Não</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="limite_conexoes" class="form-label">Limite de Conexões</label>
            <input type="number" name="limite_conexoes" id="limite_conexoes" class="form-control" value="<?= htmlspecialchars($config['limite_conexoes']) ?>">
        </div>
        <div class="col-md-4">
            <label for="tempo_expiracao" class="form-label">Expiração Sessão (min)</label>
            <input type="number" name="tempo_expiracao" id="tempo_expiracao" class="form-control" value="<?= htmlspecialchars($config['tempo_expiracao']) ?>">
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">Salvar</button>
            <a href="configuracoes.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</body>
</html>