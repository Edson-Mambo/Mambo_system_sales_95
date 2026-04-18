<?php
namespace Src\Model;

class LogLogin {
    private $conn;
    private $tabela = "logs_login";

    public function __construct($db) {
        $this->conn = $db;
    }

    /* =========================
       REGISTRAR LOGIN
    ========================= */
    public function registrarLogin($usuario_id) {

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->tabela}
            (usuario_id, data_login, ip, user_agent, status, session_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $usuario_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            'sucesso',
            session_id()
        ]);
    }

    /* =========================
       REGISTRAR LOGIN FALHADO
    ========================= */
    public function registrarFalha($usuario_id = null) {

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->tabela}
            (usuario_id, data_login, ip, user_agent, status, session_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $usuario_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            'falha',
            session_id()
        ]);
    }

    /* =========================
       REGISTRAR LOGOUT
    ========================= */
    public function registrarLogout($usuario_id) {

        $stmt = $this->conn->prepare("
            UPDATE {$this->tabela}
            SET data_logout = NOW()
            WHERE usuario_id = ?
            AND session_id = ?
            AND data_logout IS NULL
        ");

        return $stmt->execute([
            $usuario_id,
            session_id()
        ]);
    }

    /* =========================
       LISTAR TODOS
    ========================= */
    public function listarTodos() {
        $sql = "
            SELECT *
            FROM {$this->tabela}
            ORDER BY id DESC
        ";
        return $this->conn->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* =========================
       ÚLTIMO LOGIN DO USUÁRIO
    ========================= */
    public function ultimoLogin($usuario_id) {

        $stmt = $this->conn->prepare("
            SELECT *
            FROM {$this->tabela}
            WHERE usuario_id = ?
            AND status = 'sucesso'
            ORDER BY data_login DESC
            LIMIT 1
        ");

        $stmt->execute([$usuario_id]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /* =========================
       LOGINS ATIVOS
    ========================= */
    public function loginsAtivos() {

        $stmt = $this->conn->query("
            SELECT *
            FROM {$this->tabela}
            WHERE data_logout IS NULL
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}