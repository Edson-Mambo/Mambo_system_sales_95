<?php
namespace Src\Model;

class LogLogin {
    private $conn;
    private $tabela = "logs_login";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrarLogin($usuario_id, $data_login) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->tabela} (usuario_id, data_login) VALUES (?, ?)");
        return $stmt->execute([$usuario_id, $data_login]);
    }

    public function registrarLogout($log_id, $data_logout) {
        $stmt = $this->conn->prepare("UPDATE {$this->tabela} SET data_logout = ? WHERE id = ?");
        return $stmt->execute([$data_logout, $log_id]);
    }

    public function listarTodos() {
        $sql = "SELECT * FROM {$this->tabela} ORDER BY id DESC";
        return $this->conn->query($sql);
    }
}
