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
       VENDAS OFFLINE
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            total REAL DEFAULT 0,
            metodo_pagamento TEXT,
            cliente_id INTEGER,
            usuario_id INTEGER,
            data TEXT,
            status TEXT DEFAULT 'pendente',
            sync_status TEXT DEFAULT 'pendente'
        )
    ");

    /* =========================
       ITENS DA VENDA
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS itens_venda (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venda_id INTEGER,
            produto_id INTEGER,
            nome_produto TEXT,
            quantidade INTEGER,
            preco REAL,
            subtotal REAL
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
       FILA DE SINCRONIZAÇÃO
    ========================= */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT,
            payload TEXT,
            status TEXT DEFAULT 'pending',
            tentativas INTEGER DEFAULT 0,
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

    echo "SQLite POS estruturado com sucesso.";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}