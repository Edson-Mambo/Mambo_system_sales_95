<?php

require_once __DIR__ . '/../config/database.php';

class SyncService
{
    private static function serverDB()
    {
        return Database::conectar(); // servidor
    }

    private static function localDB()
    {
        return new PDO("sqlite:" . __DIR__ . "/../localdb/mambo_local.db");
    }

    /* =========================
       PRODUTOS
    ========================= */
    public static function syncProdutos()
{
    $local = self::localDB();
    $server = self::serverDB();

    $produtos = $local->query("SELECT * FROM produtos WHERE sync = 0")
                      ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($produtos as $p) {

        $preco = $p['preco_venda']
            ?? $p['preco']
            ?? 0;

        $stmt = $server->prepare("
            INSERT OR REPLACE INTO produtos
            (id, nome, preco, stock, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $p['id'],
            $p['nome'],
            $preco,
            $p['stock'],
            $p['updated_at']
        ]);

        $local->prepare("UPDATE produtos SET sync = 1 WHERE id = ?")
              ->execute([$p['id']]);
    }

    return count($produtos);
}

    /* =========================
       CLIENTES
    ========================= */
    public static function syncClientes()
    {
        $local = self::localDB();
        $server = self::serverDB();

        $clientes = $local->query("SELECT * FROM clientes WHERE sync = 0")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($clientes as $c) {

            $stmt = $server->prepare("
                INSERT OR REPLACE INTO clientes
                (id, nome, telefone, email, morada)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $c['id'],
                $c['nome'],
                $c['telefone'],
                $c['email'],
                $c['morada']
            ]);

            $local->prepare("UPDATE clientes SET sync = 1 WHERE id = ?")
                  ->execute([$c['id']]);
        }

        return count($clientes);
    }

    /* =========================
       VENDAS
    ========================= */
    public static function syncVendas()
    {
        $local = self::localDB();
        $server = self::serverDB();

        $vendas = $local->query("SELECT * FROM vendas WHERE sync = 0")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vendas as $v) {

            $stmt = $server->prepare("
                INSERT INTO vendas
                (id, cliente_id, total, metodo_pagamento, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $v['id'],
                $v['cliente_id'],
                $v['total'],
                $v['metodo_pagamento'],
                $v['created_at']
            ]);

            $local->prepare("UPDATE vendas SET sync = 1 WHERE id = ?")
                  ->execute([$v['id']]);
        }

        return count($vendas);
    }

    /* =========================
       CAIXA
    ========================= */
    public static function syncCaixa()
    {
        $local = self::localDB();
        $server = self::serverDB();

        $caixa = $local->query("SELECT * FROM abertura_caixa WHERE sync = 0")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($caixa as $c) {

            $stmt = $server->prepare("
                INSERT INTO abertura_caixa
                (id, usuario_id, status, aberto_em, fechado_em)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $c['id'],
                $c['usuario_id'],
                $c['status'],
                $c['aberto_em'],
                $c['fechado_em']
            ]);

            $local->prepare("UPDATE abertura_caixa SET sync = 1 WHERE id = ?")
                  ->execute([$c['id']]);
        }

        return count($caixa);
    }
}