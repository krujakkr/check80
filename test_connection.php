<?php
$host = 'localhost';
$dbname = 'knwacth_Check80';
$username = 'knwacth_Check80';
$password = 'Nb4z1k7?7';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "เชื่อมต่อฐานข้อมูลสำเร็จ";
    
    // ทดสอบดึงข้อมูล
    $stmt = $conn->query("SELECT * FROM users LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "การเชื่อมต่อล้มเหลว: " . $e->getMessage();
}