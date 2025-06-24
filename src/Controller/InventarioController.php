<?php
namespace Controller;

use PDO;

class InventarioController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarProdutos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM produtos ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarEstoque($id, $novaQuantidade): bool
    {
        $stmt = $this->pdo->prepare("UPDATE produtos SET quantidade = :qtd WHERE id = :id");
        return $stmt->execute([':qtd' => $novaQuantidade, ':id' => $id]);
    }
}
