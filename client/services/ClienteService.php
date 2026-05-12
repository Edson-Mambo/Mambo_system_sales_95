<?php

namespace Services;

use PDO;
use Exception;

require_once __DIR__ . '/../config/database.php';

class ClienteService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::conectar();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /* =========================
       BUSCAR CLIENTES
    ========================= */
    public function buscar(string $termo = ''): array
    {
        try {

            $termo = trim($termo);

            if (empty($termo)) {

                $stmt = $this->pdo->query("
                    SELECT *
                    FROM clientes
                    ORDER BY id DESC
                    LIMIT 50
                ");

            } else {

                $stmt = $this->pdo->prepare("
                    SELECT *
                    FROM clientes
                    WHERE
                        nome LIKE :termo
                        OR apelido LIKE :termo
                        OR telefone LIKE :termo
                        OR nuit LIKE :termo
                    ORDER BY nome ASC
                    LIMIT 50
                ");

                $stmt->execute([
                    ':termo' => "%{$termo}%"
                ]);
            }

            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'clientes' => $clientes
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       BUSCAR POR ID
    ========================= */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM clientes
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cliente ?: null;
    }

    /* =========================
       CADASTRAR CLIENTE
    ========================= */
    public function cadastrar(array $dados): array
    {
        try {

            $nome = trim($dados['nome'] ?? '');

            if (empty($nome)) {

                return [
                    'success' => false,
                    'message' => 'Nome obrigatório'
                ];
            }

            $telefone = trim($dados['telefone'] ?? '');

            /* =========================
               VERIFICAR DUPLICADO
            ========================= */

            if (!empty($telefone)) {

                $check = $this->pdo->prepare("
                    SELECT id
                    FROM clientes
                    WHERE telefone = ?
                    LIMIT 1
                ");

                $check->execute([$telefone]);

                if ($check->fetch()) {

                    return [
                        'success' => false,
                        'message' => 'Telefone já cadastrado'
                    ];
                }
            }

            /* =========================
               INSERIR
            ========================= */

            $stmt = $this->pdo->prepare("
                INSERT INTO clientes (
                    nome,
                    apelido,
                    telefone,
                    telefone_alt,
                    email,
                    morada,
                    nuit,
                    criado_em,
                    sync_status
                )
                VALUES (
                    :nome,
                    :apelido,
                    :telefone,
                    :telefone_alt,
                    :email,
                    :morada,
                    :nuit,
                    datetime('now'),
                    'pendente'
                )
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':apelido' => trim($dados['apelido'] ?? ''),
                ':telefone' => $telefone,
                ':telefone_alt' => trim($dados['telefone_alt'] ?? ''),
                ':email' => trim($dados['email'] ?? ''),
                ':morada' => trim($dados['morada'] ?? ''),
                ':nuit' => trim($dados['nuit'] ?? '')
            ]);

            $clienteId = (int)$this->pdo->lastInsertId();

            $cliente = $this->buscarPorId($clienteId);

            return [
                'success' => true,
                'message' => 'Cliente cadastrado',
                'cliente' => $cliente
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       ATUALIZAR CLIENTE
    ========================= */
    public function atualizar(int $id, array $dados): array
    {
        try {

            $cliente = $this->buscarPorId($id);

            if (!$cliente) {

                return [
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ];
            }

            $stmt = $this->pdo->prepare("
                UPDATE clientes SET
                    nome = :nome,
                    apelido = :apelido,
                    telefone = :telefone,
                    telefone_alt = :telefone_alt,
                    email = :email,
                    morada = :morada,
                    nuit = :nuit,
                    atualizado_em = datetime('now'),
                    sync_status = 'pendente'
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id,
                ':nome' => trim($dados['nome'] ?? ''),
                ':apelido' => trim($dados['apelido'] ?? ''),
                ':telefone' => trim($dados['telefone'] ?? ''),
                ':telefone_alt' => trim($dados['telefone_alt'] ?? ''),
                ':email' => trim($dados['email'] ?? ''),
                ':morada' => trim($dados['morada'] ?? ''),
                ':nuit' => trim($dados['nuit'] ?? '')
            ]);

            return [
                'success' => true,
                'message' => 'Cliente atualizado',
                'cliente' => $this->buscarPorId($id)
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       REMOVER CLIENTE
    ========================= */
    public function remover(int $id): array
    {
        try {

            $cliente = $this->buscarPorId($id);

            if (!$cliente) {

                return [
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ];
            }

            $stmt = $this->pdo->prepare("
                DELETE FROM clientes
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Cliente removido'
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       DEFINIR CLIENTE SESSÃO
    ========================= */
    public function selecionarCliente(int $clienteId): array
    {
        $cliente = $this->buscarPorId($clienteId);

        if (!$cliente) {

            return [
                'success' => false,
                'message' => 'Cliente inválido'
            ];
        }

        $_SESSION['cliente_id'] = $cliente['id'];

        $_SESSION['cliente_nome'] =
            trim(
                ($cliente['nome'] ?? '') . ' ' .
                ($cliente['apelido'] ?? '')
            );

        return [
            'success' => true,
            'cliente' => $cliente
        ];
    }

    /* =========================
       CLIENTE ATUAL
    ========================= */
    public function clienteAtual(): array
    {
        if (empty($_SESSION['cliente_id'])) {

            return [
                'id' => null,
                'nome' => 'Cliente Geral'
            ];
        }

        $cliente = $this->buscarPorId(
            (int)$_SESSION['cliente_id']
        );

        if (!$cliente) {

            return [
                'id' => null,
                'nome' => 'Cliente Geral'
            ];
        }

        return [
            'id' => $cliente['id'],
            'nome' =>
                trim(
                    ($cliente['nome'] ?? '') . ' ' .
                    ($cliente['apelido'] ?? '')
                ),
            'telefone' => $cliente['telefone'] ?? '',
            'email' => $cliente['email'] ?? '',
            'morada' => $cliente['morada'] ?? '',
            'nuit' => $cliente['nuit'] ?? ''
        ];
    }

    /* =========================
       LIMPAR CLIENTE
    ========================= */
    public function limparCliente(): void
    {
        unset($_SESSION['cliente_id']);
        unset($_SESSION['cliente_nome']);
    }

    /* =========================
       CLIENTES PENDENTES SYNC
    ========================= */
    public function pendentesSync(): array
    {
        $stmt = $this->pdo->query("
            SELECT *
            FROM clientes
            WHERE sync_status = 'pendente'
            ORDER BY id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       MARCAR SINCRONIZADO
    ========================= */
    public function marcarSincronizado(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE clientes
            SET sync_status = 'sincronizado'
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}