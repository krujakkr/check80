<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}

// Include config file
require_once 'config.php';

// Get PDO connection using the function
$pdo = getPDO();

try {
    // จำนวนทั้งหมดในระบบ
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance_records");
    $total = $stmt->fetchColumn();

    // เตรียมข้อมูลสำหรับรายงาน
    $data = [];
    

    // 1. ครูที่ส่ง มส
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ar.teacher_id) as teacher_count FROM attendance_records ar");
    $submitting_teachers = $stmt->fetchColumn();
    
    // จำนวนครูทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_teachers = $stmt->fetchColumn();

    $data[] = [
        'รายการ' => 'ครูที่ส่ง มส',
        'จำนวน' => $submitting_teachers,
        'ร้อยละ' => $total_teachers > 0 ? number_format(($submitting_teachers / $total_teachers) * 100, 2) : 0
    ];

    // รายวิชาที่ส่ง มส
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ar.subject_id) as subject_count FROM attendance_records ar");
    $submitted_subjects = $stmt->fetchColumn();
    
    // จำนวนวิชาทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM subjects");
    $total_subjects = $stmt->fetchColumn();

    $data[] = [
        'รายการ' => 'รายวิชาที่ส่ง มส',
        'จำนวน' => $submitted_subjects,
        'ร้อยละ' => $total_subjects > 0 ? number_format(($submitted_subjects / $total_subjects) * 100, 2) : 0
    ];

    // 4. รายการ มส ทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance_records");
    $count = $stmt->fetchColumn();
    $data[] = [
        'รายการ' => 'รายการ มส ทั้งหมด(นร.หนึ่งคนอาจติด มส หลายรายการ)',
        'จำนวน' => $count,
        'ร้อยละ' => 'ไม่คำนวณ'
    ];


 // 5. นักเรียนที่ติด มส
 $stmt = $pdo->query("SELECT COUNT(DISTINCT student_id) as count FROM attendance_records");
 $total_students_ms = $stmt->fetchColumn();
 $data[] = [
     'รายการ' => 'นักเรียนที่ติด มส',
     'จำนวน' => $total_students_ms,
     'ร้อยละ' => 'ไม่คำนวณ'
 ];

 // 6-11. นักเรียนแต่ละระดับชั้น
 foreach(['ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'] as $level) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ar.student_id) as count 
        FROM attendance_records ar 
        JOIN students s ON ar.student_id = s.student_id 
        WHERE s.class LIKE ?
    ");
    $stmt->execute([$level . '%']);
    $count = $stmt->fetchColumn();
    $data[] = [
        'รายการ' => 'นักเรียน ' . $level,
        'จำนวน' => $count,
        'ร้อยละ' => $total_students_ms > 0 ? 
                   number_format(($count / $total_students_ms) * 100, 2) : 0
    ];
}

    // 12. นักเรียนที่มีสิทธิ์ยื่นคำร้อง
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance_records WHERE status = 'รออนุมัติ'");
    $count = $stmt->fetchColumn();
    $data[] = [
        'รายการ' => 'รายการที่มีสิทธิ์ยื่นคำร้อง (>=60%)',
        'จำนวน' => $count,
        'ร้อยละ' => $total > 0 ? number_format(($count / $total) * 100, 2) : 0
    ];

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>รายงานสรุป มส</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .number-column {
            text-align: right;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background-color: #da190b;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-btn {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-btn:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="header">
            <h1>รายงานสรุป มส</h1>
            <div class="nav-buttons">
                <a href="form.php" class="nav-btn">กลับหน้าหลัก</a>
                <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
            </div>
        </div>

        <table>
            <tr>
                <th>รายการ</th>
                <th style="width: 150px;">จำนวน</th>
                <th style="width: 150px;">ร้อยละ</th>
            </tr>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= $row['รายการ'] ?></td>
                <td class="number-column"><?= number_format($row['จำนวน']) ?></td>
                <td class="number-column"><?= $row['ร้อยละ'] ?>%</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4CAF50; font-size: 0.9em;">
            <strong>หมายเหตุ:</strong>
            <ol style="margin-left: 20px; line-height: 1.6;">
                <li>ค่าร้อยละของ ครูที่ส่ง มส คิดจากจำนวนครูที่สอนทั้งหมด</li>
                <li>ค่าร้อยละของ รายวิชาที่ส่ง มส คิดจากจำนวนวิชาในระบบทั้งหมดของภาคเรียนนั้น</li>
                <li>รายการ มส ทั้งหมด คือ จำนวนรายการที่ครูบันทึกมาในระบบ</li>
                <li>นักเรียนที่ติด มส คือ จำนวน นร. ที่ไม่นับซ้ำใน รายการ มส ทั้งหมด</li>
                <li>ค่าร้อยละของ นร. ม.1-6 จะคิดจาก จำนวนนักเรียนที่ติด มส</li>
                <li>ค่าร้อยละของ รายการที่มีสิทธิ์ยื่นคำร้อง (>=60%) คิดจาก จำนวนรายการ มส ทั้งหมด</li>
            </ol>
        </div>
    </div>
</body>
</html>