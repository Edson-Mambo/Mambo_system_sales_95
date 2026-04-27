<?php
session_start();

require_once '../../config/database.php';
$pdo = Database::conectar();

/* =========================
   SEGURANÇA ERP
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
   CONFIG
========================= */
$stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

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
<title>Configurações ERP</title>

<link rel="stylesheet" href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css">

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

/* CARD */
.erp-card{
    border:0;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
}

.section-title{
    font-size:14px;
    font-weight:600;
    color:#111827;
    margin-bottom:15px;
}
</style>

</head>

<body>

<div class="container py-4">

    <!-- HEADER -->
    <div class="erp-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">⚙️ Configurações do Sistema</h5>
            <small>Mambo System Sales 95</small>
        </div>

        <a href="<?= $voltar ?>" class="btn btn-light btn-sm">
            ← Voltar
        </a>
    </div>

    <!-- FORM -->
    <div class="card erp-card">
        <div class="card-body p-4">

            <form action="config_controller.php" method="POST" class="row g-3">

                <input type="hidden" name="id" value="<?= $config['id'] ?>">

                <!-- ADMIN -->
                <div class="col-12 section-title">👤 Administrador</div>

                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome_admin" class="form-control"
                           value="<?= htmlspecialchars($config['nome_admin'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email_admin" class="form-control"
                           value="<?= htmlspecialchars($config['email_admin'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone_suporte" class="form-control"
                           value="<?= htmlspecialchars($config['telefone_suporte'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control"
                           value="<?= htmlspecialchars($config['website'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="endereco" class="form-control"
                           value="<?= htmlspecialchars($config['endereco'] ?? '') ?>">
                </div>

                <div class="col-12 section-title mt-4">🛠️ Sistema</div>

                <div class="col-md-4">
                    <label class="form-label">Horário Atendimento</label>
                    <input type="text" name="horario_atendimento" class="form-control"
                           value="<?= htmlspecialchars($config['horario_atendimento'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">SSL</label>
                    <select name="ssl_ativado" class="form-select">
                        <option value="1" <?= !empty($config['ssl_ativado']) ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= empty($config['ssl_ativado']) ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Modo</label>
                    <input type="text" name="modo_exibicao" class="form-control"
                           value="<?= htmlspecialchars($config['modo_exibicao'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Limite Conexões</label>
                    <input type="number" name="limite_conexoes" class="form-control"
                           value="<?= htmlspecialchars($config['limite_conexoes'] ?? 100) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Expiração Sessão (min)</label>
                    <input type="number" name="tempo_expiracao" class="form-control"
                           value="<?= htmlspecialchars($config['tempo_expiracao'] ?? 30) ?>">
                </div>

                <!-- BOTÕES -->
                <div class="col-12 mt-4 d-flex gap-2">

                    <button type="submit" class="btn btn-success">
                        💾 Salvar Alterações
                    </button>

                    <a href="configuracoes.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>