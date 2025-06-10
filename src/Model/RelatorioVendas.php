<?php
namespace Src\Model;

class RelatorioVendas {
    private $conn;
    private $tabela = "relatorio_vendas";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function gerarRelatorio($data_inicio, $data_fim) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabela} WHERE data_venda BETWEEN ? AND ?");
        $stmt->execute([$data_inicio, $data_fim]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
