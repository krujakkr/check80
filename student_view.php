<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['student_id'])) {
    header('Location: stdlogin.php');
    exit();
}

try {


    // Include config file
    require_once 'config.php';

    // Get PDO connection using the function
    $pdo = getPDO();

    // ดึงข้อมูลนักเรียน
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch();

    // ดึงข้อมูลการเข้าเรียน
    $stmt = $pdo->prepare("
    SELECT 
        ar.id,
        ar.created_at,
        ar.student_id,
        ar.subject_id,
        ar.attendance_percent,
        ar.status,
        s.fullname,
        s.class,
        s.number,
        sub.subject_name,
        u.teacher_name,
        u.mobile_phone,
        u.department
    FROM 
        attendance_records ar
    LEFT JOIN 
        students s ON ar.student_id = s.student_id
    LEFT JOIN 
        subjects sub ON ar.subject_id = sub.subject_id
    LEFT JOIN 
        users u ON ar.teacher_id = u.teacher_id
    WHERE 
        ar.student_id = ?
    ORDER BY 
        ar.created_at DESC
");
    $stmt->execute([$_SESSION['student_id']]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ข้อมูลการเรียน</title>
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
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
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
        tr:hover {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .ms { 
            color: #dc3545; 
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            background-color: #ffebee;
        }
        .waiting { 
            color: #ff9800;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            background-color: #fff3e0;
        }
        .approved { 
            color: #4caf50;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            background-color: #e8f5e9;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .pdf-link {
            color: #2196F3;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        .pdf-link:hover {
            background-color: #e3f2fd;
        }

        .pdf-link.disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }

        .pdf-link.disabled:hover {
            text-decoration: none;
        }


    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ระบบตรวจสอบเวลาเรียน</h1>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>

        <div class="student-info">
            <h3>ข้อมูลนักเรียน</h3>
            <p>ชื่อ-สกุล: <?= $student['fullname'] ?></p>
            <p>รหัสนักเรียน: <?= $student['student_id'] ?></p>
            <p>ชั้น: <?= $student['class'] ?> เลขที่: <?= $student['number'] ?></p>
        </div>

        <table>
            <tr>
                <th>วันที่</th>
                <th>รหัสวิชา</th>
                <th>วิชา</th>
                <th>ครูผู้สอน</th>
                <th>เบอร์โทรศัพท์</th>
                <th>ร้อยละเวลาเรียน</th>
                <th>สถานะ</th>
                <th>PDF</th>
            </tr>
            <?php foreach ($records as $record): ?>
            <tr>
                <td><?= $record['created_at'] != '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($record['created_at'])) : '-' ?></td>
                <td><?= $record['subject_id'] ?></td>
                <td><?= $record['subject_name'] ?></td>
                <td><?= $record['teacher_name'] ?></td>
                <td><?= $record['mobile_phone'] ?></td>  
                <td><?= number_format($record['attendance_percent'], 2) ?>%</td>
                <td><span class="<?= $record['status'] == 'มส' ? 'ms' : ($record['status'] == 'รออนุมัติ' ? 'waiting' : 'approved') ?>">
                    <?= $record['status'] ?>
                </span></td>
                <td>
                <?php if ($record['status'] == 'มส'): ?>
                    <span class="pdf-link disabled">📄 PDF</span>
                <?php else: ?>
                    <a href="generate_pdf.php?id=<?= $record['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
                <?php endif; ?>
            </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>