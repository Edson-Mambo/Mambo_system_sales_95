<?php

$dbPath = __DIR__ . '/mambo_local.db';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

require 'criar_db.php';