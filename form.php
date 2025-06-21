<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}


//กำหนดช่วงเวลาในการบันทึกข้อมูลได้ 
function isInSubmissionPeriod() {
    $start_date = strtotime('2025-02-01');
    $end_date = strtotime('2050-02-07 23:59:59');
    $current_date = time();
    
    return ($current_date >= $start_date && $current_date <= $end_date);
}

// Include config file
require_once 'config.php';

// Get PDO connection using the function
$pdo = getPDO();


// Add this code to fetch teacher name
$stmt = $pdo->prepare("SELECT teacher_name FROM users WHERE teacher_id = ?");
$stmt->execute([$_SESSION['teacher_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$teacher_name = $teacher ? $teacher['teacher_name'] : 'ไม่ระบุชื่อ';




if (isset($_POST['update_status']) && isset($_POST['record_id'])) {
    try {
        // อัพเดตสถานะของรายการที่มี ID ตรงกัน
        $stmt = $pdo->prepare("UPDATE attendance_records SET status = 'อนุมัติแล้ว' WHERE id = ?");
        $stmt->execute([$_POST['record_id']]);
        if($stmt->rowCount() > 0) {
            echo "success";
        } else {
            echo "error";
        }
        exit();
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
        exit();
    }
}



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



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    if (!isInSubmissionPeriod()) {
        $_SESSION['error_message'] = "ไม่อยู่ในช่วงเวลาส่ง มส";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    try {
        $pdo->beginTransaction();
        foreach ($_POST['student_id'] as $i => $student_id) {
            if (empty($student_id)) continue;
            
            $attendance = floatval($_POST['attendance'][$i]);
            if ($attendance < 60) {
                $status = 'มส';
            } elseif ($attendance < 80) {
                $status = 'รออนุมัติ';
            } else {
                $status = 'อนุมัติแล้ว';
            }
            
            $stmt = $pdo->prepare("INSERT INTO attendance_records (student_id, subject_id, attendance_percent, teacher_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $_POST['subject_id'], $attendance, $_SESSION['teacher_id'], $status]);
        }
        $pdo->commit();
        $_SESSION['success_message'] = "บันทึกข้อมูลสำเร็จ";
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}




if (isset($_POST['delete_record']) && isset($_POST['record_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM attendance_records WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$_POST['record_id'], $_SESSION['teacher_id']]);
        echo $stmt->rowCount() > 0 ? "success" : "error";
        exit();
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
        exit();
    }
}

// แสดงข้อความ success/error จาก session แทน
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// ลบข้อความออกจาก session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);


// ดึงข้อมูลสำหรับแสดงในตาราง
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
    <title>บันทึกข้อมูลนักเรียนที่มีเวลาเรียนไม่ถึง 80%</title>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .student-info { color: blue; margin-left: 10px; }
        .subject-info { color: green; margin-left: 10px; }
        .teacher-name {
            color: #333;
            font-size: 1.1em;
            margin-left: 15px;
            font-weight: normal;
        }

    </style>
</head>
<body>
<div class="header-section">
            <div class="title-section">
            <h2>บันทึกข้อมูลนักเรียน
                <span class="teacher-name">(<?php echo htmlspecialchars($teacher_name); ?>)</span>
            </h2>
            </div>
        <div class="nav-buttons">
            <a href="advisor.php" class="nav-btn">สำหรับครูที่ปรึกษา</a>
            <a href="report.php" class="nav-btn">รายงานสรุป มส</a>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
    </div>
    
    <?php 
    if (isset($success)) echo "<p style='color:green'>$success</p>";
    if (isset($error)) echo "<p style='color:red'>$error</p>";
    ?>



<?php if (!isInSubmissionPeriod()): ?>
    <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">
        <h4 style="margin-top: 0;">ไม่อยู่ในช่วงเวลาส่ง มส</h4>
        <p style="margin-bottom: 0;">----- ปฏิทินการดำเนินการ มส -----</p>
        <ul style="margin-top: 10px;">
            <li>11 ก.พ. 68 ประกาศรายชื่อนักเรียน มส</li>
            <li>11-14 ก.พ. 68 นักเรียนดำเนินการยื่นใบขอมีสิทธิ์สอบสำหรับนักเรียนที่มีเวลาเรียนไม่น้อยกว่า 60%</li>
            <li>11-19 ก.พ. 68 ครูมอบงาน/ดำเนินการ และบันทึก อนุมัติการมีสิทธิ์สอบ</li>
            <li>20 ก.พ. 68 ประกาศรายชื่อผู้มีสิทธ์สอบปลายภาค</li>
        </ul>
    </div>



<?php else: ?> 
    <form method="POST" onsubmit="return validateForm()">
        <div class="form-row">
            <label>รหัสวิชา:</label>
            <input type="text" name="subject_id" id="subject_id" required>
            <span id="subject_name" class="subject-info"></span>
        </div>
        
        <?php for ($i = 0; $i < 10; $i++): ?>
            <div class="form-row">
            <input type="text" 
                   name="student_id[]" 
                   class="student_id" 
                   maxlength="5" 
                   placeholder="รหัสนักเรียน"
                   pattern=".{5,5}"
                   title="รหัสนักเรียนต้องมี 5 หลัก">
            <span class="student_info"></span>
            <input type="number" 
                   name="attendance[]" 
                   class="attendance"
                   min="0" 
                   max="100" 
                   step="0.01" 
                   placeholder="ร้อยละเวลาเรียน"
                   title="กรุณากรอกร้อยละเวลาเรียน">
        </div>
        <?php endfor; ?>
        
        <button type="submit" name="submit">บันทึก</button>
    </form>
<?php endif; ?>

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
        <th>PDF</th>
        <th>สถานะ</th>
        <th>จัดการ</th>
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
        <td>
            <?php if ($record['status'] == 'มส'): ?>
                <span class="pdf-link disabled">📄 PDF</span>
            <?php else: ?>
                <a href="generate_pdf.php?id=<?= $record['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
    <?php endif; ?>
        </td>
        <td>
            <?php if ($record['status'] == 'รออนุมัติ'): ?>
                <button onclick="updateStatus(<?= $record['id'] ?>)" class="status-btn waiting">
            รออนุมัติ
        </button>
            <?php else: ?>
                <span class="<?= $record['status'] == 'มส' ? 'ms' : 'approved' ?>">
                    <?= $record['status'] ?>
                </span>
            <?php endif; ?>
        </td>
        <td>
            <button onclick="deleteRecord(<?= $record['id'] ?>)" class="delete-btn">
                ลบ
            </button>
        </td>


    </tr>
    <?php endforeach; ?>
</table>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
}

.logout-btn:hover {
    background-color: #da190b;
}

/* Form Styles */
form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-row {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    gap: 10px;
}

input[type="text"], 
input[type="number"] {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 5px rgba(76,175,80,0.2);
}

.student-info {
    color: #1976D2;
    margin-left: 10px;
    font-size: 14px;
}

.subject-info {
    color: #388E3C;
    margin-left: 10px;
    font-size: 14px;
}


.delete-btn {
    background-color: #ff4444;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
}

.delete-btn:hover {
    background-color: #cc0000;
}

button[type="submit"] {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

button[type="submit"]:hover {
    background-color: #45a049;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #4CAF50;
    color: white;
    font-weight: normal;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #f5f5f5;
}

/* Status Button Styles */
.status-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.status-btn.waiting {
    background-color: #FFC107;
    color: black;
}

.status-btn:hover {
    opacity: 0.9;
}

.ms {
    color: #D32F2F;
    font-weight: bold;
}

.approved {
    color: #388E3C;
    font-weight: bold;
}

/* Success/Error Messages */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #DFF0D8;
    border: 1px solid #D6E9C6;
    color: #3C763D;
}

.alert-error {
    background-color: #F2DEDE;
    border: 1px solid #EBCCD1;
    color: #A94442;
}

.alert-warning {
    margin-top: 20px;
    font-size: 16px;
    line-height: 1.5;
}
.alert-warning h4 {
    color: #856404;
    font-size: 20px;
    margin-bottom: 10px;
}
.alert-warning ul {
    padding-left: 20px;
}
.alert-warning li {
    margin-bottom: 5px;
}

/* PDF Link */
a[href*="generate_pdf"] {
    text-decoration: none;
    color: #1976D2;
}

a[href*="generate_pdf"]:hover {
    text-decoration: underline;
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

/* Responsive Design */
@media screen and (max-width: 768px) {
    .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .student-info, .subject-info {
        margin: 5px 0;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
}

.pdf-link {
    text-decoration: none;
    color: #1976D2;
}

.pdf-link:hover {
    text-decoration: underline;
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

<script>
function updateStatus(recordId) {
    if (confirm('ต้องการอนุมัติผลการเรียนหรือไม่?')) {
        $.post('form.php', {
            update_status: true,
            record_id: recordId
        }, function(response) {
            if (response === 'success') {
                // อัพเดทการแสดงผลโดยตรง ไม่ต้อง reload หน้า
                var buttonCell = $('button[onclick="updateStatus(' + recordId + ')"]').parent();
                buttonCell.html('<span class="approved">อนุมัติแล้ว</span>');
            } else {
                alert('เกิดข้อผิดพลาด: ' + response);
            }
        });
    }
}
</script>

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

<script>
function validateForm() {
    var students = document.getElementsByClassName('student_id');
    var attendances = document.getElementsByClassName('attendance');
    var isValid = true;
    var hasData = false;

    // ตรวจสอบว่ามีการกรอกข้อมูลอย่างน้อย 1 คน
    for(var i = 0; i < students.length; i++) {
        if(students[i].value !== '') {
            hasData = true;
            // ถ้ามีรหัสนักเรียน ต้องมีเวลาเรียนด้วย
            if(attendances[i].value === '') {
                alert('กรุณากรอกร้อยละเวลาเรียนของรหัสนักเรียน ' + students[i].value);
                attendances[i].focus();
                isValid = false;
                break;
            }
            // ตรวจสอบความยาวรหัสนักเรียน
            if(students[i].value.length !== 5) {
                alert('รหัสนักเรียนต้องมี 5 หลัก');
                students[i].focus();
                isValid = false;
                break;
            }
        } else if(attendances[i].value !== '') {
            // ถ้ามีเวลาเรียน ต้องมีรหัสนักเรียนด้วย
            alert('กรุณากรอกรหัสนักเรียน');
            students[i].focus();
            isValid = false;
            break;
        }
    }

    if(!hasData && isValid) {
        alert('กรุณากรอกข้อมูลอย่างน้อย 1 คน');
        return false;
    }

    return isValid;
}

function deleteRecord(recordId) {
    if (confirm('ต้องการลบข้อมูลนี้ใช่หรือไม่?')) {
        $.post('form.php', {
            delete_record: true,
            record_id: recordId
        }, function(response) {
            if (response === 'success') {
                // ลบแถวนั้นออกจากตารางโดยตรง
                $('button[onclick="deleteRecord(' + recordId + ')"]').closest('tr').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert('เกิดข้อผิดพลาด: ' + response);
            }
        });
    }
}



</script>

</body>
</html>