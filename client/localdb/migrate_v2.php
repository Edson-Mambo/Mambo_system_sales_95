<?php

/**
 * MAMBO POS — Migração v1 → v2
 *
 * Este script é seguro para correr múltiplas vezes (idempotente).
 * Verifica o que já existe antes de alterar qualquer coisa.
 *
 * Uso: php migrate_v2.php
 *   ou aceder via browser: /migrate_v2.php
 */

$dbPath = __DIR__ . '/mambo_local.db';

$log = [];
$erros = [];

function aplicar(PDO $pdo, string $sql, string $descricao, array &$log, array &$erros): void {
    try {
        $pdo->exec($sql);
        $log[] = "✅ $descricao";
    } catch (Throwable $e) {
        // ignora "duplicate column" — já existe
        if (stripos($e->getMessage(), 'duplicate column') !== false) {
            $log[] = "⏭️  $descricao (já existe)";
        } else {
            $erros[] = "❌ $descricao — " . $e->getMessage();
        }
    }
}

function tabelaExiste(PDO $pdo, string $tabela): bool {
    $r = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tabela'")->fetch();
    return (bool)$r;
}

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool {
    foreach ($pdo->query("PRAGMA table_info($tabela)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
        if ($col['name'] === $coluna) return true;
    }
    return false;
}

function indiceExiste(PDO $pdo, string $nome): bool {
    $r = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='$nome'")->fetch();
    return (bool)$r;
}

try {

    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode = WAL;");
    // NÃO activar foreign_keys durante migração para evitar bloqueios
    $pdo->exec("PRAGMA foreign_keys = OFF;");

    $versaoActual = 1;
    try {
        $v = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'versao_schema' LIMIT 1")->fetchColumn();
        if ($v) $versaoActual = (int)$v;
    } catch (Throwable $e) {
        // tabela configuracoes não existe ainda — é v1
    }

    $log[] = "📦 Versão actual do schema: v$versaoActual";

    if ($versaoActual >= 2) {
        $log[] = "✅ Schema já está na v2. Nenhuma migração necessária.";
        // ainda corre fixes de segurança em baixo
    }

    /* =====================================================
       1. PRAGMA
    ===================================================== */
    $log[] = "";
    $log[] = "── PRAGMA ──────────────────────────────────";

    $pdo->exec("PRAGMA journal_mode = WAL;");
    $log[] = "✅ journal_mode = WAL";


    /* =====================================================
       2. TABELA: categorias (nova)
    ===================================================== */
    $log[] = "";
    $log[] = "── CATEGORIAS ──────────────────────────────";

    if (!tabelaExiste($pdo, 'categorias')) {
        aplicar($pdo, "
            CREATE TABLE categorias (
                id          INTEGER PRIMARY KEY,
                uuid        TEXT UNIQUE NOT NULL,
                nome        TEXT NOT NULL,
                descricao   TEXT,
                cor         TEXT,
                icone       TEXT,
                ativa       INTEGER DEFAULT 1,
                created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at  TEXT DEFAULT CURRENT_TIMESTAMP,
                deleted_at  TEXT NULL,
                sync_status INTEGER DEFAULT 0
            )
        ", "Tabela categorias criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela categorias (já existe)";
    }

    if (!indiceExiste($pdo, 'idx_categorias_uuid'))
        aplicar($pdo, "CREATE INDEX idx_categorias_uuid ON categorias(uuid)", "Índice categorias.uuid", $log, $erros);
    if (!indiceExiste($pdo, 'idx_categorias_sync'))
        aplicar($pdo, "CREATE INDEX idx_categorias_sync ON categorias(sync_status)", "Índice categorias.sync", $log, $erros);


    /* =====================================================
       3. TABELA: produtos — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── PRODUTOS ────────────────────────────────";

    if (!colunaExiste($pdo, 'produtos', 'uuid'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN uuid TEXT", "produtos.uuid", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'descricao'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN descricao TEXT", "produtos.descricao", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'custo'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN custo REAL DEFAULT 0", "produtos.custo", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'unidade'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN unidade TEXT DEFAULT 'un'", "produtos.unidade", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'estoque_minimo'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN estoque_minimo REAL DEFAULT 0", "produtos.estoque_minimo", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'ativo')) {
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN ativo INTEGER DEFAULT 1", "produtos.ativo", $log, $erros);
        // FIX: activar todos os produtos existentes
        aplicar($pdo, "UPDATE produtos SET ativo = 1 WHERE ativo IS NULL", "Fix: ativo = 1 em produtos existentes", $log, $erros);
    } else {
        // coluna já existe mas pode ter NULLs da migração anterior
        $nulls = (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo IS NULL OR ativo = 0")->fetchColumn();
        if ($nulls > 0) {
            aplicar($pdo, "UPDATE produtos SET ativo = 1 WHERE ativo IS NULL OR ativo = 0", "Fix: $nulls produto(s) com ativo NULL/0 corrigidos", $log, $erros);
        } else {
            $log[] = "⏭️  produtos.ativo (sem NULLs para corrigir)";
        }
    }

    if (!colunaExiste($pdo, 'produtos', 'deleted_at'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN deleted_at TEXT NULL", "produtos.deleted_at", $log, $erros);

    if (!colunaExiste($pdo, 'produtos', 'sync_status'))
        aplicar($pdo, "ALTER TABLE produtos ADD COLUMN sync_status INTEGER DEFAULT 0", "produtos.sync_status", $log, $erros);

    if (!indiceExiste($pdo, 'idx_produtos_uuid'))
        aplicar($pdo, "CREATE INDEX idx_produtos_uuid ON produtos(uuid)", "Índice produtos.uuid", $log, $erros);
    if (!indiceExiste($pdo, 'idx_produtos_categoria'))
        aplicar($pdo, "CREATE INDEX idx_produtos_categoria ON produtos(categoria_id)", "Índice produtos.categoria_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_produtos_sync'))
        aplicar($pdo, "CREATE INDEX idx_produtos_sync ON produtos(sync_status)", "Índice produtos.sync", $log, $erros);


    /* =====================================================
       4. TABELA: clientes — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── CLIENTES ────────────────────────────────";

    if (!colunaExiste($pdo, 'clientes', 'uuid'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN uuid TEXT", "clientes.uuid", $log, $erros);

    if (!colunaExiste($pdo, 'clientes', 'email'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN email TEXT", "clientes.email", $log, $erros);

    if (!colunaExiste($pdo, 'clientes', 'endereco'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN endereco TEXT", "clientes.endereco", $log, $erros);

    if (!colunaExiste($pdo, 'clientes', 'limite_credito'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN limite_credito REAL DEFAULT 0", "clientes.limite_credito", $log, $erros);

    if (!colunaExiste($pdo, 'clientes', 'deleted_at'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN deleted_at TEXT NULL", "clientes.deleted_at", $log, $erros);

    if (!colunaExiste($pdo, 'clientes', 'sync_status'))
        aplicar($pdo, "ALTER TABLE clientes ADD COLUMN sync_status INTEGER DEFAULT 0", "clientes.sync_status", $log, $erros);

    if (!indiceExiste($pdo, 'idx_clientes_uuid'))
        aplicar($pdo, "CREATE INDEX idx_clientes_uuid ON clientes(uuid)", "Índice clientes.uuid", $log, $erros);
    if (!indiceExiste($pdo, 'idx_clientes_nif'))
        aplicar($pdo, "CREATE INDEX idx_clientes_nif ON clientes(nif)", "Índice clientes.nif", $log, $erros);
    if (!indiceExiste($pdo, 'idx_clientes_sync'))
        aplicar($pdo, "CREATE INDEX idx_clientes_sync ON clientes(sync_status)", "Índice clientes.sync", $log, $erros);


    /* =====================================================
       5. TABELA: usuarios — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── USUÁRIOS ────────────────────────────────";

    if (!colunaExiste($pdo, 'usuarios', 'uuid'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN uuid TEXT", "usuarios.uuid", $log, $erros);

    if (!colunaExiste($pdo, 'usuarios', 'pin'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN pin TEXT", "usuarios.pin", $log, $erros);

    if (!colunaExiste($pdo, 'usuarios', 'ativo'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN ativo INTEGER DEFAULT 1", "usuarios.ativo", $log, $erros);

    if (!colunaExiste($pdo, 'usuarios', 'ultimo_acesso'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN ultimo_acesso TEXT NULL", "usuarios.ultimo_acesso", $log, $erros);

    if (!colunaExiste($pdo, 'usuarios', 'deleted_at'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN deleted_at TEXT NULL", "usuarios.deleted_at", $log, $erros);

    if (!colunaExiste($pdo, 'usuarios', 'sync_status'))
        aplicar($pdo, "ALTER TABLE usuarios ADD COLUMN sync_status INTEGER DEFAULT 0", "usuarios.sync_status", $log, $erros);

    if (!indiceExiste($pdo, 'idx_usuarios_uuid'))
        aplicar($pdo, "CREATE INDEX idx_usuarios_uuid ON usuarios(uuid)", "Índice usuarios.uuid", $log, $erros);
    if (!indiceExiste($pdo, 'idx_usuarios_username'))
        aplicar($pdo, "CREATE INDEX idx_usuarios_username ON usuarios(username)", "Índice usuarios.username", $log, $erros);


    /* =====================================================
       6. TABELA: caixa_sessoes (nova)
    ===================================================== */
    $log[] = "";
    $log[] = "── CAIXA SESSÕES ───────────────────────────";

    if (!tabelaExiste($pdo, 'caixa_sessoes')) {
        aplicar($pdo, "
            CREATE TABLE caixa_sessoes (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid          TEXT UNIQUE NOT NULL,
                usuario_id    INTEGER NOT NULL,
                saldo_inicial REAL DEFAULT 0,
                saldo_final   REAL NULL,
                aberto_em     TEXT DEFAULT CURRENT_TIMESTAMP,
                fechado_em    TEXT NULL,
                observacao    TEXT,
                status        TEXT DEFAULT 'aberta',
                created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at    TEXT DEFAULT CURRENT_TIMESTAMP,
                sync_status   INTEGER DEFAULT 0
            )
        ", "Tabela caixa_sessoes criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela caixa_sessoes (já existe)";
    }

    if (!indiceExiste($pdo, 'idx_caixa_sessoes_status'))
        aplicar($pdo, "CREATE INDEX idx_caixa_sessoes_status ON caixa_sessoes(status)", "Índice caixa_sessoes.status", $log, $erros);
    if (!indiceExiste($pdo, 'idx_caixa_sessoes_sync'))
        aplicar($pdo, "CREATE INDEX idx_caixa_sessoes_sync ON caixa_sessoes(sync_status)", "Índice caixa_sessoes.sync", $log, $erros);


    /* =====================================================
       7. TABELA: caixa_movimentos (nova)
    ===================================================== */
    $log[] = "";
    $log[] = "── CAIXA MOVIMENTOS ────────────────────────";

    if (!tabelaExiste($pdo, 'caixa_movimentos')) {
        aplicar($pdo, "
            CREATE TABLE caixa_movimentos (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid       TEXT UNIQUE NOT NULL,
                sessao_id  INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                tipo       TEXT NOT NULL,
                valor      REAL NOT NULL,
                motivo     TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL,
                sync_status INTEGER DEFAULT 0
            )
        ", "Tabela caixa_movimentos criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela caixa_movimentos (já existe)";
    }

    if (!indiceExiste($pdo, 'idx_caixa_mov_sessao'))
        aplicar($pdo, "CREATE INDEX idx_caixa_mov_sessao ON caixa_movimentos(sessao_id)", "Índice caixa_movimentos.sessao_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_caixa_mov_sync'))
        aplicar($pdo, "CREATE INDEX idx_caixa_mov_sync ON caixa_movimentos(sync_status)", "Índice caixa_movimentos.sync", $log, $erros);


    /* =====================================================
       8. TABELA: vendas — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── VENDAS ──────────────────────────────────";

    if (!colunaExiste($pdo, 'vendas', 'uuid'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN uuid TEXT", "vendas.uuid", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'sessao_id'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN sessao_id INTEGER", "vendas.sessao_id", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'subtotal'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN subtotal REAL DEFAULT 0", "vendas.subtotal", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'desconto'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN desconto REAL DEFAULT 0", "vendas.desconto", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'imposto'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN imposto REAL DEFAULT 0", "vendas.imposto", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'valor_recebido'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN valor_recebido REAL DEFAULT 0", "vendas.valor_recebido", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'troco'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN troco REAL DEFAULT 0", "vendas.troco", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'numero_referencia'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN numero_referencia TEXT NULL", "vendas.numero_referencia", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'observacao'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN observacao TEXT", "vendas.observacao", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'deleted_at'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN deleted_at TEXT NULL", "vendas.deleted_at", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'sync_tentativas'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN sync_tentativas INTEGER DEFAULT 0", "vendas.sync_tentativas", $log, $erros);

    if (!colunaExiste($pdo, 'vendas', 'sync_erro'))
        aplicar($pdo, "ALTER TABLE vendas ADD COLUMN sync_erro TEXT NULL", "vendas.sync_erro", $log, $erros);

    // Fix: coluna 'data' antiga → created_at (só renomeia se ainda existir e created_at não)
    if (colunaExiste($pdo, 'vendas', 'data') && !colunaExiste($pdo, 'vendas', 'created_at')) {
        aplicar($pdo, "ALTER TABLE vendas RENAME COLUMN data TO created_at", "vendas: data → created_at", $log, $erros);
    }

    if (!indiceExiste($pdo, 'idx_vendas_uuid'))
        aplicar($pdo, "CREATE INDEX idx_vendas_uuid ON vendas(uuid)", "Índice vendas.uuid", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vendas_cliente'))
        aplicar($pdo, "CREATE INDEX idx_vendas_cliente ON vendas(cliente_id)", "Índice vendas.cliente_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vendas_sessao'))
        aplicar($pdo, "CREATE INDEX idx_vendas_sessao ON vendas(sessao_id)", "Índice vendas.sessao_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vendas_status'))
        aplicar($pdo, "CREATE INDEX idx_vendas_status ON vendas(status)", "Índice vendas.status", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vendas_sync'))
        aplicar($pdo, "CREATE INDEX idx_vendas_sync ON vendas(sync_status)", "Índice vendas.sync", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vendas_data'))
        aplicar($pdo, "CREATE INDEX idx_vendas_data ON vendas(created_at)", "Índice vendas.created_at", $log, $erros);


    /* =====================================================
       9. TABELA: venda_itens — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── VENDA ITENS ─────────────────────────────";

    if (!colunaExiste($pdo, 'venda_itens', 'uuid'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN uuid TEXT", "venda_itens.uuid", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'nome_produto'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN nome_produto TEXT", "venda_itens.nome_produto", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'preco_custo'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN preco_custo REAL DEFAULT 0", "venda_itens.preco_custo", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'desconto'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN desconto REAL DEFAULT 0", "venda_itens.desconto", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'subtotal'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN subtotal REAL DEFAULT 0", "venda_itens.subtotal", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'deleted_at'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN deleted_at TEXT NULL", "venda_itens.deleted_at", $log, $erros);

    if (!colunaExiste($pdo, 'venda_itens', 'sync_status'))
        aplicar($pdo, "ALTER TABLE venda_itens ADD COLUMN sync_status INTEGER DEFAULT 0", "venda_itens.sync_status", $log, $erros);

    // Preencher subtotal nos itens antigos que têm preco e quantidade
    aplicar($pdo, "
        UPDATE venda_itens
        SET subtotal = round(preco * quantidade, 2)
        WHERE subtotal = 0 AND preco > 0 AND quantidade > 0
    ", "Fix: subtotal calculado em itens antigos", $log, $erros);

    if (!indiceExiste($pdo, 'idx_vitens_venda'))
        aplicar($pdo, "CREATE INDEX idx_vitens_venda ON venda_itens(venda_id)", "Índice venda_itens.venda_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vitens_produto'))
        aplicar($pdo, "CREATE INDEX idx_vitens_produto ON venda_itens(produto_id)", "Índice venda_itens.produto_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_vitens_sync'))
        aplicar($pdo, "CREATE INDEX idx_vitens_sync ON venda_itens(sync_status)", "Índice venda_itens.sync", $log, $erros);


    /* =====================================================
       10. TABELAS: devolucoes + devolucao_itens (novas)
    ===================================================== */
    $log[] = "";
    $log[] = "── DEVOLUÇÕES ──────────────────────────────";

    if (!tabelaExiste($pdo, 'devolucoes')) {
        aplicar($pdo, "
            CREATE TABLE devolucoes (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid       TEXT UNIQUE NOT NULL,
                venda_id   INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                motivo     TEXT,
                total      REAL DEFAULT 0,
                status     TEXT DEFAULT 'pendente',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL,
                sync_status INTEGER DEFAULT 0
            )
        ", "Tabela devolucoes criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela devolucoes (já existe)";
    }

    if (!tabelaExiste($pdo, 'devolucao_itens')) {
        aplicar($pdo, "
            CREATE TABLE devolucao_itens (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid          TEXT UNIQUE NOT NULL,
                devolucao_id  INTEGER NOT NULL,
                venda_item_id INTEGER NOT NULL,
                quantidade    REAL DEFAULT 1,
                preco         REAL DEFAULT 0,
                subtotal      REAL DEFAULT 0,
                created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
                sync_status   INTEGER DEFAULT 0
            )
        ", "Tabela devolucao_itens criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela devolucao_itens (já existe)";
    }

    if (!indiceExiste($pdo, 'idx_dev_venda'))
        aplicar($pdo, "CREATE INDEX idx_dev_venda ON devolucoes(venda_id)", "Índice devolucoes.venda_id", $log, $erros);
    if (!indiceExiste($pdo, 'idx_dev_sync'))
        aplicar($pdo, "CREATE INDEX idx_dev_sync ON devolucoes(sync_status)", "Índice devolucoes.sync", $log, $erros);


    /* =====================================================
       11. TABELA: sync_queue — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── SYNC QUEUE ──────────────────────────────";

    if (!tabelaExiste($pdo, 'sync_queue')) {
        aplicar($pdo, "
            CREATE TABLE sync_queue (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name      TEXT NOT NULL,
                record_id       INTEGER,
                uuid            TEXT,
                operation       TEXT NOT NULL,
                payload         TEXT NOT NULL,
                payload_hash    TEXT,
                prioridade      INTEGER DEFAULT 5,
                status          TEXT DEFAULT 'pending',
                tentativas      INTEGER DEFAULT 0,
                max_tentativas  INTEGER DEFAULT 5,
                erro            TEXT NULL,
                sincronizado_em TEXT NULL,
                created_at      TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at      TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ", "Tabela sync_queue criada", $log, $erros);
    } else {
        if (!colunaExiste($pdo, 'sync_queue', 'payload_hash'))
            aplicar($pdo, "ALTER TABLE sync_queue ADD COLUMN payload_hash TEXT", "sync_queue.payload_hash", $log, $erros);
        if (!colunaExiste($pdo, 'sync_queue', 'prioridade'))
            aplicar($pdo, "ALTER TABLE sync_queue ADD COLUMN prioridade INTEGER DEFAULT 5", "sync_queue.prioridade", $log, $erros);
        if (!colunaExiste($pdo, 'sync_queue', 'sincronizado_em'))
            aplicar($pdo, "ALTER TABLE sync_queue ADD COLUMN sincronizado_em TEXT NULL", "sync_queue.sincronizado_em", $log, $erros);
    }

    if (!indiceExiste($pdo, 'idx_sync_status'))
        aplicar($pdo, "CREATE INDEX idx_sync_status ON sync_queue(status)", "Índice sync_queue.status", $log, $erros);
    if (!indiceExiste($pdo, 'idx_sync_prioridade'))
        aplicar($pdo, "CREATE INDEX idx_sync_prioridade ON sync_queue(prioridade, created_at)", "Índice sync_queue.prioridade", $log, $erros);
    if (!indiceExiste($pdo, 'idx_sync_uuid'))
        aplicar($pdo, "CREATE INDEX idx_sync_uuid ON sync_queue(uuid)", "Índice sync_queue.uuid", $log, $erros);
    // índice único para payload_hash (ignora NULL)
    if (!indiceExiste($pdo, 'idx_sync_hash'))
        aplicar($pdo, "CREATE UNIQUE INDEX idx_sync_hash ON sync_queue(payload_hash) WHERE payload_hash IS NOT NULL", "Índice único sync_queue.payload_hash", $log, $erros);


    /* =====================================================
       12. TABELA: configuracoes (nova)
    ===================================================== */
    $log[] = "";
    $log[] = "── CONFIGURAÇÕES ───────────────────────────";

    if (!tabelaExiste($pdo, 'configuracoes')) {
        aplicar($pdo, "
            CREATE TABLE configuracoes (
                chave      TEXT PRIMARY KEY,
                valor      TEXT,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ", "Tabela configuracoes criada", $log, $erros);
    } else {
        $log[] = "⏭️  Tabela configuracoes (já existe)";
    }

    // Inserir valores padrão (OR IGNORE não sobrescreve existentes)
    $defaults = [
        ['moeda',           'MZN'],
        ['nome_empresa',    'Mambo POS'],
        ['nif_empresa',     ''],
        ['endereco',        ''],
        ['iva_percentagem', '16'],
        ['iva_activo',      '0'],
        ['sync_intervalo',  '30'],
        ['versao_schema',   '2'],
    ];
    $stmtCfg = $pdo->prepare("INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)");
    foreach ($defaults as [$chave, $valor]) {
        $stmtCfg->execute([$chave, $valor]);
    }
    $log[] = "✅ Valores padrão de configuracoes inseridos (OR IGNORE)";

    // Actualizar versão do schema
    $pdo->prepare("INSERT OR REPLACE INTO configuracoes (chave, valor, updated_at) VALUES ('versao_schema', '2', datetime('now'))")
        ->execute();
    $log[] = "✅ versao_schema = 2";


    /* =====================================================
       13. TABELA: logs — colunas novas
    ===================================================== */
    $log[] = "";
    $log[] = "── LOGS ────────────────────────────────────";

    if (!colunaExiste($pdo, 'logs', 'nivel'))
        aplicar($pdo, "ALTER TABLE logs ADD COLUMN nivel TEXT DEFAULT 'info'", "logs.nivel", $log, $erros);

    if (!colunaExiste($pdo, 'logs', 'detalhe'))
        aplicar($pdo, "ALTER TABLE logs ADD COLUMN detalhe TEXT", "logs.detalhe", $log, $erros);

    if (!colunaExiste($pdo, 'logs', 'usuario_id'))
        aplicar($pdo, "ALTER TABLE logs ADD COLUMN usuario_id INTEGER NULL", "logs.usuario_id", $log, $erros);

    if (!indiceExiste($pdo, 'idx_logs_tipo'))
        aplicar($pdo, "CREATE INDEX idx_logs_tipo ON logs(tipo)", "Índice logs.tipo", $log, $erros);
    if (!indiceExiste($pdo, 'idx_logs_nivel'))
        aplicar($pdo, "CREATE INDEX idx_logs_nivel ON logs(nivel)", "Índice logs.nivel", $log, $erros);
    if (!indiceExiste($pdo, 'idx_logs_data'))
        aplicar($pdo, "CREATE INDEX idx_logs_data ON logs(created_at)", "Índice logs.created_at", $log, $erros);


    /* =====================================================
       RESULTADO FINAL
    ===================================================== */
    $pdo->exec("PRAGMA foreign_keys = ON;");

    $log[] = "";
    $log[] = str_repeat("─", 44);

    if (empty($erros)) {
        $log[] = "✅ Migração v2 concluída sem erros.";
    } else {
        $log[] = "⚠️  Migração concluída com " . count($erros) . " erro(s). Ver abaixo.";
    }

} catch (Throwable $e) {
    $erros[] = "💥 ERRO CRÍTICO: " . $e->getMessage();
}

/* =====================================================
   OUTPUT
===================================================== */
$isCli = PHP_SAPI === 'cli';
$nl     = $isCli ? "\n" : "<br>";

if (!$isCli) {
    echo "<pre style='font-family:monospace;background:#111;color:#eee;padding:20px;border-radius:8px;'>";
    echo "<strong style='color:#7ef57e'>MAMBO POS — Migração v1 → v2</strong>\n\n";
}

foreach ($log as $linha) {
    echo $linha . $nl;
}

if (!empty($erros)) {
    echo $nl . ($isCli ? "" : "<strong style='color:#ff6b6b'>") . "ERROS:" . ($isCli ? "" : "</strong>") . $nl;
    foreach ($erros as $e) {
        echo $e . $nl;
    }
}

if (!$isCli) echo "</pre>";