<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class CarrinhoService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            "sqlite:" . __DIR__ . "/../localdb/mambo_local.db",
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

        public function adicionar(array $produto, float $quantidade): void
    {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }

        $id = (int)($produto['id'] ?? $produto['produto_id'] ?? 0);

        if ($id <= 0) {
            throw new Exception("Produto inválido ao adicionar ao carrinho.");
        }

        $stmt = $this->pdo->prepare("
            SELECT id, nome, codigo_barra, preco
            FROM produtos
            WHERE id = ?
            AND (ativo = 1 OR ativo IS NULL)
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Produto não encontrado.");
        }

        $preco = (float)$row['preco'];

        if ($preco <= 0) {
            throw new Exception("Preço inválido.");
        }

        // SE EXISTE → atualiza
        if (isset($_SESSION['carrinho'][$id])) {

            $_SESSION['carrinho'][$id]['quantidade'] += $quantidade;

        } else {

            $_SESSION['carrinho'][$id] = [
                'produto_id' => $id,
                'nome'       => $row['nome'],
                'codigo'     => $row['codigo_barra'] ?? '',
                'preco'      => $preco,
                'quantidade' => $quantidade,
            ];
        }

        // 🔥 SEMPRE recalcular subtotal (CRÍTICO PARA VENDA)
        $_SESSION['carrinho'][$id]['subtotal'] =
            $_SESSION['carrinho'][$id]['preco'] *
            $_SESSION['carrinho'][$id]['quantidade'];
    }

    public function listar(): array
    {
        // Devolve array indexado (não associativo) para o VendaService
        return array_values($_SESSION['carrinho'] ?? []);
    }

    public function total(): float
    {
        $total = 0.0;

        foreach ($this->listar() as $item) {
            $total += (float)($item['preco'] ?? 0) * (float)($item['quantidade'] ?? 0);
        }

        return round($total, 2);
    }

    public function limpar(): void
    {
        $_SESSION['carrinho'] = [];
    }

    public function atualizarQuantidade(int $id, float $quantidade): void
    {
        if (isset($_SESSION['carrinho'][$id])) {
            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$id]);
            } else {
                $_SESSION['carrinho'][$id]['quantidade'] = $quantidade;
            }
        }
    }

    public function remover(int $id): void
    {
        unset($_SESSION['carrinho'][$id]);
    }

    public function contar(): int
    {
        return count($_SESSION['carrinho'] ?? []);
    }
}