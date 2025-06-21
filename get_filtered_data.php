<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['teacher_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['grade'])) {
    die(json_encode(['error' => 'Grade is required']));
}

try {
    // Include config file
require_once 'config.php';

// Get PDO connection using the function
$pdo = getPDO();

    // ดึงข้อมูลห้อง
    $stmt = $pdo->prepare("SELECT DISTINCT SUBSTRING_INDEX(class, '/', -1) as room 
                          FROM students 
                          WHERE class LIKE ? 
                          ORDER BY CAST(SUBSTRING_INDEX(class, '/', -1) AS UNSIGNED)");
    $stmt->execute([$_GET['grade'] . '/%']);
    $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // ดึงข้อมูลรายวิชา
    $grade_level = $_GET['grade']; // ใช้ค่า ม.1 โดยตรง ไม่ต้องแปลง
    $stmt = $pdo->prepare("SELECT DISTINCT subject_id, subject_name 
                          FROM subjects 
                          WHERE grade_level = ? 
                          ORDER BY subject_id");
    $stmt->execute([$grade_level]);

    // Debug: แสดงค่า SQL และ parameters
    error_log("Grade Level: " . $grade_level);
    error_log("SQL: " . $stmt->queryString);
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลกลับในรูปแบบ JSON
    die(json_encode([
        'rooms' => $rooms,
        'subjects' => $subjects
    ], JSON_UNESCAPED_UNICODE));

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
}
?>