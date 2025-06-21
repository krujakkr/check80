<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}

// Include config file
require_once 'config.php';

// Get PDO connection using the function
$pdo = getPDO();

// กำหนดระดับชั้นแบบตายตัว
$grades = ['ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'];

// ดึงข้อมูลห้องเรียนตามระดับชั้นที่เลือก
$rooms = [];
if (!empty($_GET['grade'])) {
    $stmt = $pdo->prepare("SELECT DISTINCT SUBSTRING_INDEX(class, '/', -1) as room 
                          FROM students 
                          WHERE class LIKE ? 
                          ORDER BY CAST(SUBSTRING_INDEX(class, '/', -1) AS UNSIGNED)");
    $stmt->execute([$_GET['grade'] . '/%']);
    $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ดึงข้อมูลรายวิชาตามระดับชั้น
$subjects = [];
if (!empty($_GET['grade'])) {
    // แปลงจาก "ม.1" เป็น "1" สำหรับค้นหาในฐานข้อมูล
    $grade_level = str_replace('ม.', '', $_GET['grade']);
    $stmt = $pdo->prepare("SELECT DISTINCT subject_id, subject_name 
                          FROM subjects 
                          WHERE grade_level = ? 
                          ORDER BY subject_id");
    $stmt->execute([$grade_level]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // ถ้าไม่ได้เลือกระดับชั้น จะไม่แสดงรายวิชา
    $subjects = [];
}

// สร้าง WHERE clause สำหรับการกรอง
$where_conditions = [];
$params = [];

if (!empty($_GET['grade'])) {
    if (!empty($_GET['room'])) {
        // ถ้าเลือกทั้งระดับชั้นและห้อง
        $where_conditions[] = "s.class = ?";
        $params[] = $_GET['grade'] . '/' . $_GET['room'];
    } else {
        // ถ้าเลือกแค่ระดับชั้น
        $where_conditions[] = "s.class LIKE ?";
        $params[] = $_GET['grade'] . '/%';
    }
}

if (!empty($_GET['subject'])) {
    $where_conditions[] = "ar.subject_id = ?";
    $params[] = $_GET['subject'];
}

// สร้าง WHERE clause
$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Query หลักสำหรับดึงข้อมูล
$sql = "
    SELECT ar.*, s.fullname, s.class, s.number, 
           sub.subject_name, u.teacher_id,
           COALESCE(u.teacher_name, 'ไม่ระบุชื่อครู') as teacher_name
    FROM attendance_records ar
    JOIN students s ON ar.student_id = s.student_id
    JOIN subjects sub ON ar.subject_id = sub.subject_id
    JOIN users u ON ar.teacher_id = u.teacher_id
    $where_clause
    ORDER BY s.class, s.number, ar.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $records = [];
}

// Export to CSV
if(isset($_POST['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=รายงานการเข้าเรียน_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, [
        'วันที่', 'รหัสครู', 'ชื่อครู', 'รหัสวิชา', 'ชื่อวิชา', 
        'รหัสนักเรียน', 'ชื่อ-สกุล', 'ชั้น', 'เลขที่', 'ร้อยละเวลาเรียน', 'สถานะ'
    ]);
    
    foreach($records as $row) {
        fputcsv($output, [
            $row['created_at'], $row['teacher_id'], $row['teacher_name'],
            $row['subject_id'], $row['subject_name'], $row['student_id'],
            $row['fullname'], $row['class'], $row['number'],
            $row['attendance_percent'], $row['status']
        ]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>รายงานนักเรียนที่มีเวลาเรียนไม่ถึง 80% (สำหรับครูที่ปรึกษา)</title>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        .export-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
            font-size: 16px;
        }
        .export-btn:hover {
            background-color: #45a049;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background-color: #da190b;
        }
        .ms { color: red; font-weight: bold; }
        .approved { color: green; font-weight: bold; }
        .waiting { color: orange; font-weight: bold; }
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
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h2>รายงานนักเรียนที่มีเวลาเรียนไม่ถึง 80% (สำหรับครูที่ปรึกษา)</h2>
            <div class="nav-buttons">
                <a href="form.php" class="nav-btn">กลับหน้าหลัก</a>
                <form method="post" style="display: inline;">
                    <button type="submit" name="export" class="export-btn">Export CSV</button>
                </form>
                <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
            </div>
        </div>

        <form method="GET" action="" id="filterForm">
            <div class="filter-section">
                <div class="filter-group">
                    <label for="grade">ระดับชั้น:</label>
                    <select id="grade" name="grade">
                        <option value="">เลือกระดับชั้น</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade ?>" <?= isset($_GET['grade']) && $_GET['grade'] == $grade ? 'selected' : '' ?>>
                                <?= $grade ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="room">ห้อง:</label>
                    <select id="room" name="room">
                        <option value="">เลือกห้อง</option>
                        <?php if (!empty($rooms)): ?>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= $room ?>" <?= isset($_GET['room']) && $_GET['room'] == $room ? 'selected' : '' ?>>
                                    <?= $room ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="subject">รายวิชา:</label>
                    <select id="subject" name="subject">
                        <option value="">เลือกรายวิชา</option>
                        <?php if (!empty($subjects)): ?>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>" 
                                        <?= isset($_GET['subject']) && $_GET['subject'] == $subject['subject_id'] ? 'selected' : '' ?>>
                                    <?= $subject['subject_id'] ?> - <?= $subject['subject_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="export-btn" style="height: 40px;">กรองข้อมูล</button>
                <a href="advisor.php" class="clear-btn" style="height: 40px; text-decoration: none; padding: 10px 20px; margin-left: 10px; background-color: #6c757d; color: white; border-radius: 4px;">ล้างการกรอง</a>
            </div>
        </form>

        <table>
            <tr>
                <th>วันที่</th>
                <th>รหัสครู</th>
                <th>ชื่อครู</th>
                <th>รหัสวิชา</th>
                <th>ชื่อวิชา</th>
                <th>รหัสนักเรียน</th>
                <th>ชื่อ-สกุล</th>
                <th>ชั้น</th>
                <th>เลขที่</th>
                <th>ร้อยละเวลาเรียน</th>
                <th>PDF</th>
                <th>สถานะ</th>
            </tr>
            <?php foreach ($records as $record): ?>
            <tr>
                <td><?= $record['created_at'] ?></td>
                <td><?= $record['teacher_id'] ?></td>
                <td><?= $record['teacher_name'] ?></td>
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
                <td class="<?= $record['status'] == 'มส' ? 'ms' : ($record['status'] == 'รออนุมัติ' ? 'waiting' : 'approved') ?>">
                    <?= $record['status'] ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <script>
    $(document).ready(function() {
        // อัพเดทรายการห้องและรายวิชาเมื่อเลือกระดับชั้น
        $('#grade').change(function() {
            var grade = $(this).val();
            var roomSelect = $('#room');
            var subjectSelect = $('#subject');
            
            if (grade) {
                // ทำ AJAX request เพื่อดึงข้อมูลห้องและรายวิชา
                $.ajax({
                    url: 'get_filtered_data.php',
                    method: 'GET',
                    data: { grade: grade },
                    dataType: 'json',  // ระบุว่าต้องการข้อมูลแบบ JSON
                    success: function(data) {
                        // อัพเดทห้อง
                        var roomOptions = '<option value="">เลือกห้อง</option>';
                        if (data.rooms) {
                            data.rooms.forEach(function(room) {
                                roomOptions += '<option value="' + room + '">' + room + '</option>';
                            });
                        }
                        roomSelect.html(roomOptions);
                        roomSelect.prop('disabled', false);
                        
                        // อัพเดทรายวิชา
                        var subjectOptions = '<option value="">เลือกรายวิชา</option>';
                        if (data.subjects) {
                            data.subjects.forEach(function(subject) {
                                subjectOptions += '<option value="' + subject.subject_id + '">' + 
                                                subject.subject_id + ' - ' + subject.subject_name + '</option>';
                            });
                        }
                        subjectSelect.html(subjectOptions);
                        subjectSelect.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.log('Response:', xhr.responseText);
                    }
                });
            } else {
                // ถ้าไม่ได้เลือกระดับชั้น
                roomSelect.html('<option value="">เลือกห้อง</option>');
                roomSelect.prop('disabled', true);
                subjectSelect.html('<option value="">เลือกรายวิชา</option>');
                subjectSelect.prop('disabled', true);
            }
        });

        // ถ้ามีการเลือกระดับชั้นอยู่แล้ว ให้ทำการ trigger change event
        if ($('#grade').val()) {
            $('#grade').trigger('change');
        }
    });
    </script>
</body>
</html>