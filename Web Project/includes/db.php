<?php
/**
 * MySQLi database connection.
 * The connection is exposed as $conn and reused by every page.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Database unavailable · <?= SITE_NAME ?></title>
        <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <div class="container py-5" style="max-width: 640px">
            <div class="alert alert-danger shadow-sm">
                <h1 class="h4 alert-heading">We can't reach the database</h1>
                <p class="mb-2">Start MySQL in XAMPP, then import <code>database/threadline.sql</code>.</p>
                <p class="small mb-0 text-muted"><?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
