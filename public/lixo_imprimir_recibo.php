
<?php
session_start();

if (!isset($_SESSION['recibo_path'])) {
    exit("Recibo não disponível.");
}

$file = $_SESSION['recibo_path'];
unset($_SESSION['recibo_path']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Recibo</title>
</head>
<body>

<iframe id="pdfFrame"
        src="../<?= htmlspecialchars($file) ?>"
        style="width:0;height:0;border:0;"></iframe>

<script>
window.onload = function () {
    const frame = document.getElementById("pdfFrame");

    frame.onload = function () {
        setTimeout(() => {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }, 500);
    };
};
</script>

</body>
</html>