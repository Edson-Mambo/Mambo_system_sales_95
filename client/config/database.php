<?php

class Database
{
    private static $local = null;

    /**
     * =========================
     * SQLITE LOCAL (OFFLINE)
     * =========================
     */
    public static function conectarLocal(): PDO
    {
        if (self::$local === null) {

            $dbPath = __DIR__ . '/../localdb/mambo_local.db';

            self::$local = new PDO("sqlite:" . $dbPath);
            self::$local->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$local;
    }

    /**
 * =========================
 * MYSQL REMOTO (CONFIG / SYNC)
 * =========================
 */
public static function conectarRemoto(): PDO
{
    $host = "localhost";

    // ALTERAR PARA O NOME REAL DA SUA BASE
    $dbname = "mambo_system_95";

    $user = "root";
    $pass = "";

    return new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]
    );
}
}