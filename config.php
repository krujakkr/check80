<?php
// Database Configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'Check80',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8'
];

// Function to get PDO connection
function getPDO() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Alternative: Direct variables (if you prefer this approach)
define('DB_HOST', $db_config['host']);
define('DB_NAME', $db_config['dbname']);
define('DB_USER', $db_config['username']);
define('DB_PASS', $db_config['password']);
define('DB_CHARSET', $db_config['charset']);


// Admin Configuration
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin12345678');
?>