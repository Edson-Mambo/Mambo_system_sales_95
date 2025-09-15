<?php
session_start();

if (isset($_SESSION['recibo_path'])) {
    $file = __DIR__ . '/../' . $_SESSION['recibo_path'];
    unset($_SESSION['recibo_path']);
    if (file_exists($file)) {
        ?>
        <iframe src="../<?= basename($file) ?>" style="display:none;" id="reciboFrame"></iframe>
        <script>
            window.onload = function () {
                let frame = document.getElementById("reciboFrame");
                frame.contentWindow.print();
            }
        </script>
        <?php
    } else {
        echo "Arquivo não encontrado.";
    }
} else {
    echo "Recibo não disponível.";
}
