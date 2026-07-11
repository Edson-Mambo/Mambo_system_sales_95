<?php

namespace Services;

use PDO;
use Throwable;

require_once __DIR__ . '/../config/database.php';

class StockService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::conectarLocal();
        $this->pdo->exec("PRAGMA foreign_keys = ON;");
        $this->garantirTabela();
    }

    /* =========================
       ENTRADA DE STOCK
    ========================= */
    public function entrada(
        int    $produtoId,
        float  $quantidade,          // float: suporta kg, lt
        string $motivo    = 'Reposição',
        ?int   $usuarioId = null
    ): array {

        if ($quantidade <= 0) {
            return ['success' => false, 'message' => 'Quantidade inválida'];
        }

        $produto = $this->buscarProduto($produtoId);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado'];
        }

        $estoqueAnterior = (float)$produto['estoque'];
        $novoEstoque     = round($estoqueAnterior + $quantidade, 4);

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                UPDATE produtos
                SET estoque     = :estoque,
                    sync_status = 0,
                    updated_at  = datetime('now')
                WHERE id = :id
            ")->execute([':estoque' => $novoEstoque, ':id' => $produtoId]);

            $this->registrarMovimento([
                'produto_id'      => $produtoId,
                'tipo'            => 'entrada',
                'quantidade'      => $quantidade,
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'   => $novoEstoque,
                'motivo'          => $motivo,
                'usuario_id'      => $usuarioId,
            ]);

            $this->pdo->commit();

            return [
                'success'          => true,
                'message'          => 'Entrada de stock realizada',
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'    => $novoEstoque,
            ];

        } catch (Throwable $e) {
            $this->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       SAÍDA DE STOCK
    ========================= */
    public function saida(
        int    $produtoId,
        float  $quantidade,
        string $motivo    = 'Venda',
        ?int   $usuarioId = null
    ): array {

        if ($quantidade <= 0) {
            return ['success' => false, 'message' => 'Quantidade inválida'];
        }

        $produto = $this->buscarProduto($produtoId);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado'];
        }

        $estoqueAnterior = (float)$produto['estoque'];

        if ($estoqueAnterior < $quantidade) {
            return [
                'success' => false,
                'message' => 'Stock insuficiente',
                'estoque' => $estoqueAnterior,
            ];
        }

        $novoEstoque = round($estoqueAnterior - $quantidade, 4);

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                UPDATE produtos
                SET estoque     = :estoque,
                    sync_status = 0,
                    updated_at  = datetime('now')
                WHERE id = :id
            ")->execute([':estoque' => $novoEstoque, ':id' => $produtoId]);

            $this->registrarMovimento([
                'produto_id'       => $produtoId,
                'tipo'             => 'saida',
                'quantidade'       => $quantidade,
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'    => $novoEstoque,
                'motivo'           => $motivo,
                'usuario_id'       => $usuarioId,
            ]);

            $this->pdo->commit();

            return [
                'success'          => true,
                'message'          => 'Saída de stock realizada',
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'    => $novoEstoque,
            ];

        } catch (Throwable $e) {
            $this->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       AJUSTE DE STOCK
    ========================= */
    public function ajustar(
        int    $produtoId,
        float  $novoEstoque,
        string $motivo    = 'Ajuste manual',
        ?int   $usuarioId = null
    ): array {

        if ($novoEstoque < 0) {
            return ['success' => false, 'message' => 'Stock inválido'];
        }

        $produto = $this->buscarProduto($produtoId);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado'];
        }

        $estoqueAnterior = (float)$produto['estoque'];
        $novoEstoque     = round($novoEstoque, 4);

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                UPDATE produtos
                SET estoque     = :estoque,
                    sync_status = 0,
                    updated_at  = datetime('now')
                WHERE id = :id
            ")->execute([':estoque' => $novoEstoque, ':id' => $produtoId]);

            $this->registrarMovimento([
                'produto_id'       => $produtoId,
                'tipo'             => 'ajuste',
                'quantidade'       => abs($novoEstoque - $estoqueAnterior),
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'    => $novoEstoque,
                'motivo'           => $motivo,
                'usuario_id'       => $usuarioId,
            ]);

            $this->pdo->commit();

            return [
                'success'          => true,
                'message'          => 'Stock ajustado',
                'estoque_anterior' => $estoqueAnterior,
                'estoque_atual'    => $novoEstoque,
            ];

        } catch (Throwable $e) {
            $this->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       HISTÓRICO DE MOVIMENTOS
    ========================= */
    public function historico(
        ?int $produtoId = null,
        int  $limite    = 100
    ): array {

        if ($produtoId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT
                    m.*,
                    p.nome AS produto_nome,
                    p.unidade
                FROM stock_movimentos m
                LEFT JOIN produtos p ON p.id = m.produto_id
                WHERE m.produto_id = ?
                ORDER BY m.id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $produtoId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limite,    PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT
                    m.*,
                    p.nome AS produto_nome,
                    p.unidade
                FROM stock_movimentos m
                LEFT JOIN produtos p ON p.id = m.produto_id
                ORDER BY m.id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       PRODUTOS COM STOCK BAIXO
       Usa estoque_minimo definido por produto.
       Fallback: limite fixo se estoque_minimo = 0.
    ========================= */
    public function baixoStock(float $limiteFallback = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.nome,
                p.codigo_barra,
                p.estoque,
                p.estoque_minimo,
                p.unidade,
                c.nome AS categoria_nome
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE
                p.deleted_at IS NULL
                AND (p.ativo = 1 OR p.ativo IS NULL)
                AND (
                    -- tem mínimo definido: usa-o
                    (p.estoque_minimo > 0 AND p.estoque <= p.estoque_minimo)
                    OR
                    -- sem mínimo: usa fallback
                    (p.estoque_minimo = 0 AND p.estoque <= :fallback)
                )
            ORDER BY p.estoque ASC
        ");

        $stmt->bindValue(':fallback', $limiteFallback);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       PRIVADOS
    ========================= */
    private function buscarProduto(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, estoque, estoque_minimo, unidade
            FROM produtos
            WHERE id = ?
              AND (ativo = 1 OR ativo IS NULL)
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function registrarMovimento(array $d): void
    {
        $this->pdo->prepare("
            INSERT INTO stock_movimentos (
                produto_id,
                tipo,
                quantidade,
                estoque_anterior,
                estoque_atual,
                motivo,
                usuario_id,
                sync_status,
                created_at
            ) VALUES (
                :produto_id,
                :tipo,
                :quantidade,
                :estoque_anterior,
                :estoque_atual,
                :motivo,
                :usuario_id,
                0,
                datetime('now')
            )
        ")->execute([
            ':produto_id'       => $d['produto_id'],
            ':tipo'             => $d['tipo'],
            ':quantidade'       => $d['quantidade'],
            ':estoque_anterior' => $d['estoque_anterior'],
            ':estoque_atual'    => $d['estoque_atual'],
            ':motivo'           => $d['motivo'],
            ':usuario_id'       => $d['usuario_id'],
        ]);
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Cria a tabela stock_movimentos se não existir.
     * Chamado no __construct — seguro em produção.
     */
    private function garantirTabela(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS stock_movimentos (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id       INTEGER NOT NULL,
                tipo             TEXT NOT NULL,   -- entrada | saida | ajuste
                quantidade       REAL NOT NULL,
                estoque_anterior REAL DEFAULT 0,
                estoque_atual    REAL DEFAULT 0,
                motivo           TEXT,
                usuario_id       INTEGER NULL,
                sync_status      INTEGER DEFAULT 0,
                created_at       TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id)
            )
        ");
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_smov_produto ON stock_movimentos(produto_id);
        ");
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_smov_tipo ON stock_movimentos(tipo);
        ");
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_smov_sync ON stock_movimentos(sync_status);
        ");
    }
}