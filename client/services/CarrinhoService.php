<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class CarrinhoService
{
    private $pdo;

    public function __construct()
    {
        // 🔥 ligação direta à base de dados (SQLite no teu caso)
        $this->pdo = new PDO(
            "sqlite:" . __DIR__ . "/../localdb/mambo_local.db"
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function adicionar(array $produto, float $quantidade): void
    {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }

        $id = $produto['id'] ?? 0;

        if ($id <= 0) {
            throw new Exception("Produto inválido ao adicionar ao carrinho.");
        }

        // 🔥 SEMPRE buscar preço real da base de dados (fonte única de verdade)
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(preco_venda, preco, valor, 0) AS preco
            FROM produtos
            WHERE id = ?
        ");

        $stmt->execute([$id]);
        $preco = (float) $stmt->fetchColumn();

        if ($preco <= 0) {
            throw new Exception("Preço inválido para o produto ID: $id");
        }

        // 🔥 Se já existe no carrinho, apenas atualiza quantidade
        if (isset($_SESSION['carrinho'][$id])) {

            $_SESSION['carrinho'][$id]['quantidade'] += $quantidade;

        } else {

            $_SESSION['carrinho'][$id] = [
                'id'         => $id,
                'nome'       => $produto['nome'] ?? 'Produto',
                'codigo'     => $produto['codigo'] ?? '',
                'preco'      => $preco,
                'quantidade' => $quantidade
            ];
        }
    }

    public function listar(): array
    {
        return $_SESSION['carrinho'] ?? [];
    }

    public function total(): float
    {
        $total = 0;

        foreach ($this->listar() as $item) {

            $preco = (float)($item['preco'] ?? 0);
            $quantidade = (float)($item['quantidade'] ?? 0);

            $total += $preco * $quantidade;
        }

        return $total;
    }

    public function limpar(): void
    {
        $_SESSION['carrinho'] = [];
    }

    // 🔥 opcional: atualizar um item específico
    public function atualizarQuantidade(int $id, float $quantidade): void
    {
        if (isset($_SESSION['carrinho'][$id])) {
            $_SESSION['carrinho'][$id]['quantidade'] = $quantidade;
        }
    }

    // 🔥 opcional: remover item
    public function remover(int $id): void
    {
        if (isset($_SESSION['carrinho'][$id])) {
            unset($_SESSION['carrinho'][$id]);
        }
    }
}