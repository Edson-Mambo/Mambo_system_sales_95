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
     * BUSCAR PRODUTOS (NOME OU CÓDIGO DE BARRAS)
     */
    public function buscar(string $term): array
    {
        $sql = "
            SELECT 
                id,
                nome,
                codigo_barra,
                preco AS preco_venda,
                estoque
            FROM produtos
            WHERE 
                codigo_barra LIKE :term
                OR nome LIKE :term
            ORDER BY nome ASC
            LIMIT 20
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':term' => "%{$term}%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * BUSCAR PRODUTO POR ID
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                nome,
                codigo_barra,
                preco AS preco_venda,
                estoque
            FROM produtos
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }

    /**
     * BUSCAR POR CÓDIGO (SCANNER)
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                nome,
                codigo_barra,
                preco AS preco_venda,
                estoque
            FROM produtos
            WHERE codigo_barra = :codigo
            LIMIT 1
        ");

        $stmt->execute([
            ':codigo' => $codigo
        ]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }
}