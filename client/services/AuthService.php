<?php

namespace Services;

use PDO;
use Throwable;

require_once __DIR__ . '/../config/database.php';

class AuthService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::conectarLocal();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /* =========================
       LOGIN
    ========================= */
    public function login(string $username, string $senha): array
    {
        try {

            // schema v2: coluna é `username`, não `usuario`
            $stmt = $this->pdo->prepare("
                SELECT id, uuid, nome, username, password, nivel,
                       ativo, deleted_at
                FROM usuarios
                WHERE username = :username
                  AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([':username' => trim($username)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            if (!$user['ativo']) {
                return ['success' => false, 'message' => 'Conta desactivada'];
            }

            /* =========================
               VALIDAR SENHA
               schema v2: coluna é `password`, não `senha`
               SEGURANÇA: nunca comparar senha em texto simples
            ========================= */
            // schema v2: coluna é `password`
            $hash = $user['password'] ?? '';

            if (empty($hash)) {
                return ['success' => false, 'message' => 'Conta sem senha definida'];
            }

            if (!password_verify($senha, $hash)) {
                return ['success' => false, 'message' => 'Senha incorreta'];
            }

            // Re-hash automático se o algoritmo mudou (PHP 8+)
            if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
                $this->pdo->prepare("
                    UPDATE usuarios
                    SET password   = ?,
                        updated_at = datetime('now')
                    WHERE id = ?
                ")->execute([password_hash($senha, PASSWORD_BCRYPT), $user['id']]);
            }

           /* =========================
                SESSÃO DO UTILIZADOR
                ========================= */

                session_regenerate_id(true);

                $nivel = strtolower(trim($user['nivel'] ?? 'caixa'));

                $_SESSION['usuario_id']    = $user['id'];
                $_SESSION['usuario_uuid']  = $user['uuid'];
                $_SESSION['usuario_nome']  = $user['nome'];
                $_SESSION['usuario_login'] = $user['username'];

                /*
                Compatibilidade cliente-servidor
                mantém os dois nomes
                */
                $_SESSION['nivel_acesso'] = $nivel;
                $_SESSION['nivel']        = $nivel;

                $_SESSION['logado'] = true;

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['ultimo_acesso'] = time();

            /* =========================
               ÚLTIMO ACESSO
               schema v2: coluna é `ultimo_acesso`, não `ultimo_login`
            ========================= */
            $this->pdo->prepare("
                UPDATE usuarios
                SET ultimo_acesso = datetime('now'),
                    updated_at    = datetime('now')
                WHERE id = ?
            ")->execute([$user['id']]);

            return [
                'success' => true,
                'message' => 'Login realizado',
                'usuario' => [
                    'id'    => $user['id'],
                    'nome'  => $user['nome'],
                    'nivel' => $_SESSION['nivel_acesso'],
                ],
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       LOGIN POR PIN (novo — schema v2)
       Acesso rápido no POS sem digitar senha completa
    ========================= */
    public function loginPin(string $username, string $pin): array
    {
        try {

            $stmt = $this->pdo->prepare("
                SELECT id, uuid, nome, username, pin, nivel, ativo, deleted_at
                FROM usuarios
                WHERE username  = :username
                  AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([':username' => trim($username)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !$user['ativo']) {
                return ['success' => false, 'message' => 'Usuário não encontrado ou inactivo'];
            }

            if (empty($user['pin'])) {
                return ['success' => false, 'message' => 'PIN não configurado'];
            }

            // PIN guardado como hash (bcrypt)
            if (!password_verify($pin, $user['pin'])) {
                return ['success' => false, 'message' => 'PIN incorreto'];
            }

            /* =========================
                SESSÃO LOGIN PIN
                ========================= */

                session_regenerate_id(true);

                $nivel = strtolower(trim($user['nivel'] ?? 'caixa'));

                $_SESSION['usuario_id']    = $user['id'];
                $_SESSION['usuario_uuid']  = $user['uuid'];
                $_SESSION['usuario_nome']  = $user['nome'];
                $_SESSION['usuario_login'] = $user['username'];

                $_SESSION['nivel_acesso'] = $nivel;
                $_SESSION['nivel']        = $nivel;

                $_SESSION['logado'] = true;

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['ultimo_acesso'] = time();

            $this->pdo->prepare("
                UPDATE usuarios
                SET ultimo_acesso = datetime('now'),
                    updated_at    = datetime('now')
                WHERE id = ?
            ")->execute([$user['id']]);

            return [
                'success' => true,
                'message' => 'Login por PIN realizado',
                'usuario' => [
                    'id'    => $user['id'],
                    'nome'  => $user['nome'],
                    'nivel' => $_SESSION['nivel_acesso'],
                ],
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       LOGOUT
    ========================= */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /* =========================
       VERIFICAR LOGIN
    ========================= */
    public function verificar(): bool
    {
        return !empty($_SESSION['logado']) && !empty($_SESSION['usuario_id']);
    }

    /* =========================
       USUÁRIO ATUAL
    ========================= */
    public function usuario(): ?array
    {
        if (!$this->verificar()) {
            return null;
        }

        return [
            'id'    => $_SESSION['usuario_id'],
            'uuid'  => $_SESSION['usuario_uuid'] ?? null,
            'nome'  => $_SESSION['usuario_nome']  ?? '',
            'nivel' => $_SESSION['nivel_acesso']  ?? '',
        ];
    }

    /* =========================
       EXIGIR LOGIN
    ========================= */
    public function exigirLogin(): void
    {
        if (!$this->verificar()) {
            header("Location: ../auth/login.php");
            exit;
        }
    }

    /* =========================
       EXIGIR NÍVEL
    ========================= */
    public function exigirNivel(array $niveisPermitidos): void
    {
        $this->exigirLogin();

        $nivel = $_SESSION['nivel_acesso'] ?? '';

        if (!in_array($nivel, $niveisPermitidos, true)) {
            http_response_code(403);
            die("Acesso negado");
        }
    }

    /* =========================
       VALIDAR AUTORIZAÇÃO
       (ex: supervisor autoriza desconto no POS)

       SEGURANÇA: a versão antiga fazia SELECT * FROM usuarios
       e iterava todos os registos — muito inseguro.
       Agora busca por username e verifica hash.
    ========================= */
    public function validarAutorizacao(
        string $username,
        string $senha,
        array  $niveisPermitidos = ['admin', 'supervisor']
    ): array {

        try {

            $stmt = $this->pdo->prepare("
                SELECT id, nome, password, nivel, ativo, deleted_at
                FROM usuarios
                WHERE username   = :username
                  AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([':username' => trim($username)]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !$user['ativo']) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            if (!password_verify($senha, $user['password'] ?? '')) {
                return ['success' => false, 'message' => 'Senha inválida'];
            }

            $nivel = $user['nivel'] ?? '';

            if (!in_array($nivel, $niveisPermitidos, true)) {
                return ['success' => false, 'message' => 'Sem permissão'];
            }

            return [
                'success' => true,
                'usuario' => [
                    'id'    => $user['id'],
                    'nome'  => $user['nome'],
                    'nivel' => $nivel,
                ],
            ];

        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* =========================
       CSRF
    ========================= */
    public function validarCSRF(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function gerarCSRF(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}