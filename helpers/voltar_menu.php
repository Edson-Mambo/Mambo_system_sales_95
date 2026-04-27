<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nivel = $_SESSION['nivel_acesso'] ?? null;

/* =========================
   ROTAS CENTRALIZADAS
========================= */
$rotas = [
    'admin' => '/Mambo_system_sales_95/public/index_admin.php',
    'gerente' => '/Mambo_system_sales_95/public/index_gerente.php',
    'supervisor' => '/Mambo_system_sales_95/public/index_supervisor.php',
];

/* =========================
   FALLBACK SEGURO
========================= */
$pagina_destino = $rotas[$nivel] ?? '/Mambo_system_sales_95/public/index.php';

/* =========================
   FUNÇÃO GLOBAL
========================= */
function voltarMenu() {
    global $pagina_destino;
    return $pagina_destino;
}