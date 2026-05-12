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
     * MYSQL REMOTO (SYNC FUTURO)
     * =========================
     */
    public static function conectarRemoto(): PDO
    {
        $host = "localhost";
        $dbname = "mambo_system";
        $user = "root";
        $pass = "";

        return new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }
}