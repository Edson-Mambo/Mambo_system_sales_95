<?php
namespace Src\Model;

class ProdutoVenda {
    private $conn;
    private $tabela = "produtos_para_venda";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function adicionarProduto($codigo_barras, $quantidade) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->tabela} (codigo_barras, quantidade) VALUES (?, ?)");
        return $stmt->execute([$codigo_barras, $quantidade]);
    }

    public function listarTodos() {
        $sql = "SELECT * FROM {$this->tabela}";
        return $this->conn->query($sql);
    }

    public function limpar() {
        $sql = "DELETE FROM {$this->tabela}";
        return $this->conn->query($sql);
    }
}
