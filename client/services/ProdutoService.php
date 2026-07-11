<?php

require_once __DIR__ . '/../config/database.php';

class ProdutoService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectarLocal();
    }

    /**
     * Colunas base devolvidas em todos os métodos.
     * Mantém `preco_venda` como alias para não partir
     * código front-end que já usa essa chave.
     */
    private function colunas(): string
    {
        return "
            p.id,
            p.nome,
            p.codigo_barra,
            p.preco,
            p.preco          AS preco_venda,  -- alias legado
            p.custo,
            p.unidade,
            p.estoque,
            p.estoque_minimo,
            p.categoria_id,
            c.nome           AS categoria_nome,
            p.imagem,
            p.ativo
        ";
    }

    /**
     * BUSCAR PRODUTOS POR NOME OU CÓDIGO DE BARRAS
     */
    public function buscar(string $term): array
    {
        $stmt = $this->pdo->prepare("
            SELECT " . $this->colunas() . "
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE
                p.deleted_at IS NULL
                AND (p.ativo = 1 OR p.ativo IS NULL)
                AND (
                    p.codigo_barra LIKE :term
                    OR p.nome      LIKE :term
                )
            ORDER BY p.nome ASC
            LIMIT 30
        ");

        $stmt->execute([':term' => "%{$term}%"]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * BUSCAR PRODUTO POR ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT " . $this->colunas() . "
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE
                p.id = :id
                AND p.deleted_at IS NULL
                AND (p.ativo = 1 OR p.ativo IS NULL)
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * BUSCAR POR CÓDIGO DE BARRAS (SCANNER)
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT " . $this->colunas() . "
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE
                p.codigo_barra = :codigo
                AND p.deleted_at IS NULL
                AND (p.ativo = 1 OR p.ativo IS NULL)
            LIMIT 1
        ");

        $stmt->execute([':codigo' => $codigo]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * LISTAR TODOS (com filtro opcional por categoria)
     */
    public function listar(?int $categoria_id = null): array
    {
        $where = "p.deleted_at IS NULL AND (p.ativo = 1 OR p.ativo IS NULL)";
        $params = [];

        if ($categoria_id !== null) {
            $where   .= " AND p.categoria_id = :categoria_id";
            $params[':categoria_id'] = $categoria_id;
        }

        $stmt = $this->pdo->prepare("
            SELECT " . $this->colunas() . "
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE $where
            ORDER BY c.nome ASC, p.nome ASC
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PRODUTOS COM STOCK BAIXO
     * (estoque <= estoque_minimo e estoque_minimo > 0)
     */
    public function stockBaixo(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT " . $this->colunas() . "
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE
                p.deleted_at IS NULL
                AND (p.ativo = 1 OR p.ativo IS NULL)
                AND p.estoque_minimo > 0
                AND p.estoque <= p.estoque_minimo
            ORDER BY p.estoque ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}