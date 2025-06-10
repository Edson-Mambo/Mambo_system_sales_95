<?php
namespace Src\Model;

class MovimentoEstoque {
    private $conn;
    private $tabela = "movimento_estoque";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrarMovimento($produto_id, $quantidade, $tipo, $data_hora, $referencia) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->tabela} (produto_id, quantidade, tipo, data_hora, referencia) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$produto_id, $quantidade, $tipo, $data_hora, $referencia]);
    }

    public function listarSaidas() {
        $sql = "SELECT * FROM {$this->tabela} WHERE tipo = 'saida' ORDER BY data_hora DESC";
        return $this->conn->query($sql);
    }
}
