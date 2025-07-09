<?php
// src/Logger.php

class Logger
{
    private static $instance = null;
    private $pdo;
    private $user_id;
    private $user_nome;

    private function __construct($pdo)
    {
        $this->pdo = $pdo;

        $this->user_id = $_SESSION['usuario_id'] ?? null;
        $this->user_nome = $_SESSION['usuario_nome'] ?? 'Desconhecido';

        // Log básico de requisição
        $this->logRequest();

        // Registra handlers para capturar tudo
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public static function init($pdo)
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
    }

    private function log($tipo, $descricao)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
        $rota = $_SERVER['REQUEST_URI'] ?? 'Desconhecido';
        $dataHora = (new DateTime('now', new DateTimeZone('Africa/Maputo')))->format('Y-m-d H:i:s');

        $sql = "INSERT INTO logs_sistema 
            (usuario_id, usuario_nome, tipo_log, descricao, ip_usuario, user_agent, rota, data_hora)
            VALUES (:usuario_id, :usuario_nome, :tipo_log, :descricao, :ip_usuario, :user_agent, :rota, :data_hora)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'   => $this->user_id,
            ':usuario_nome' => $this->user_nome,
            ':tipo_log'     => $tipo,
            ':descricao'    => $descricao,
            ':ip_usuario'   => $ip,
            ':user_agent'   => $userAgent,
            ':rota'         => $rota,
            ':data_hora'    => $dataHora
        ]);
    }

    private function logRequest()
    {
        $this->log('INFO', 'Requisição iniciada');
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $mensagem = "Erro [$errno]: $errstr em $errfile:$errline";
        $this->log('ERROR', $mensagem);
    }

    public function handleException($exception)
    {
        $mensagem = "Exceção não tratada: " . $exception->getMessage();
        $this->log('ERROR', $mensagem);
    }

    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error !== null) {
            $mensagem = "Shutdown com erro fatal: {$error['message']} em {$error['file']}:{$error['line']}";
            $this->log('ERROR', $mensagem);
        } else {
            $this->log('INFO', 'Requisição finalizada com sucesso');
        }
    }

    public static function logCustom($tipo, $descricao)
    {
        if (self::$instance) {
            self::$instance->log($tipo, $descricao);
        }
    }
}
