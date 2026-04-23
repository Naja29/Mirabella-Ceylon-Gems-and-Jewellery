<?php

function log_activity(string $type, string $description): void {
    try {
        $db      = db();
        $adminId = $_SESSION['admin_id'] ?? null;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? null;
        $db->prepare(
            'INSERT INTO activity_log (admin_id, type, description, ip_address) VALUES (?, ?, ?, ?)'
        )->execute([$adminId, $type, $description, $ip]);
    } catch (Throwable $e) {
        // Never crash the page over a log failure
    }
}
