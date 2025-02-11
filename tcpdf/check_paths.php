<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";

// ทดสอบ path ต่างๆ
$paths = [
    '../library/tcpdf.php',
    './library/tcpdf.php',
    '/library/tcpdf.php',
    'library/tcpdf.php',
    $_SERVER['DOCUMENT_ROOT'] . '/library/tcpdf.php',
];

foreach ($paths as $path) {
    echo "\nTesting path: " . $path . "\n";
    echo "File exists: " . (file_exists($path) ? "Yes" : "No") . "\n";
    if (file_exists($path)) {
        echo "File permissions: " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
    }
}
echo "</pre>";
?>