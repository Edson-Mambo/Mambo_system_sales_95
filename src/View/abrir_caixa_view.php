<?php
session_start();

/* =========================
   VALIDA LOGIN
========================= */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Mambo_system_sales_95/public/login.php");
    exit;
}

/* =========================
   GARANTE QUE É CAIXA
========================= */
if (($_SESSION['nivel_acesso'] ?? '') !== 'caixa') {
    die("⛔ Apenas operadores de caixa podem abrir caixa.");
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Abrir Caixa</title>

    <link href="../../bootstrap/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-header bg-success text-white text-center">
                    <h4>🟢 Abrir Caixa</h4>
                </div>

                <div class="card-body">

                    <!-- MOSTRA QUEM ESTÁ LOGADO -->
                    <div class="alert alert-info text-center">
                        Operador: <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
                    </div>

               <form action="../../public/abrir_caixa.php" method="POST">

                        <p>
                            Operador: <b><?= $_SESSION['usuario_nome'] ?></b>
                        </p>

                        <input type="number" name="valor_inicial" value="0" required>

                        <button type="submit">Abrir Caixa</button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>