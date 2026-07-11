<?php

require_once __DIR__ . '/../config/database.php';

class SyncService
{
    /* =========================
       CONEXÕES
    ========================= */

    private static function serverDB(): PDO
    {
        $pdo = Database::conectar(); // MySQL servidor
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private static function localDB(): PDO
    {
        $dbPath = __DIR__ . '/../localdb/mambo_local.db';

        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /* =========================
       INICIALIZAÇÃO LOCAL
    ========================= */

    public static function initLocal()
    {
        $db = self::localDB();

        // Tabela de sync
        $db->exec("
            CREATE TABLE IF NOT EXISTS sync_meta (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE,
                value TEXT
            );
        ");

        // Fila de operações offline
        $db->exec("
            CREATE TABLE IF NOT EXISTS sync_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name TEXT NOT NULL,
                record_id INTEGER NOT NULL,
                operation TEXT NOT NULL,
                payload TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                synced INTEGER DEFAULT 0
            );
        ");

        // Controle de sincronização
        $stmt = $db->prepare("INSERT OR IGNORE INTO sync_meta (key, value) VALUES ('last_sync', '0')");
        $stmt->execute();
    }

    /* =========================
       REGISTRA OPERAÇÕES OFFLINE
    ========================= */

    public static function queueOperation($table, $recordId, $operation, $payload)
    {
        $db = self::localDB();

        $stmt = $db->prepare("
            INSERT INTO sync_queue (table_name, record_id, operation, payload)
            VALUES (:table, :record, :operation, :payload)
        ");

        $stmt->execute([
            ':table' => $table,
            ':record' => $recordId,
            ':operation' => $operation,
            ':payload' => json_encode($payload)
        ]);
    }

    /* =========================
       SYNC LOCAL -> SERVER
    ========================= */

    public static function pushToServer()
    {
        $local = self::localDB();
        $server = self::serverDB();

        $stmt = $local->prepare("
            SELECT * FROM sync_queue
            WHERE synced = 0
            ORDER BY id ASC
        ");
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $server->beginTransaction();

        try {
            foreach ($items as $item) {

                $payload = json_decode($item['payload'], true);

                if ($item['operation'] === 'INSERT') {
                    self::insertRemote($server, $item['table_name'], $payload);
                }

                if ($item['operation'] === 'UPDATE') {
                    self::updateRemote($server, $item['table_name'], $item['record_id'], $payload);
                }

                if ($item['operation'] === 'DELETE') {
                    self::deleteRemote($server, $item['table_name'], $item['record_id']);
                }

                // marca como sincronizado
                $update = $local->prepare("UPDATE sync_queue SET synced = 1 WHERE id = ?");
                $update->execute([$item['id']]);
            }

            $server->commit();

        } catch (Exception $e) {
            $server->rollBack();
            throw $e;
        }
    }

    /* =========================
       SYNC SERVER -> LOCAL
    ========================= */

    public static function pullFromServer($table)
    {
        $local = self::localDB();
        $server = self::serverDB();

        // last sync timestamp
        $stmt = $local->prepare("SELECT value FROM sync_meta WHERE key = 'last_sync'");
        $stmt->execute();
        $lastSync = $stmt->fetchColumn();

        $query = "SELECT * FROM {$table} WHERE updated_at > :last_sync";
        $stmt = $server->prepare($query);
        $stmt->execute([':last_sync' => $lastSync]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $local->beginTransaction();

        try {
            foreach ($rows as $row) {

                $columns = array_keys($row);
                $fields = implode(',', $columns);
                $placeholders = ':' . implode(',:', $columns);

                $sql = "INSERT OR REPLACE INTO {$table} ($fields)
                        VALUES ($placeholders)";

                $stmtLocal = $local->prepare($sql);
                $stmtLocal->execute($row);
            }

            // atualiza timestamp
            $now = date('Y-m-d H:i:s');
            $upd = $local->prepare("
                UPDATE sync_meta SET value = :v WHERE key = 'last_sync'
            ");
            $upd->execute([':v' => $now]);

            $local->commit();

        } catch (Exception $e) {
            $local->rollBack();
            throw $e;
        }
    }

    /* =========================
       HELPERS SERVER
    ========================= */

    private static function insertRemote(PDO $db, $table, $data)
    {
        $fields = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));

        $sql = "INSERT INTO {$table} ($fields) VALUES ($values)";
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
    }

    private static function updateRemote(PDO $db, $table, $id, $data)
    {
        $set = [];

        foreach ($data as $key => $val) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$table} SET " . implode(',', $set) . " WHERE id = :id";

        $data['id'] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($data);
    }

    private static function deleteRemote(PDO $db, $table, $id)
    {
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
    }
}