<?php

// 1. Ensure essential writable directories exist in /tmp for Vercel Serverless environment
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Setup SQLite database file in /tmp if using sqlite driver
if (getenv('DB_CONNECTION') === 'sqlite') {
    $sqlitePath = getenv('DB_DATABASE') ?: '/tmp/database.sqlite';
    if (!file_exists($sqlitePath)) {
        @touch($sqlitePath);
    }
}

// 3. Delegate request to Laravel public entrypoint
require __DIR__ . '/../public/index.php';
