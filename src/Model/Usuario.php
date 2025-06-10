<?php
namespace Src\Model;

class Usuario {
    private $conn;
    private $tabela = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function autenticar($username, $senha) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabela} WHERE username = ?");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return $usuario;
        }

        return false;
    }

    public function listarTodos() {
        $sql = "SELECT * FROM {$this->tabela} ORDER BY id DESC";
        return $this->conn->query($sql);
    }

    public function encontrarPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabela} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
