<?php
session_start();

// Verifica se o usuário está logado e tem acesso permitido
if (!isset($_SESSION['usuario_id']) || 
    ($_SESSION['nivel_acesso'] !== 'gerente' && $_SESSION['nivel_acesso'] !== 'admin')) {
    header("Location: ../login.php");
    exit();
}

// Conexão com a base de dados
require_once '../../config/database.php';
$pdo = Database::conectar();

// Função para obter as configurações do sistema
function obterConfiguracoes($pdo) {
    try {
        $sql = "SELECT titulo, nome_admin, email_admin, telefone_suporte, horario_atendimento, endereco, website, ssl_ativado, limite_conexoes, tempo_expiracao, modo_exibicao FROM configuracoes LIMIT 1";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar configurações: " . $e->getMessage());
    }
}

$configuracoes = obterConfiguracoes($pdo);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema</title>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Configurações do Sistema - Mambo System Sales 95</h2>

    <!-- Informações do Administrador -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><strong>Informações do Administrador</strong></div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Título:</strong> <?= htmlspecialchars($configuracoes['titulo'] ?? 'Administrador') ?></li>
            <li class="list-group-item"><strong>Nome:</strong> <?= htmlspecialchars($configuracoes['nome_admin'] ?? 'Não definido') ?></li>
            <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($configuracoes['email_admin'] ?? 'não definido') ?></li>
            <li class="list-group-item"><strong>Telefone:</strong> <?= htmlspecialchars($configuracoes['telefone_suporte'] ?? 'não definido') ?></li>
            <li class="list-group-item"><strong>Endereço:</strong> <?= htmlspecialchars($configuracoes['endereco'] ?? '') ?></li>
            <li class="list-group-item"><strong>Horário de Atendimento:</strong> <?= htmlspecialchars($configuracoes['horario_atendimento'] ?? '') ?></li>
            <li class="list-group-item"><strong>Website:</strong> 
                <a href="<?= htmlspecialchars($configuracoes['website'] ?? '#') ?>" target="_blank">
                    <?= htmlspecialchars($configuracoes['website'] ?? 'sem link') ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- Informações Técnicas -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white"><strong>Opções Técnicas</strong></div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>SSL Ativado:</strong> <?= ($configuracoes['ssl_ativado'] ?? 0) ? 'Sim' : 'Não' ?></li>
            <li class="list-group-item"><strong>Limite de Conexões:</strong> <?= htmlspecialchars($configuracoes['limite_conexoes'] ?? '100') ?></li>
            <li class="list-group-item"><strong>Tempo de Expiração da Sessão (min):</strong> <?= htmlspecialchars($configuracoes['tempo_expiracao'] ?? '30') ?></li>
            <li class="list-group-item"><strong>Modo de Exibição:</strong> <?= htmlspecialchars($configuracoes['modo_exibicao'] ?? 'Padrão') ?></li>
        </ul>
    </div>

    <!-- Ações do Sistema -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white"><strong>Ações do Sistema</strong></div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><a href="backup.php" class="btn btn-outline-secondary">Fazer Backup</a></li>
            <li class="list-group-item"><a href="restaurar_backup.php" class="btn btn-outline-warning">Restaurar Backup</a></li>
            <li class="list-group-item"><a href="ver_logs.php" class="btn btn-outline-info">Ver Logs</a></li>
        </ul>
    </div>

    <div class="text-center mt-4">
    <a href="../index_admin.php" class="btn btn-secondary">← Voltar ao Painel</a>
</div>

</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
