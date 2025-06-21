<?php
session_start();

// ลบ session ของ admin
unset($_SESSION['admin_logged_in']);
session_destroy();

// ส่งกลับไปหน้าล็อกอิน admin
header("Location: admin_login.php?status=logout");
exit();
?>