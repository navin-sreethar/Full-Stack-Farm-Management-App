<?php
/**
 * Database Configuration — EXAMPLE FILE
 * ----------------------------------------
 * Copy this file to database.php and fill in your real credentials.
 * database.php is listed in .gitignore and will NOT be pushed to GitHub.
 *
 * Auto-detects local vs production environment.
 */

// Detect production environment
$isProduction = isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'yourdomain.com');

if ($isProduction) {
    return [
        'driver'    => 'mysql',
        'host'      => 'your-production-db-host.com',
        'port'      => '3306',
        'database'  => 'your_production_db_name',
        'username'  => 'your_production_db_user',
        'password'  => getenv('DB_PASSWORD') ?: (file_exists(__DIR__ . '/secrets.php') ? require(__DIR__ . '/secrets.php') : ''),
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ];
}

// Local development
return [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'database' => 'farm_manager',
    'username' => 'root',
    'password' => 'root',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
