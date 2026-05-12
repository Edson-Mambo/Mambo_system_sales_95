<?php

namespace Services;

use PDO;
use Exception;

require_once __DIR__ . '/../config/database.php';

class AuthService
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
       LOGIN
    ========================= */
    public function login(string $usuario, string $senha): array
    {
        try {

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM usuarios
                WHERE usuario = :usuario
                LIMIT 1
            ");

            $stmt->execute([
                ':usuario' => trim($usuario)
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }

            /* =========================
               VALIDAR SENHA
            ========================= */

            $senhaValida = false;

            // password_hash
            if (!empty($user['senha']) && password_verify($senha, $user['senha'])) {
                $senhaValida = true;
            }

            // fallback senha simples
            if (!$senhaValida && $senha === $user['senha']) {
                $senhaValida = true;
            }

            if (!$senhaValida) {

                return [
                    'success' => false,
                    'message' => 'Senha incorreta'
                ];
            }

            /* =========================
               SESSÃO
            ========================= */

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'] ?? $user['usuario'];
            $_SESSION['usuario_login'] = $user['usuario'];

            $_SESSION['nivel_acesso'] =
                $user['nivel_acesso']
                ?? $user['nivel']
                ?? 'caixa';

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $_SESSION['logado'] = true;

            /* =========================
               ÚLTIMO LOGIN
            ========================= */

            try {

                $update = $this->pdo->prepare("
                    UPDATE usuarios
                    SET ultimo_login = datetime('now')
                    WHERE id = ?
                ");

                $update->execute([$user['id']]);

            } catch (Exception $e) {
                // ignora
            }

            return [
                'success' => true,
                'message' => 'Login realizado',
                'usuario' => [
                    'id' => $user['id'],
                    'nome' => $_SESSION['usuario_nome'],
                    'nivel' => $_SESSION['nivel_acesso']
                ]
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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
                session_name(),
                '',
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
        return isset($_SESSION['usuario_id']);
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
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'] ?? '',
            'nivel' => $_SESSION['nivel_acesso'] ?? ''
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

        if (!in_array($nivel, $niveisPermitidos)) {

            http_response_code(403);

            die("Acesso negado");
        }
    }

    /* =========================
       VALIDAR AUTORIZAÇÃO
    ========================= */
    public function validarAutorizacao(
        string $senha,
        array $niveisPermitidos = ['admin', 'gerente', 'supervisor']
    ): array {

        try {

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM usuarios
                WHERE senha = ?
                LIMIT 1
            ");

            $stmt->execute([$senha]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $stmt = $this->pdo->query("
                    SELECT *
                    FROM usuarios
                ");

                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($usuarios as $u) {

                    if (
                        !empty($u['senha']) &&
                        password_verify($senha, $u['senha'])
                    ) {
                        $user = $u;
                        break;
                    }
                }
            }

            if (!$user) {

                return [
                    'success' => false,
                    'message' => 'Senha inválida'
                ];
            }

            $nivel =
                $user['nivel_acesso']
                ?? $user['nivel']
                ?? '';

            if (!in_array($nivel, $niveisPermitidos)) {

                return [
                    'success' => false,
                    'message' => 'Sem permissão'
                ];
            }

            return [
                'success' => true,
                'usuario' => [
                    'id' => $user['id'],
                    'nome' => $user['nome'] ?? '',
                    'nivel' => $nivel
                ]
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* =========================
       VALIDAR TOKEN CSRF
    ========================= */
    public function validarCSRF(?string $token): bool
    {
        if (
            empty($_SESSION['csrf_token']) ||
            empty($token)
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
    }

    /* =========================
       GERAR TOKEN
    ========================= */
    public function gerarCSRF(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }
}