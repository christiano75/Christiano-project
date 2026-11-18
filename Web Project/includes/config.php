<?php
/**
 * Threadline — global configuration
 * ------------------------------------------------------------------
 * Values can be overridden with environment variables so the same
 * code can run on XAMPP, a live host or a local test server.
 */

declare(strict_types=1);

define('SITE_NAME', 'Threadline');
define('SITE_TAGLINE', 'Feel-good clothing, made to last.');

define('DB_HOST', getenv('THREADLINE_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('THREADLINE_DB_PORT') ?: 3307));
define('DB_NAME', getenv('THREADLINE_DB_NAME') ?: 'threadline');
define('DB_USER', getenv('THREADLINE_DB_USER') ?: 'root');
define('DB_PASS', getenv('THREADLINE_DB_PASS') ?: '');

define('CURRENCY', '$');
define('FREE_SHIPPING_THRESHOLD', 100.00);
define('SHIPPING_FLAT_RATE', 7.95);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
mb_internal_encoding('UTF-8');
