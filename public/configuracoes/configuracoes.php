<?php
session_start();

require_once '../../config/database.php';
$pdo = Database::conectar();

/* =========================
   SEGURANÇA ERP PADRÃO
========================= */
if (empty($_SESSION['usuario_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

$nivel = $_SESSION['nivel_acesso'] ?? '';

$permitidos = ['admin', 'gerente'];

if (!in_array($nivel, $permitidos)) {
    header("Location: ../../public/index.php");
    exit;
}

/* =========================
   CONFIGURAÇÕES
========================= */
function obterConfiguracoes($pdo) {
    $sql = "SELECT * FROM configuracoes LIMIT 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$config = obterConfiguracoes($pdo);

/* =========================
   VOLTAR PADRÃO ERP
========================= */
$voltar = match($nivel) {
    'admin' => '../../public/index_admin.php',
    'gerente' => '../../public/index_gerente.php',
    default => '../../public/index.php'
};
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Configurações do Sistema</title>

<link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}

/* HEADER ERP */
.erp-header{
    background:#111827;
    color:#fff;
    padding:18px 20px;
    border-radius:10px;
    margin-bottom:20px;
}

.erp-title{
    font-size:18px;
    font-weight:700;
}

/* CARD PADRÃO ERP */
.erp-card{
    border:0;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
    margin-bottom:20px;
}

.erp-card .card-header{
    font-weight:600;
    background:#fff;
    border-bottom:1px solid #eee;
}

/* BOTÕES ERP */
.btn-erp{
    border-radius:8px;
    font-weight:500;
}

/* LISTA LIMPA */
.list-group-item{
    border:0;
    padding:10px 15px;
    background:transparent;
}
</style>

</head>
<body>

<div class="container py-4">

    <!-- HEADER -->
    <div class="erp-header d-flex justify-content-between align-items-center">
        <div class="erp-title">
            ⚙️ Configurações do Sistema
        </div>

        <a href="<?= $voltar ?>" class="btn btn-light btn-sm btn-erp">
            ← Voltar
        </a>
    </div>

    <!-- ADMIN -->
    <div class="card erp-card">
        <div class="card-header">👤 Informações do Administrador</div>
        <div class="card-body">

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Título:</strong> <?= htmlspecialchars($config['titulo'] ?? '') ?></p>
                    <p><strong>Nome:</strong> <?= htmlspecialchars($config['nome_admin'] ?? '') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($config['email_admin'] ?? '') ?></p>
                </div>

                <div class="col-md-6">
                    <p><strong>Telefone:</strong> <?= htmlspecialchars($config['telefone_suporte'] ?? '') ?></p>
                    <p><strong>Endereço:</strong> <?= htmlspecialchars($config['endereco'] ?? '') ?></p>
                    <p><strong>Horário:</strong> <?= htmlspecialchars($config['horario_atendimento'] ?? '') ?></p>
                </div>
            </div>

            <hr>

            <p>
                <strong>Website:</strong>
                <a href="<?= htmlspecialchars($config['website'] ?? '#') ?>" target="_blank">
                    <?= htmlspecialchars($config['website'] ?? 'N/D') ?>
                </a>
            </p>

        </div>
    </div>

    <!-- TÉCNICO -->
    <div class="card erp-card">
        <div class="card-header">🛠️ Configurações Técnicas</div>
        <div class="card-body row">

            <div class="col-md-3">
                <strong>SSL</strong><br>
                <?= !empty($config['ssl_ativado']) ? 'Ativo' : 'Inativo' ?>
            </div>

            <div class="col-md-3">
                <strong>Conexões</strong><br>
                <?= $config['limite_conexoes'] ?? 100 ?>
            </div>

            <div class="col-md-3">
                <strong>Expiração</strong><br>
                <?= $config['tempo_expiracao'] ?? 30 ?> min
            </div>

            <div class="col-md-3">
                <strong>Modo</strong><br>
                <?= $config['modo_exibicao'] ?? 'Padrão' ?>
            </div>

        </div>
    </div>

    <!-- AÇÕES -->
    <div class="card erp-card">
        <div class="card-header">⚡ Ações do Sistema</div>

        <div class="card-body d-grid gap-2">

            <a href="backup.php" class="btn btn-outline-secondary btn-erp">
                💾 Backup do Sistema
            </a>

            <a href="restaurar_backup.php" class="btn btn-outline-warning btn-erp">
                ♻ Restaurar Backup
            </a>

            <a href="ver_logs.php" class="btn btn-outline-info btn-erp">
                📄 Ver Logs
            </a>

            <button class="btn btn-outline-primary btn-erp" data-bs-toggle="modal" data-bs-target="#modalConfig">
                ✏ Editar Configurações
            </button>

        </div>
    </div>

</div>

<!-- MODAL -->
<div class="modal fade" id="modalConfig" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Editar Configurações</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        Funcionalidade em desenvolvimento no ERP Mambo System Sales 95.
      </div>

    </div>
  </div>
</div>

<script src="../../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>