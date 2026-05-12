<?php

require_once __DIR__ . '/../../config/database.php';

class ClienteService {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::conectar();
    }

    /**
     * Buscar clientes por nome ou telefone
     */
    public function buscar($termo) {

        $stmt = $this->pdo->prepare("
            SELECT id, nome, apelido, telefone, email
            FROM clientes
            WHERE nome LIKE ?
               OR apelido LIKE ?
               OR telefone LIKE ?
            ORDER BY nome ASC
            LIMIT 20
        ");

        $like = "%$termo%";

        $stmt->execute([$like, $like, $like]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar cliente por ID
     */
    public function getById($id) {

        $stmt = $this->pdo->prepare("
            SELECT * FROM clientes WHERE id = ? LIMIT 1
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar cliente
     */
    public function criar($data) {

        $stmt = $this->pdo->prepare("
            INSERT INTO clientes (nome, apelido, telefone, email, morada)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['nome'],
            $data['apelido'] ?? null,
            $data['telefone'],
            $data['email'] ?? null,
            $data['morada'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }
}