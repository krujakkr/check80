<?php
$config_path = dirname(__FILE__) . '/config.php';
if (file_exists($config_path)) {
    echo "config.php exists at: " . $config_path . "\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($config_path)), -4) . "\n";
} else {
    echo "config.php not found at: " . $config_path;
}