<?php

class SyncHelper
{
    public static function localDB()
    {
        return new PDO("sqlite:" . __DIR__ . "/../../localdb/mambo_local.db");
    }

    public static function serverDB()
    {
        require_once __DIR__ . '/../../config/database.php';
        return Database::conectar();
    }

    public static function unsynced($table)
    {
        $db = self::localDB();

        $stmt = $db->query("SELECT * FROM {$table} WHERE sync = 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markSynced($table, $id)
    {
        $db = self::localDB();

        $stmt = $db->prepare("UPDATE {$table} SET sync = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
}