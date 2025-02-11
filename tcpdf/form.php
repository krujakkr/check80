<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=knwacth_Check80;charset=utf8", "knwacth_Check80", "Nb4z1k7?7");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// AJAX endpoint for student info
if (isset($_POST['get_student'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_POST['student_id']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit();
}

// AJAX endpoint for subject info
if (isset($_POST['get_subject'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->execute([$_POST['subject_id']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['student_id'] as $i => $student_id) {
            if (empty($student_id)) continue;
            
            $stmt = $pdo->prepare("INSERT INTO attendance_records (student_id, subject_id, attendance_percent, teacher_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $_POST['subject_id'], $_POST['attendance'][$i], $_SESSION['teacher_id']]);
        }
        $pdo->commit();
        $success = "บันทึกข้อมูลสำเร็จ";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get records
$stmt = $pdo->prepare("
    SELECT ar.*, s.fullname, s.class, s.number, sub.subject_name 
    FROM attendance_records ar
    JOIN students s ON ar.student_id = s.student_id
    JOIN subjects sub ON ar.subject_id = sub.subject_id
    WHERE ar.teacher_id = ?
    ORDER BY ar.created_at DESC
");
$stmt->execute([$_SESSION['teacher_id']]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>บันทึกข้อมูลนักเรียน</title>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .student-info { color: blue; margin-left: 10px; }
        .subject-info { color: green; margin-left: 10px; }
    </style>
</head>
<body>
    <h2>บันทึกข้อมูลนักเรียน</h2>
    <a href="logout.php">ออกจากระบบ</a>
    
    <?php 
    if (isset($success)) echo "<p style='color:green'>$success</p>";
    if (isset($error)) echo "<p style='color:red'>$error</p>";
    ?>
    
    <form method="POST">
        <div>
            <label>รหัสวิชา:</label>
            <input type="text" name="subject_id" id="subject_id" required>
            <span id="subject_name" class="subject-info"></span>
        </div>
        
        <?php for ($i = 0; $i < 10; $i++): ?>
        <div style="margin: 10px 0;">
            <input type="text" name="student_id[]" class="student_id" maxlength="5" placeholder="รหัสนักเรียน">
            <span class="student_info"></span>
            <input type="number" name="attendance[]" min="0" max="100" step="0.01" placeholder="ร้อยละเวลาเรียน">
        </div>
        <?php endfor; ?>
        
        <button type="submit" name="submit">บันทึก</button>
    </form>

    <h3>รายการที่บันทึก</h3>
    <table border="1" style="width: 100%; margin-top: 20px;">
        <tr>
            <th>วันที่</th>
            <th>รหัสวิชา</th>
            <th>ชื่อวิชา</th>
            <th>รหัสนักเรียน</th>
            <th>ชื่อ-สกุล</th>
            <th>ชั้น</th>
            <th>เลขที่</th>
            <th>ร้อยละเวลาเรียน</th>
        </tr>
        <?php foreach ($records as $record): ?>
        <tr>
            <td><?= $record['created_at'] ?></td>
            <td><?= $record['subject_id'] ?></td>
            <td><?= $record['subject_name'] ?></td>
            <td><?= $record['student_id'] ?></td>
            <td><?= $record['fullname'] ?></td>
            <td><?= $record['class'] ?></td>
            <td><?= $record['number'] ?></td>
            <td><?= $record['attendance_percent'] ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <script>
    $(document).ready(function() {
        $('.student_id').on('change', function() {
            var input = $(this);
            var student_id = input.val();
            if (student_id.length == 5) {
                $.post('form.php', {
                    get_student: true,
                    student_id: student_id
                }, function(data) {
                    var student = JSON.parse(data);
                    if (student) {
                        input.siblings('.student_info').html(
                            student.fullname + ' ชั้น ' + student.class + ' เลขที่ ' + student.number
                        );
                    } else {
                        input.siblings('.student_info').html('ไม่พบข้อมูล');
                    }
                });
            }
        });
        
        $('#subject_id').on('change', function() {
            var subject_id = $(this).val();
            $.post('form.php', {
                get_subject: true,
                subject_id: subject_id
            }, function(data) {
                var subject = JSON.parse(data);
                if (subject) {
                    $('#subject_name').html(subject.subject_name);
                } else {
                    $('#subject_name').html('ไม่พบข้อมูล');
                }
            });
        });
    });
    </script>
</body>
</html>