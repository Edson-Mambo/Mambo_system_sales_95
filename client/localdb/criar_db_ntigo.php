<?php

/**
 * MAMBO POS — Local SQLite DB
 * Versão 2.0 — Schema actualizado
 *
 * Melhorias:
 *  - Tabela `categorias` adicionada (com sync)
 *  - Coluna `categoria_id` em `produtos` agora tem FK explícita
 *  - Tabela `caixa_movimentos` para abertura/fecho de caixa
 *  - Tabela `devolucoes` + `devolucao_itens` para reembolsos futuros
 *  - sync_queue melhorada: prioridade, payload_hash (evita duplicados)
 *  - Índices adicionados em todas as tabelas críticas
 *  - `sync_status` padronizado: 0=pendente, 1=sincronizado, 2=erro
 *  - `deleted_at` mantido em todas as tabelas (soft delete)
 */

$dbPath = __DIR__ . '/mambo_local.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Activar suporte a foreign keys no SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("PRAGMA journal_mode = WAL;"); // melhor performance offline


    /* =====================================================
       CATEGORIAS (NOVO)
       Geridas pelo servidor, replicadas localmente.
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id   INTEGER PRIMARY KEY,
            uuid TEXT UNIQUE NOT NULL,

            nome        TEXT NOT NULL,
            descricao   TEXT,
            cor         TEXT,   -- ex: #FF5733 para UI
            icone       TEXT,   -- nome do ícone ou path

            ativa       INTEGER DEFAULT 1, -- 0=inactiva

            created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at  TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at  TEXT NULL,

            sync_status INTEGER DEFAULT 0
            -- 0=pendente envio | 1=sincronizado | 2=erro
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_categorias_uuid ON categorias(uuid);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_categorias_sync ON categorias(sync_status);");


    /* =====================================================
       PRODUTOS (ACTUALIZADO)
       - categoria_id agora referencia tabela categorias
       - custo adicionado (para margem de lucro)
       - unidade adicionada (ex: un, kg, lt)
       - estoque_minimo para alertas
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id   INTEGER PRIMARY KEY,
            uuid TEXT UNIQUE NOT NULL,

            nome         TEXT NOT NULL,
            codigo_barra TEXT UNIQUE,
            descricao    TEXT,

            preco        REAL DEFAULT 0,
            custo        REAL DEFAULT 0,   -- preço de custo (novo)
            unidade      TEXT DEFAULT 'un', -- un | kg | lt | cx

            categoria_id    INTEGER,
            estoque         REAL DEFAULT 0,  -- REAL para fraccionários (kg)
            estoque_minimo  REAL DEFAULT 0,  -- alerta de stock baixo (novo)

            imagem       TEXT,
            ativo        INTEGER DEFAULT 1,  -- 0=inactivo (novo)

            created_at   TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at   TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at   TEXT NULL,

            sync_status  INTEGER DEFAULT 0,

            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_produtos_uuid       ON produtos(uuid);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_produtos_categoria  ON produtos(categoria_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_produtos_barcode    ON produtos(codigo_barra);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_produtos_sync       ON produtos(sync_status);");


    /* =====================================================
       CLIENTES (ACTUALIZADO)
       - endereco e email adicionados
       - limite_credito para vendas a crédito
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clientes (
            id   INTEGER PRIMARY KEY,
            uuid TEXT UNIQUE NOT NULL,

            nome     TEXT NOT NULL,
            contacto TEXT,
            email    TEXT,
            nif      TEXT,
            endereco TEXT,

            limite_credito REAL DEFAULT 0, -- vendas a crédito

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status INTEGER DEFAULT 0
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clientes_uuid ON clientes(uuid);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clientes_nif  ON clientes(nif);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clientes_sync ON clientes(sync_status);");


    /* =====================================================
       USUÁRIOS OFFLINE (ACTUALIZADO)
       - pin adicionado para login rápido no POS
       - ultimo_acesso para auditoria
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            nome     TEXT NOT NULL,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            pin      TEXT,    -- PIN de 4 dígitos para acesso rápido (novo)

            nivel    TEXT DEFAULT 'caixa',
            -- caixa | supervisor | admin

            ativo         INTEGER DEFAULT 1,
            ultimo_acesso TEXT NULL,   -- auditoria (novo)

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status INTEGER DEFAULT 0
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_usuarios_uuid     ON usuarios(uuid);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_usuarios_username ON usuarios(username);");


    /* =====================================================
       CAIXA / SESSÕES (NOVO)
       Controlo de abertura e fecho de caixa por turno.
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS caixa_sessoes (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            usuario_id   INTEGER NOT NULL,
            saldo_inicial REAL DEFAULT 0,
            saldo_final   REAL NULL,   -- preenchido no fecho

            aberto_em    TEXT DEFAULT CURRENT_TIMESTAMP,
            fechado_em   TEXT NULL,

            observacao   TEXT,
            status       TEXT DEFAULT 'aberta', -- aberta | fechada

            created_at   TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at   TEXT DEFAULT CURRENT_TIMESTAMP,

            sync_status  INTEGER DEFAULT 0,

            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_caixa_sessoes_status ON caixa_sessoes(status);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_caixa_sessoes_sync   ON caixa_sessoes(sync_status);");


    /* =====================================================
       MOVIMENTOS DE CAIXA (NOVO)
       Entradas e saídas manuais de dinheiro na caixa.
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS caixa_movimentos (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            sessao_id  INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,

            tipo       TEXT NOT NULL,   -- entrada | saida
            valor      REAL NOT NULL,
            motivo     TEXT,

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status INTEGER DEFAULT 0,

            FOREIGN KEY (sessao_id)  REFERENCES caixa_sessoes(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_caixa_mov_sessao ON caixa_movimentos(sessao_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_caixa_mov_sync   ON caixa_movimentos(sync_status);");


    /* =====================================================
       VENDAS (ACTUALIZADO)
       - sessao_id ligado à caixa aberta
       - desconto e imposto adicionados
       - troco calculado e guardado
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vendas (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            usuario_id INTEGER,
            cliente_id INTEGER,
            sessao_id  INTEGER,  -- sessão de caixa (novo)

            subtotal          REAL DEFAULT 0,   -- antes de desconto/imposto (novo)
            desconto          REAL DEFAULT 0,   -- valor do desconto (novo)
            imposto           REAL DEFAULT 0,   -- IVA ou outro (novo)
            total             REAL DEFAULT 0,

            metodo_pagamento  TEXT DEFAULT 'dinheiro',
            -- dinheiro | mpesa | emola | cartao | credito

            valor_recebido    REAL DEFAULT 0,   -- novo
            troco             REAL DEFAULT 0,   -- novo

            numero_referencia TEXT NULL,        -- referência Mpesa/eMola (novo)

            status     TEXT DEFAULT 'concluida',
            -- concluida | anulada | pendente

            observacao TEXT,

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status    INTEGER DEFAULT 0,
            sync_tentativas INTEGER DEFAULT 0,
            sync_erro      TEXT NULL,

            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (cliente_id) REFERENCES clientes(id),
            FOREIGN KEY (sessao_id)  REFERENCES caixa_sessoes(id)
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_uuid    ON vendas(uuid);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_cliente ON vendas(cliente_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_sessao  ON vendas(sessao_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_status  ON vendas(status);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_sync    ON vendas(sync_status);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vendas_data    ON vendas(created_at);");


    /* =====================================================
       ITENS DE VENDA (ACTUALIZADO)
       - desconto_item para promoções por linha
       - nome_produto snapshot para histórico
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS venda_itens (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            venda_id   INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,

            -- snapshot no momento da venda (importante!)
            nome_produto TEXT,  -- nome guardado mesmo se produto mudar (novo)
            preco_custo  REAL DEFAULT 0, -- para calcular margem (novo)

            quantidade   REAL DEFAULT 1,
            preco        REAL DEFAULT 0,
            desconto     REAL DEFAULT 0, -- desconto por linha (novo)
            subtotal     REAL DEFAULT 0,

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status INTEGER DEFAULT 0,

            FOREIGN KEY (venda_id)   REFERENCES vendas(id) ON DELETE CASCADE,
            FOREIGN KEY (produto_id) REFERENCES produtos(id)
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vitens_venda   ON venda_itens(venda_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vitens_produto ON venda_itens(produto_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_vitens_sync    ON venda_itens(sync_status);");


    /* =====================================================
       DEVOLUÇÕES (NOVO)
       Reembolsos e trocas de mercadoria.
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devolucoes (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            venda_id   INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,

            motivo     TEXT,
            total      REAL DEFAULT 0,
            status     TEXT DEFAULT 'pendente',
            -- pendente | aprovada | rejeitada

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL,

            sync_status INTEGER DEFAULT 0,

            FOREIGN KEY (venda_id)   REFERENCES vendas(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devolucao_itens (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,

            devolucao_id  INTEGER NOT NULL,
            venda_item_id INTEGER NOT NULL,

            quantidade  REAL DEFAULT 1,
            preco       REAL DEFAULT 0,
            subtotal    REAL DEFAULT 0,

            created_at  TEXT DEFAULT CURRENT_TIMESTAMP,

            sync_status INTEGER DEFAULT 0,

            FOREIGN KEY (devolucao_id)  REFERENCES devolucoes(id) ON DELETE CASCADE,
            FOREIGN KEY (venda_item_id) REFERENCES venda_itens(id)
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_dev_venda ON devolucoes(venda_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_dev_sync  ON devolucoes(sync_status);");


    /* =====================================================
       CARRINHO TEMPORÁRIO (MANTIDO — sem sync)
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS carrinho (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER NOT NULL,
            quantidade REAL DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");


    /* =====================================================
       SYNC QUEUE (MELHORADA)
       - prioridade: vendas sincronizam antes de produtos
       - payload_hash: evita entradas duplicadas na fila
       - sincronizado_em: auditoria de quando foi enviado
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sync_queue (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,

            table_name  TEXT NOT NULL,
            record_id   INTEGER,
            uuid        TEXT,

            operation   TEXT NOT NULL,
            -- INSERT | UPDATE | DELETE

            payload     TEXT NOT NULL,  -- JSON do registo
            payload_hash TEXT,          -- MD5 do payload (evita duplicados) (novo)

            prioridade  INTEGER DEFAULT 5,
            -- 1=crítico (vendas) | 5=normal | 10=baixo (novo)

            status      TEXT DEFAULT 'pending',
            -- pending | processing | done | failed

            tentativas      INTEGER DEFAULT 0,
            max_tentativas  INTEGER DEFAULT 5,

            erro            TEXT NULL,
            sincronizado_em TEXT NULL,  -- timestamp de sucesso (novo)

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sync_status     ON sync_queue(status);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sync_prioridade ON sync_queue(prioridade, created_at);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sync_uuid       ON sync_queue(uuid);");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_sync_hash ON sync_queue(payload_hash) WHERE payload_hash IS NOT NULL;");


    /* =====================================================
       CONFIGURAÇÕES LOCAIS (NOVO)
       Chave-valor para guardar preferências locais.
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracoes (
            chave TEXT PRIMARY KEY,
            valor TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Valores padrão
    $pdo->exec("
        INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES
            ('moeda',           'MZN'),
            ('nome_empresa',    'Mambo POS'),
            ('nif_empresa',     ''),
            ('endereco',        ''),
            ('iva_percentagem', '16'),
            ('iva_activo',      '0'),
            ('sync_intervalo',  '30'),
            ('versao_schema',   '2')
    ");


    /* =====================================================
       LOGS (ACTUALIZADO)
       - usuario_id para rastrear quem gerou o log
       - nivel adicionado (info | aviso | erro)
    ===================================================== */
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,

            tipo       TEXT,   -- sync | venda | auth | sistema
            nivel      TEXT DEFAULT 'info', -- info | aviso | erro (novo)
            mensagem   TEXT,
            detalhe    TEXT,   -- JSON extra (novo)
            usuario_id INTEGER NULL,

            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_logs_tipo  ON logs(tipo);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_logs_nivel ON logs(nivel);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_logs_data  ON logs(created_at);");


    echo "✅ Mambo POS — SQLite DB v2 instalado com sucesso.\n";
    echo "   Tabelas criadas: categorias, produtos, clientes, usuarios,\n";
    echo "                    caixa_sessoes, caixa_movimentos, vendas,\n";
    echo "                    venda_itens, devolucoes, devolucao_itens,\n";
    echo "                    carrinho, sync_queue, configuracoes, logs\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}