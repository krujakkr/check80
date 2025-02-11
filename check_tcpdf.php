<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$tcpdf_path = dirname(__FILE__) . '/tcpdf/tcpdf.php';
if (file_exists($tcpdf_path)) {
    echo "TCPDF file exists at: " . $tcpdf_path . "\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($tcpdf_path)), -4) . "\n";
    
    require_once($tcpdf_path);
    echo "TCPDF loaded successfully";
} else {
    echo "TCPDF not found at: " . $tcpdf_path;
    echo "\nCurrent directory: " . dirname(__FILE__);
}
?>