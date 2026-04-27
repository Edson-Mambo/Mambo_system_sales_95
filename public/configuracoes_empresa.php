<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$pdo = Database::conectar();

requireRole(['admin']);

/* =========================
   CONFIG
========================= */
$stmt = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   VOLTAR ERP
========================= */
$nivel = $_SESSION['nivel_acesso'] ?? '';

$voltar = match($nivel) {
    'admin' => 'index_admin.php',
    'gerente' => 'index_gerente.php',
    'supervisor' => 'index_supervisor.php',
    default => 'index.php'
};
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Configurações Empresa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6f9;
}

/* HEADER ERP */
.erp-header{
    background:#111827;
    color:#fff;
    padding:18px;
    border-radius:12px;
    margin-bottom:20px;
}

/* CARD */
.erp-card{
    border:0;
    border-radius:12px;
    box-shadow:0 2px 12px rgba(0,0,0,0.08);
}

/* TABS LIMPO ERP */
.nav-tabs .nav-link{
    font-weight:500;
}

.logo-preview{
    max-height:120px;
    border:1px solid #ddd;
    padding:5px;
    border-radius:8px;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="container py-4">

    <!-- HEADER -->
    <div class="erp-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">🏢 Configurações da Empresa</h5>
            <small>Mambo System Sales 95</small>
        </div>

        <a href="<?= $voltar ?>" class="btn btn-light btn-sm">
            ← Voltar
        </a>
    </div>

    <!-- CARD -->
    <div class="card erp-card">
        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">

                <!-- TABS -->
                <ul class="nav nav-tabs mb-3">

                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#empresa">Empresa</button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#endereco">Endereço</button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#impressao">Impressão</button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email">Email</button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#smtp">SMTP</button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#logo">Logo</button>
                    </li>

                </ul>

                <!-- CONTENT -->
                <div class="tab-content">

                    <!-- EMPRESA -->
                    <div class="tab-pane fade show active" id="empresa">
                        <input class="form-control mb-2" name="nome_empresa" placeholder="Nome empresa" value="<?= $config['nome_empresa'] ?? '' ?>">
                        <input class="form-control mb-2" name="telefone" placeholder="Telefone" value="<?= $config['telefone'] ?? '' ?>">
                        <input class="form-control mb-2" name="email_empresa" placeholder="Email" value="<?= $config['email_empresa'] ?? '' ?>">
                        <input class="form-control mb-2" name="nuit_empresa" placeholder="NUIT" value="<?= $config['nuit_empresa'] ?? '' ?>">
                    </div>

                    <!-- ENDEREÇO -->
                    <div class="tab-pane fade" id="endereco">
                        <input class="form-control mb-2" name="provincia" placeholder="Província" value="<?= $config['provincia'] ?? '' ?>">
                        <input class="form-control mb-2" name="cidade" placeholder="Cidade" value="<?= $config['cidade'] ?? '' ?>">
                        <input class="form-control mb-2" name="bairro" placeholder="Bairro" value="<?= $config['bairro'] ?? '' ?>">
                        <input class="form-control mb-2" name="rua_avenida" placeholder="Rua/Avenida" value="<?= $config['rua_avenida'] ?? '' ?>">
                    </div>

                    <!-- IMPRESSÃO -->
                    <div class="tab-pane fade" id="impressao">
                        <input class="form-control mb-2" name="nome_impressora" placeholder="Impressora" value="<?= $config['nome_impressora'] ?? '' ?>">
                        <textarea class="form-control" name="mensagem_rodape" placeholder="Mensagem rodapé"><?= $config['mensagem_rodape'] ?? '' ?></textarea>
                    </div>

                    <!-- EMAIL -->
                    <div class="tab-pane fade" id="email">
                        <textarea class="form-control" name="mensagem_email" placeholder="Mensagem email"><?= $config['mensagem_email'] ?? '' ?></textarea>
                    </div>

                    <!-- SMTP -->
                    <div class="tab-pane fade" id="smtp">
                        <input class="form-control mb-2" name="smtp_host" placeholder="Host" value="<?= $config['smtp_host'] ?? '' ?>">
                        <input class="form-control mb-2" name="smtp_port" placeholder="Porta" value="<?= $config['smtp_port'] ?? '' ?>">
                        <input class="form-control mb-2" name="smtp_email" placeholder="Email" value="<?= $config['smtp_email'] ?? '' ?>">
                        <input class="form-control mb-2" name="smtp_senha" placeholder="Senha" value="<?= $config['smtp_senha'] ?? '' ?>">
                    </div>

                   <!-- LOGO -->
<div class="tab-pane fade" id="logo">

    <input type="file" class="form-control mb-2" name="logo">

    <?php if (!empty($config['logo'])): ?>
        <img class="logo-preview" src="../public/uploads/<?= htmlspecialchars($config['logo']) ?>">
    <?php endif; ?>

</div>

</div>

<br>

<button class="btn btn-primary w-100">💾 Salvar</button>

</form>

</div>
</div>

<!-- VOLTAR -->
<div class="text-center mt-4">
<?php
$nivel = $_SESSION['usuario_nivel'] ?? '';

$voltar = match($nivel) {
    'admin' => 'index_admin.php',
    'supervisor' => 'index_supervisor.php',
    'gerente' => 'index_gerente.php',
    default => 'index.php'
};
?>



<!-- =========================
     JS BOOTSTRAP (OBRIGATÓRIO)
========================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- (OPCIONAL MAS RECOMENDADO) ativa tabs automaticamente -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        });
    });
});
</script>

</body>
</html>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>