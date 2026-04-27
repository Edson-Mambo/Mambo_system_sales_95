<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();
$erro = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {

        session_regenerate_id(true);

        $_SESSION['usuario_id']   = (int)$user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['nivel_acesso'] = $user['nivel'];

        unset($_SESSION['caixa_id'], $_SESSION['abertura_id']);

        switch ($user['nivel']) {

            case 'admin':
                header("Location: index_admin.php");
                exit;

            case 'gerente':
                header("Location: index_gerente.php");
                exit;

            case 'supervisor':
                header("Location: index_supervisor.php");
                exit;

            case 'caixa':

                $usuario_id = $user['id'];

                // verifica se já existe caixa aberto
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM abertura_caixa
                    WHERE usuario_id = ?
                    AND status = 'aberto'
                    LIMIT 1
                ");

                $stmt->execute([$usuario_id]);
                $abertura = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$abertura) {

                    $stmt = $pdo->prepare("
                        INSERT INTO abertura_caixa
                        (usuario_id, valor_inicial, status)
                        VALUES (?, 0, 'aberto')
                    ");

                    $stmt->execute([$usuario_id]);
                    $abertura_id = $pdo->lastInsertId();

                } else {
                    $abertura_id = $abertura['id'];
                }

                $_SESSION['abertura_id'] = $abertura_id;

                header("Location: /Mambo_system_sales_95/public/venda.php");
                exit;

            default:
                header("Location: login.php");
                exit;
        }

    } else {
        $erro = "Credenciais inválidas";
    }
}

/* ================= ADMIN LOGIN ================= */
if (isset($_POST['auth_admin'])) {

    header('Content-Type: application/json');

    $stmt = $pdo->prepare("
        SELECT * FROM usuarios 
        WHERE email = ? AND nivel IN ('admin','gerente')
    ");
    $stmt->execute([$_POST['admin_email'] ?? '']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($_POST['admin_pass'], $admin['senha'])) {

        $_SESSION['reset_auth']  = true;
        $_SESSION['reset_admin'] = $admin['id'];

        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false]);
    }
    exit;
}

