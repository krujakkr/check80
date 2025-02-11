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

// จัดการการอัพเดทสถานะ
if (isset($_POST['update_status']) && isset($_POST['record_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE attendance_records SET status = 'อนุมัติแล้ว' WHERE id = ? AND status = 'รออนุมัติ'");
        $stmt->execute([$_POST['record_id']]);
        if($stmt->rowCount() > 0) {
            echo "success";
        } else {
            echo "error: No record updated";
        }
        exit();
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
        exit();
    }
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
    
    $stmt = $pdo->query("
        SELECT ar.*, s.fullname, s.class, s.number, 
               sub.subject_name, u.teacher_id,
               COALESCE(u.teacher_name, 'ไม่ระบุชื่อครู') as teacher_name
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.student_id
        JOIN subjects sub ON ar.subject_id = sub.subject_id
        JOIN users u ON ar.teacher_id = u.teacher_id
        ORDER BY ar.created_at DESC
    ");
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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


// การค้นหา
$where_conditions = array();
$params = array();

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(
        ar.student_id LIKE ? OR 
        s.fullname LIKE ? OR 
        u.teacher_id LIKE ? OR 
        u.teacher_name LIKE ? OR 
        sub.subject_id LIKE ? OR 
        sub.subject_name LIKE ? OR
        s.class LIKE ?
    )";
    // เพิ่ม parameters สำหรับเงื่อนไขการค้นหาที่เพิ่มขึ้น
    $params[] = $search_term; // student_id
    $params[] = $search_term; // fullname
    $params[] = $search_term; // teacher_id
    $params[] = $search_term; // teacher_name
    $params[] = $search_term; // subject_id
    $params[] = $search_term; // subject_name
    $params[] = $search_term; // class
}
    
    if (!empty($_GET['status'])) {
        $where_conditions[] = "ar.status = ?";
        $params[] = $_GET['status'];
    }
    
    
    // สร้าง WHERE clause
$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    

// Query ดึงข้อมูลครั้งเดียว
    $sql = "
        SELECT ar.*, s.fullname, s.class, s.number, 
               sub.subject_name, u.teacher_id,
               COALESCE(u.teacher_name, 'ไม่ระบุชื่อครู') as teacher_name
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.student_id
        JOIN subjects sub ON ar.subject_id = sub.subject_id
        JOIN users u ON ar.teacher_id = u.teacher_id
        $where_clause
        ORDER BY ar.created_at DESC
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        $records = [];
    }  
?>


<!DOCTYPE html>
<html>
<head>
    <title>รายงานการนักเรียนที่มีเวลาเรียนไม่ถึง 80%</title>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
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
        .ms { color: red; font-weight: bold; }
        .approved { color: green; font-weight: bold; }
        .waiting { color: orange; font-weight: bold; }
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
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #da190b;
        }
        .status-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            background-color: #ffc107;
            color: black;
        }
        .status-btn:hover {
            background-color: #ffb300;
        }
    </style>
</head>
<body>

<div class="header-section">
<h1>รายงานการเข้าเรียนทั้งหมด</h1>
<div>
    <form method="post" style="display: inline;">
        <button type="submit" name="export" class="export-btn">Export to CSV</button>
    </form>
    <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
</div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-search"></i> ค้นหาข้อมูล
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 2;">  <!-- เปลี่ยนจาก flex: 1 เป็น flex: 2 เพื่อให้ช่องค้นหากว้างขึ้น -->
                    <label for="search_term" class="form-label">คำค้นหา:</label>
                    <input type="text" class="form-control" id="search_term" name="search" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           placeholder="รหัสนักเรียน, ชื่อนักเรียน, รหัสครู, ชื่อครู, รหัสวิชา, ชื่อวิชา, ชั้น" 
                           style="width: 100%; padding: 8px;">  <!-- เพิ่ม padding เพื่อให้ช่องสูงขึ้น -->
                </div>
                <div style="flex: 1;">
                    <label for="status" class="form-label">สถานะ:</label>
                    <select class="form-select" id="status" name="status" style="width: 100%; padding: 8px;">
                        <option value="">ทั้งหมด</option>
                        <option value="รออนุมัติ" <?php echo (isset($_GET['status']) && $_GET['status'] == 'รออนุมัติ') ? 'selected' : ''; ?>>รออนุมัติ</option>
                        <option value="อนุมัติแล้ว" <?php echo (isset($_GET['status']) && $_GET['status'] == 'อนุมัติแล้ว') ? 'selected' : ''; ?>>อนุมัติแล้ว</option>
                        <option value="มส" <?php echo (isset($_GET['status']) && $_GET['status'] == 'มส') ? 'selected' : ''; ?>>มส</option>
                    </select>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                        ค้นหา
                    </button>
                    <a href="admin.php" class="btn btn-secondary" style="padding: 8px 16px;">
                        ล้างการค้นหา
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
    
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
                <a href="generate_pdf.php?id=<?= $record['id'] ?>" target="_blank">📄 PDF</a>
            </td>
            <td class="<?= $record['status'] == 'มส' ? 'ms' : ($record['status'] == 'รออนุมัติ' ? 'waiting' : 'approved') ?>">
    <?php if ($record['status'] == 'รออนุมัติ'): ?>
        <button onclick="updateStatus(<?= $record['id'] ?>)" class="status-btn">
            <?= $record['status'] ?>
        </button>
    <?php else: ?>
        <?= $record['status'] ?>
    <?php endif; ?>
</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <script>
function updateStatus(recordId) {
    if (confirm('ต้องการอนุมัติผลการเรียนหรือไม่?')) {
        $.post('admin.php', {
            update_status: true,
            record_id: recordId
        }, function(response) {
            if (response === 'success') {
                // อัพเดทการแสดงผลโดยตรง
                var button = $('button[onclick="updateStatus(' + recordId + ')"]');
                button.parent().html('<span class="approved">อนุมัติแล้ว</span>');
            } else {
                alert('เกิดข้อผิดพลาด: ' + response);
            }
        });
    }
}
</script>
</body>
</html>