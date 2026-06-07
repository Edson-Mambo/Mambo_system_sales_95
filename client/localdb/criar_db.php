<?php

$dbPath = __DIR__ . '/mambo_local.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* =========================
       PRODUTOS (CACHE OFFLINE)
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY,
            nome TEXT NOT NULL,
            codigo_barra TEXT UNIQUE,
            preco REAL DEFAULT 0,
            categoria_id INTEGER,
            estoque INTEGER DEFAULT 0,
            imagem TEXT,
            atualizado_em TEXT
        )
    ");

    /* =========================
       CLIENTES (OFFLINE CACHE)
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clientes (
            id INTEGER PRIMARY KEY,
            nome TEXT,
            contacto TEXT,
            nif TEXT,
            atualizado_em TEXT
        )
    ");

    /* =========================
       USUÁRIOS OFFLINE
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            username TEXT UNIQUE,
            password TEXT,
            nivel TEXT DEFAULT 'caixa',
            criado_em TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    /* =========================
       VENDAS OFFLINE (SYNC MELHORADO)
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            cliente_id INTEGER,
            total REAL DEFAULT 0,
            metodo_pagamento TEXT DEFAULT 'dinheiro',

            -- estado do negócio (não pagamento)
            status TEXT DEFAULT 'pendente',

            -- sincronização offline → server (melhorado)
            sync_status TEXT DEFAULT 'pending',

            -- controlo extra de sync (NOVO MAS COMPATÍVEL)
            sync_tentativas INTEGER DEFAULT 0,
            sync_erro TEXT NULL,

            data TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    /* =========================
       ITENS DA VENDA
    ========================= */
    $pdo->exec("
       CREATE TABLE IF NOT EXISTS venda_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venda_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade REAL DEFAULT 1,
            preco REAL DEFAULT 0,
            subtotal REAL DEFAULT 0
        )
    ");

    /* =========================
       CARRINHO TEMPORÁRIO
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS carrinho (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER,
            quantidade INTEGER,
            criado_em TEXT
        )
    ");

    /* =========================
       FILA DE SINCRONIZAÇÃO (MELHORADA)
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,

            tipo TEXT,

            payload TEXT,

            status TEXT DEFAULT 'pending',

            -- controlo de retries
            tentativas INTEGER DEFAULT 0,
            max_tentativas INTEGER DEFAULT 5,

            erro TEXT NULL,

            created_at TEXT,
            updated_at TEXT
        )
    ");

    /* =========================
       LOGS
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT,
            mensagem TEXT,
            created_at TEXT
        )
    ");

    echo "SQLite POS estruturado com sync robusto.";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}