/* ================= LIST USERS ================= */
if (isset($_POST['list_users'])) {

    header('Content-Type: application/json');

    if (!($_SESSION['reset_auth'] ?? false)) {
        echo json_encode(["ok" => false]);
        exit;
    }

    $stmt = $pdo->query("SELECT id, nome, email, nivel FROM usuarios");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ================= RESET PASSWORD ================= */
if (isset($_POST['reset_password'])) {

    header('Content-Type: application/json');

    if (!($_SESSION['reset_auth'] ?? false)) {
        echo json_encode(["ok" => false]);
        exit;
    }

    $id   = $_POST['user_id'] ?? 0;
    $mode = $_POST['mode'] ?? 'auto';
    $nova = $_POST['nova_senha'] ?? '';

    if ($mode === "manual") {

        if (strlen($nova) < 4) {
            echo json_encode(["ok" => false, "msg" => "Senha muito curta"]);
            exit;
        }

        $senhaFinal = $nova;

    } else {
        $senhaFinal = bin2hex(random_bytes(4));
    }

    $hash = password_hash($senhaFinal, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);

    echo json_encode([
        "ok" => true,
        "senha" => $senhaFinal
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Mambo System 95</title>

<link href="../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
<script src="../bootstrap/bootstrap-5.3.3/js/jquery-3.7.1.min.js"></script>
<script src="../bootstrap/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-dark">

<div class="container mt-5">
<div class="col-md-4 mx-auto">

<div class="card shadow-lg">

<div class="card-header bg-primary text-white text-center">
<h4>🔐 Mambo System 95 - LOGIN</h4>
</div>

<div class="card-body">

<?php if($erro): ?>
<div class="alert alert-danger"><?= $erro ?></div>
<?php endif; ?>

<form method="POST">

<input name="usuario" class="form-control mb-2" placeholder="Email">
<input name="senha" type="password" class="form-control mb-2">

<button name="login" class="btn btn-primary w-100">
Entrar
</button>

</form>

<hr>

<a href="#" data-bs-toggle="modal" data-bs-target="#adminModal">
🔑 Reset Password (Admin)
</a>

</div>
</div>
</div>
</div>

<!-- ADMIN MODAL -->
<div class="modal fade" id="adminModal">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header bg-dark text-white">
<h5>Admin Login</h5>
</div>

<div class="modal-body">

<input id="admin_email" class="form-control mb-2" placeholder="Email">
<input id="admin_pass" type="password" class="form-control mb-2">

<button class="btn btn-dark w-100" onclick="loginAdmin()">
Entrar
</button>

</div>
</div>
</div>
</div>

<!-- USERS MODAL -->
<div class="modal fade" id="usersModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5>👥 Utilizadores</h5>
</div>

<div class="modal-body">

<input type="text" id="searchUser" class="form-control mb-3" placeholder="🔍 Pesquisar...">

<div style="max-height:400px; overflow-y:auto;">
<table class="table table-hover text-center">
<thead class="table-dark">
<tr>
<th>Nome</th>
<th>Email</th>
<th>Nível</th>
<th>Ação</th>
</tr>
</thead>
<tbody id="usersList"></tbody>
</table>
</div>

</div>
</div>
</div>
</div>

<!-- RESET MODAL -->
<div class="modal fade" id="resetModal">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header bg-danger text-white">
<h5>Reset Password</h5>
</div>

<div class="modal-body">

<input type="hidden" id="user_id">

<select id="modo" class="form-control mb-2">
<option value="auto">Automática</option>
<option value="manual">Manual</option>
</select>

<div id="manualBox" style="display:none;">
<input id="nova_senha" class="form-control" placeholder="Nova senha">
</div>

</div>

<div class="modal-footer">
<button class="btn btn-danger w-100" onclick="resetPassword()">
Confirmar
</button>
</div>

</div>
</div>
</div>

<script>

/* ADMIN LOGIN */
function loginAdmin(){

$.post("", {
auth_admin:true,
admin_email:$("#admin_email").val(),
admin_pass:$("#admin_pass").val()
}, function(res){

if(res.ok){
bootstrap.Modal.getInstance(document.getElementById('adminModal')).hide();

new bootstrap.Modal(document.getElementById('usersModal')).show();

loadUsers();
}else{
alert("Erro login admin");
}

},"json");
}

/* USERS */
function loadUsers(){

$.post("", {list_users:true}, function(users){

let html="";

users.forEach(u=>{

let badge="<span class='badge bg-secondary'>User</span>";

if(u.nivel=="admin") badge="<span class='badge bg-danger'>Admin</span>";
if(u.nivel=="gerente") badge="<span class='badge bg-warning'>Gerente</span>";

html+=`
<tr>
<td>${u.nome}</td>
<td>${u.email}</td>
<td>${badge}</td>
<td>
<button class="btn btn-danger btn-sm" onclick="openReset(${u.id})">
Reset
</button>
</td>
</tr>`;
});

$("#usersList").html(html);

},"json");
}

$("#searchUser").on("keyup", function(){
let v=$(this).val().toLowerCase();
$("#usersList tr").filter(function(){
$(this).toggle($(this).text().toLowerCase().indexOf(v)>-1);
});
});

function openReset(id){
$("#user_id").val(id);

bootstrap.Modal.getInstance(document.getElementById('usersModal')).hide();

new bootstrap.Modal(document.getElementById('resetModal')).show();
}

$("#modo").change(function(){
$("#manualBox").toggle($(this).val()=="manual");
});

function resetPassword(){

$.post("", {
reset_password:true,
user_id:$("#user_id").val(),
mode:$("#modo").val(),
nova_senha:$("#nova_senha").val()
}, function(res){

if(res.ok){
alert("Nova senha: "+res.senha);
bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
}else{
alert(res.msg);
}

},"json");
}

</script>

</body>
</html>