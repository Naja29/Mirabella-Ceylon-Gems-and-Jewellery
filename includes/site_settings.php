<?php
/**
 * Loads site-wide settings from the `settings` DB table.
 * Returns an empty string default so callers can use empty() checks.
 */
function get_site_setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            require_once __DIR__ . '/../admin/includes/db.php';
            $rows  = db()->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}
