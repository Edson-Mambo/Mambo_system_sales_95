<?php
namespace Src\Model;

class Produto
{
    private $id;
    private $codigoBarra;
    private $nome;
    private $preco;
    private $estoque;

    public function __construct($id, $codigoBarra, $nome, $preco, $estoque)
    {
        $this->id = $id;
        $this->codigoBarra = $codigoBarra;
        $this->nome = $nome;
        $this->preco = $preco;
        $this->estoque = $estoque;
    }

    public function getId() { return $this->id; }
    public function getCodigoBarra() { return $this->codigoBarra; }
    public function getNome() { return $this->nome; }
    public function getPreco() { return $this->preco; }
    public function getEstoque() { return $this->estoque; }
}

