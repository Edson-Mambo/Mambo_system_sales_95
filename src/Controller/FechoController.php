<?php
namespace Controller;

use PDO;
use DateTime;

class FechoController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function realizarFecho(): void
    {
        
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        if (!$usuarioId) {
            echo "Acesso negado.";
            return;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Obter resumo do dia
            $stmtResumo = $this->pdo->query("SELECT COUNT(*) as total_transacoes, SUM(total) as total_vendas FROM vendas WHERE DATE(data_venda) = CURDATE()");
            $resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC);

            // 2. Registrar fecho
            $stmtFecho = $this->pdo->prepare("INSERT INTO fechos_dia (usuario_id, total_vendas, total_transacoes, observacoes) VALUES (?, ?, ?, ?)");
            $stmtFecho->execute([
                $usuarioId,
                $resumo['total_vendas'] ?? 0,
                $resumo['total_transacoes'] ?? 0,
                'Fecho automático do dia'
            ]);

            // 3. Encerrar sessões (opcional: armazenar uma flag de fecho no banco)
            $this->pdo->exec("UPDATE logs_login SET logout_time = NOW() WHERE logout_time IS NULL");

            // 4. Forçar logout geral (limpar sessões ativas, se armazenadas em arquivos)
            session_destroy();

            $this->pdo->commit();

            echo "Fecho do dia concluído com sucesso.";
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "Erro no fecho do dia: " . $e->getMessage();
        }
    }
}
