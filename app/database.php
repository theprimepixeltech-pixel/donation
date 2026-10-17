<?php
function storage_dir(): string { return getenv('HOPE_STORAGE') ?: dirname(__DIR__) . '/storage'; }
function db(): PDO {
    static $db;
    if (!$db) {
        $path = getenv('HOPE_DB') ?: storage_dir() . '/hope.sqlite';
        if (!is_dir(dirname($path))) mkdir(dirname($path),0700,true);
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;');
        $db->exec(file_get_contents(__DIR__ . '/schema.sql'));
    }
    return $db;
}
function query(string $sql, array $params = []): PDOStatement { $q = db()->prepare($sql); $q->execute($params); return $q; }
function setting(string $key, string $default = ''): string { return query('SELECT value FROM settings WHERE name=?', [$key])->fetchColumn() ?: $default; }
function audit(string $action, string $detail): void { query('INSERT INTO audit_log(user_id,action,detail) VALUES(?,?,?)', [current_user()['id'] ?? null,$action,$detail]); }
