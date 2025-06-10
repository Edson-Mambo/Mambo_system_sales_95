<?php
namespace Src\Model;

class Configuracoes {
    private $conn;
    private $tabela = "configuracoes";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function buscarTodas() {
        $sql = "SELECT * FROM {$this->tabela}";
        return $this->conn->query($sql);
    }

    public function atualizar($chave, $valor) {
        $stmt = $this->conn->prepare("UPDATE {$this->tabela} SET valor = ? WHERE chave = ?");
        return $stmt->execute([$valor, $chave]);
    }
}
