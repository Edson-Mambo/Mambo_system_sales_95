<?php
class Database {
    public static function conectar() {
        $host = 'localhost';
        $db   = 'mambo_system_95';
        $user = 'root';
        $pass = ''; // Se estiver usando XAMPP padrão, a senha geralmente é vazia
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }
}
