<?php

namespace Services;

use PDO;
use Exception;

require_once __DIR__ . '/../config/database.php';

class StockService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::conectar();
    }

    /* =========================
       ENTRADA DE STOCK
    ========================= */
    public function entrada(
        int $produtoId,
        int $quantidade,
        string $motivo = 'Reposição',
        ?int $usuarioId = null
    ): array {

        try {

            if ($quantidade <= 0) {

                return [
                    'success' => false,
                    'message' => 'Quantidade inválida'
                ];
            }

            $produto = $this->buscarProduto($produtoId);

            if (!$produto) {

                return [
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ];
            }

            $stockAnterior = (int)$produto['stock'];
            $novoStock = $stockAnterior + $quantidade;

            $this->pdo->beginTransaction();

            /* =========================
               ATUALIZAR STOCK
            ========================= */

            $update = $this->pdo->prepare("
                UPDATE produtos
                SET
                    stock = :stock,
                    sync_status = 'pendente',
                    atualizado_em = datetime('now')
                WHERE id = :id
            ");

            $update->execute([
                ':stock' => $novoStock,
                ':id' => $produtoId
            ]);

            /* =========================
               MOVIMENTO
            ========================= */

            $this->registrarMovimento([
                'produto_id' => $produtoId,
                'tipo' => 'entrada',
                'quantidade' => $quantidade,
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock,
                'motivo' => $motivo,
                'usuario_id' => $usuarioId
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Entrada de stock realizada',
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock
            ];

        } catch (Exception $e) {

            $this->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       SAÍDA DE STOCK
    ========================= */
    public function saida(
        int $produtoId,
        int $quantidade,
        string $motivo = 'Venda',
        ?int $usuarioId = null
    ): array {

        try {

            if ($quantidade <= 0) {

                return [
                    'success' => false,
                    'message' => 'Quantidade inválida'
                ];
            }

            $produto = $this->buscarProduto($produtoId);

            if (!$produto) {

                return [
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ];
            }

            $stockAnterior = (int)$produto['stock'];

            if ($stockAnterior < $quantidade) {

                return [
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'stock' => $stockAnterior
                ];
            }

            $novoStock = $stockAnterior - $quantidade;

            $this->pdo->beginTransaction();

            /* =========================
               UPDATE
            ========================= */

            $update = $this->pdo->prepare("
                UPDATE produtos
                SET
                    stock = :stock,
                    sync_status = 'pendente',
                    atualizado_em = datetime('now')
                WHERE id = :id
            ");

            $update->execute([
                ':stock' => $novoStock,
                ':id' => $produtoId
            ]);

            /* =========================
               MOVIMENTO
            ========================= */

            $this->registrarMovimento([
                'produto_id' => $produtoId,
                'tipo' => 'saida',
                'quantidade' => $quantidade,
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock,
                'motivo' => $motivo,
                'usuario_id' => $usuarioId
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Saída de stock realizada',
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock
            ];

        } catch (Exception $e) {

            $this->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       AJUSTE DE STOCK
    ========================= */
    public function ajustar(
        int $produtoId,
        int $novoStock,
        string $motivo = 'Ajuste manual',
        ?int $usuarioId = null
    ): array {

        try {

            if ($novoStock < 0) {

                return [
                    'success' => false,
                    'message' => 'Stock inválido'
                ];
            }

            $produto = $this->buscarProduto($produtoId);

            if (!$produto) {

                return [
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ];
            }

            $stockAnterior = (int)$produto['stock'];

            $this->pdo->beginTransaction();

            $update = $this->pdo->prepare("
                UPDATE produtos
                SET
                    stock = :stock,
                    sync_status = 'pendente',
                    atualizado_em = datetime('now')
                WHERE id = :id
            ");

            $update->execute([
                ':stock' => $novoStock,
                ':id' => $produtoId
            ]);

            $this->registrarMovimento([
                'produto_id' => $produtoId,
                'tipo' => 'ajuste',
                'quantidade' => abs($novoStock - $stockAnterior),
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock,
                'motivo' => $motivo,
                'usuario_id' => $usuarioId
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Stock ajustado',
                'stock_anterior' => $stockAnterior,
                'stock_atual' => $novoStock
            ];

        } catch (Exception $e) {

            $this->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       HISTÓRICO MOVIMENTOS
    ========================= */
    public function historico(
        ?int $produtoId = null,
        int $limite = 100
    ): array {

        if ($produtoId) {

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM movimentacao_stock
                WHERE produto_id = ?
                ORDER BY id DESC
                LIMIT ?
            ");

            $stmt->bindValue(1, $produtoId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limite, PDO::PARAM_INT);

            $stmt->execute();

        } else {

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM movimentacao_stock
                ORDER BY id DESC
                LIMIT ?
            ");

            $stmt->bindValue(1, $limite, PDO::PARAM_INT);

            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       BAIXO STOCK
    ========================= */
    public function baixoStock(int $limite = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM produtos
            WHERE stock <= :limite
            AND ativo = 1
            ORDER BY stock ASC
        ");

        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       PRODUTO
    ========================= */
    private function buscarProduto(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM produtos
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }

    /* =========================
       REGISTRAR MOVIMENTO
    ========================= */
    private function registrarMovimento(array $dados): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO movimentacao_stock (
                produto_id,
                tipo,
                quantidade,
                stock_anterior,
                stock_atual,
                motivo,
                usuario_id,
                criado_em,
                sync_status
            )
            VALUES (
                :produto_id,
                :tipo,
                :quantidade,
                :stock_anterior,
                :stock_atual,
                :motivo,
                :usuario_id,
                datetime('now'),
                'pendente'
            )
        ");

        $stmt->execute([
            ':produto_id' => $dados['produto_id'],
            ':tipo' => $dados['tipo'],
            ':quantidade' => $dados['quantidade'],
            ':stock_anterior' => $dados['stock_anterior'],
            ':stock_atual' => $dados['stock_atual'],
            ':motivo' => $dados['motivo'],
            ':usuario_id' => $dados['usuario_id']
        ]);
    }

    /* =========================
       ROLLBACK
    ========================= */
    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}