<?php
session_start();

// เช็คว่าใครเป็นคนล็อกเอาท์ (ครูหรือนักเรียน)
$is_student = isset($_SESSION['student_id']);

// ล้าง session ทั้งหมด
$_SESSION = array();
session_destroy();

// ส่งกลับไปหน้าล็อกอินตามประเภทผู้ใช้
if ($is_student) {
    header("Location: stdlogin.php?status=logout");
} else {
    header("Location: login.php?status=logout");
}
exit();
?>