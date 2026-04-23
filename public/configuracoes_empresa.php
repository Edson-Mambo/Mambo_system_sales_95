


<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

requireRole(['admin']);

$pdo = Database::conectar();

// Buscar configuração atual
$stmt = $pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome_empresa    = trim($_POST['nome_empresa'] ?? '');
    $telefone        = trim($_POST['telefone'] ?? '');
    $email_empresa   = trim($_POST['email_empresa'] ?? '');
    $provincia       = trim($_POST['provincia'] ?? '');
    $cidade          = trim($_POST['cidade'] ?? '');
    $bairro          = trim($_POST['bairro'] ?? '');
    $rua_avenida     = trim($_POST['rua_avenida'] ?? '');
    $nuit_empresa    = trim($_POST['nuit_empresa'] ?? ''); // ✅ NOVO
    $nome_impressora = trim($_POST['nome_impressora'] ?? '');
    $mensagem_rodape = trim($_POST['mensagem_rodape'] ?? '');
    $mensagem_email  = trim($_POST['mensagem_email'] ?? '');

    $smtp_host  = trim($_POST['smtp_host'] ?? '');
    $smtp_port  = trim($_POST['smtp_port'] ?? '');
    $smtp_email = trim($_POST['smtp_email'] ?? '');
    $smtp_senha = trim($_POST['smtp_senha'] ?? '');

    // Logo upload
    $logo = $config['logo'] ?? null;

    if (!empty($_FILES['logo']['name'])) {
        $pasta = __DIR__ . '/../public/uploads/';

        if (!is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $nomeArquivo = time() . '_' . basename($_FILES['logo']['name']);
        $destino = $pasta . $nomeArquivo;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $logo = $nomeArquivo;
        }
    }

    if ($config) {

        $sql = "UPDATE configuracoes_empresa SET
            nome_empresa=?, telefone=?, email_empresa=?,
            provincia=?, cidade=?, bairro=?, rua_avenida=?,
            nuit_empresa=?,
            nome_impressora=?, mensagem_rodape=?, mensagem_email=?,
            smtp_host=?, smtp_port=?, smtp_email=?, smtp_senha=?, logo=?
            WHERE id=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_empresa,
            $telefone,
            $email_empresa,
            $provincia,
            $cidade,
            $bairro,
            $rua_avenida,
            $nuit_empresa,
            $nome_impressora,
            $mensagem_rodape,
            $mensagem_email,
            $smtp_host,
            $smtp_port,
            $smtp_email,
            $smtp_senha,
            $logo,
            $config['id']
        ]);

    } else {

        $sql = "INSERT INTO configuracoes_empresa (
            nome_empresa, telefone, email_empresa,
            provincia, cidade, bairro, rua_avenida,
            nuit_empresa,
            nome_impressora, mensagem_rodape, mensagem_email,
            smtp_host, smtp_port, smtp_email, smtp_senha, logo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_empresa,
            $telefone,
            $email_empresa,
            $provincia,
            $cidade,
            $bairro,
            $rua_avenida,
            $nuit_empresa,
            $nome_impressora,
            $mensagem_rodape,
            $mensagem_email,
            $smtp_host,
            $smtp_port,
            $smtp_email,
            $smtp_senha,
            $logo
        ]);
    }

    $_SESSION['sucesso'] = "Configurações salvas com sucesso!";
    header("Location: configuracoes_empresa.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Configurações da Empresa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">


<style>
body { background:#f4f6f9; }

.card {
    border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,0.08);
}

.logo-preview {
    max-height:120px;
    margin-top:10px;
    border-radius:8px;
    border:1px solid #ddd;
    padding:5px;
}

.tab-content {
    background:#fff;
    padding:20px;
    border:1px solid #dee2e6;
    border-top:0;
    border-radius:0 0 12px 12px;
}
</style>
</head>

<body>

<div class="container mt-4">
<div class="card p-4">

<h2>⚙️ Configurações da Empresa</h2>

<form method="POST" enctype="multipart/form-data">

<ul class="nav nav-tabs">

<li class="nav-item">
<button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#empresa">Empresa</button>
</li>

<li class="nav-item">
<button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#endereco">Endereço</button>
</li>

<li class="nav-item">
<button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#impressao">Impressão</button>
</li>

<li class="nav-item">
<button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#email">Email</button>
</li>

<li class="nav-item">
<button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#smtp">SMTP</button>
</li>

<li class="nav-item">
<button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#logo">Logo</button>
</li>

</ul>

<div class="tab-content">

<!-- EMPRESA -->
<div class="tab-pane fade show active" id="empresa">

<input class="form-control mb-2" name="nome_empresa" placeholder="Nome da empresa" value="<?= $config['nome_empresa'] ?? '' ?>">

<input class="form-control mb-2" name="telefone" placeholder="Telefone" value="<?= $config['telefone'] ?? '' ?>">

<input class="form-control mb-2" name="email_empresa" placeholder="Email" value="<?= $config['email_empresa'] ?? '' ?>">

<input class="form-control mb-2" name="nuit_empresa" placeholder="NUIT da empresa" value="<?= $config['nuit_empresa'] ?? '' ?>">

</div>

<!-- ENDEREÇO -->
<div class="tab-pane fade" id="endereco">
<input class="form-control mb-2" name="provincia" placeholder="Província" value="<?= $config['provincia'] ?? '' ?>">
<input class="form-control mb-2" name="cidade" placeholder="Cidade" value="<?= $config['cidade'] ?? '' ?>">
<input class="form-control mb-2" name="bairro" placeholder="Bairro" value="<?= $config['bairro'] ?? '' ?>">
<input class="form-control mb-2" name="rua_avenida" placeholder="Rua / Avenida" value="<?= $config['rua_avenida'] ?? '' ?>">
</div>

<!-- IMPRESSÃO -->
<div class="tab-pane fade" id="impressao">
<input class="form-control mb-2" name="nome_impressora" placeholder="Impressora" value="<?= $config['nome_impressora'] ?? '' ?>">
<textarea class="form-control mb-2" name="mensagem_rodape"><?= $config['mensagem_rodape'] ?? '' ?></textarea>
</div>

<!-- EMAIL -->
<div class="tab-pane fade" id="email">
<textarea class="form-control mb-2" name="mensagem_email"><?= $config['mensagem_email'] ?? '' ?></textarea>
</div>

<!-- SMTP -->
<div class="tab-pane fade" id="smtp">
<input class="form-control mb-2" name="smtp_host" placeholder="Host" value="<?= $config['smtp_host'] ?? '' ?>">
<input class="form-control mb-2" name="smtp_port" placeholder="Port" value="<?= $config['smtp_port'] ?? '' ?>">
<input class="form-control mb-2" name="smtp_email" placeholder="Email" value="<?= $config['smtp_email'] ?? '' ?>">
<input class="form-control mb-2" name="smtp_senha" placeholder="Senha" value="<?= $config['smtp_senha'] ?? '' ?>">
</div>

<!-- LOGO -->
<div class="tab-pane fade" id="logo">

<input type="file" class="form-control mb-2" name="logo">

<?php if (!empty($config['logo'])): ?>
<img class="logo-preview" src="../public/uploads/<?= $config['logo'] ?>">
<?php endif; ?>

</div>

</div>

<br>

<button class="btn btn-primary w-100">💾 Salvar</button>

</form>

</div>
</div>
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

            <a href="<?= $voltar ?>" class="btn btn-outline-secondary me-2">
                ← Voltar
            </a>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"rel="stylesheet">

</body>
</html>