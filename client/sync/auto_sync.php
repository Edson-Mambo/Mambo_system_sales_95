<?php

require_once __DIR__ . '/../services/SyncService.php';

class AutoSync
{
    public static function run()
    {
        SyncService::syncVendas();
        SyncService::syncProdutos();
        SyncService::syncClientes();
        SyncService::syncCaixa();
    }
}

// Execução direta (cron job ou browser)
AutoSync::run();