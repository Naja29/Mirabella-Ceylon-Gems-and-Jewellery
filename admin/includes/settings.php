<?php

function get_setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $rows  = db()->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void {
    db()->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)")
       ->execute([$key, $value]);
}

function is_maintenance(): bool {
    return get_setting('maintenance_mode', '0') === '1';
}
