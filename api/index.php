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

// 2. Setup SQLite database file in /tmp with seeded data
$tmpDb = '/tmp/database.sqlite';
$templateDb = __DIR__ . '/../database/database.sqlite';

if (!file_exists($tmpDb) && file_exists($templateDb)) {
    @copy($templateDb, $tmpDb);
} elseif (!file_exists($tmpDb)) {
    @touch($tmpDb);
}

// 3. Force HTTPS environment flags for Vercel edge/proxy routing
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

// 4. Delegate request to Laravel public entrypoint
require __DIR__ . '/../public/index.php';
