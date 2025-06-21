<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

require_once 'config.php';

$message = '';
$message_type = '';

// จัดการการอัพโหลดไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $table = $_POST['table'];
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
        $file_type = $file['type'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'csv' || in_array($file_type, $allowed_types)) {
            $csv_content = file_get_contents($file['tmp_name']);
            $lines = str_getcsv($csv_content, "\n");
            
            try {
                $pdo = getPDO();
                $pdo->beginTransaction();
                
                $success_count = 0;
                $error_count = 0;
                $errors = [];
                
                foreach ($lines as $line_num => $line) {
                    if (empty(trim($line))) continue;
                    
                    $data = str_getcsv($line);
                    
                    // ข้าม header row
                    if ($line_num == 0) continue;
                    
                    try {
                        switch ($table) {
                            case 'students':
                                if (count($data) >= 5) {
                                    $stmt = $pdo->prepare("INSERT INTO students (student_id, fullname, class, number, id_card) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE fullname=VALUES(fullname), class=VALUES(class), number=VALUES(number), id_card=VALUES(id_card)");
                                    $stmt->execute([$data[0], $data[1], $data[2], $data[3], $data[4]]);
                                    $success_count++;
                                } else {
                                    $errors[] = "แถว " . ($line_num + 1) . ": ข้อมูลไม่ครบ";
                                    $error_count++;
                                }
                                break;
                                
                            case 'subjects':
                                if (count($data) >= 3) {
                                    $stmt = $pdo->prepare("INSERT INTO subjects (subject_id, subject_name, grade_level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE subject_name=VALUES(subject_name), grade_level=VALUES(grade_level)");
                                    $stmt->execute([$data[0], $data[1], $data[2]]);
                                    $success_count++;
                                } else {
                                    $errors[] = "แถว " . ($line_num + 1) . ": ข้อมูลไม่ครบ";
                                    $error_count++;
                                }
                                break;
                                
                            case 'users':
                                if (count($data) >= 6) {
                                    // ตรวจสอบและแปลงวันที่ถ้าจำเป็น
                                    $created_at = $data[3];
                                    if (empty($created_at) || $created_at == '0000-00-00 00:00:00') {
                                        $created_at = date('Y-m-d H:i:s');
                                    }
                                    
                                    $stmt = $pdo->prepare("INSERT INTO users (teacher_id, teacher_name, id_card_last, created_at, department, mobile_phone) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE teacher_name=VALUES(teacher_name), id_card_last=VALUES(id_card_last), created_at=VALUES(created_at), department=VALUES(department), mobile_phone=VALUES(mobile_phone)");
                                    $stmt->execute([
                                        trim($data[0]), 
                                        trim($data[1]), 
                                        trim($data[2]), 
                                        $created_at, 
                                        trim($data[4]), 
                                        trim($data[5])
                                    ]);
                                    $success_count++;
                                } else {
                                    $errors[] = "แถว " . ($line_num + 1) . ": ข้อมูลไม่ครบ (ต้องการ 6 คอลัมน์ แต่มี " . count($data) . " คอลัมน์)";
                                    $error_count++;
                                }
                                break;
                        }
                    } catch (Exception $e) {
                        $errors[] = "แถว " . ($line_num + 1) . ": " . $e->getMessage();
                        $error_count++;
                    }
                }
                
                $pdo->commit();
                $message = "นำเข้าข้อมูลสำเร็จ: {$success_count} รายการ";
                if ($error_count > 0) {
                    $message .= ", ข้อผิดพลาด: {$error_count} รายการ";
                    // แสดง error รายละเอียดแค่ 5 รายการแรก
                    if (!empty($errors)) {
                        $message .= "<br><small>ตัวอย่างข้อผิดพลาด:<br>" . implode("<br>", array_slice($errors, 0, 5));
                        if (count($errors) > 5) {
                            $message .= "<br>... และอีก " . (count($errors) - 5) . " ข้อผิดพลาด";
                        }
                        $message .= "</small>";
                    }
                }
                $message_type = 'success';
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "กรุณาอัพโหลดไฟล์ CSV เท่านั้น";
            $message_type = 'error';
        }
    } else {
        $message = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
        $message_type = 'error';
    }
}

// ดึงสถิติข้อมูล
try {
    $pdo = getPDO();
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $stats['students'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
    $stats['subjects'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance_records");
    $stats['records'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $stats = ['students' => 0, 'subjects' => 0, 'users' => 0, 'records' => 0];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - จัดการข้อมูล</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
        }

        .upload-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .upload-card {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 1.5rem;
            transition: border-color 0.3s;
        }

        .upload-card:hover {
            border-color: #667eea;
        }

        .upload-card h3 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .upload-btn {
            width: 100%;
            background-color: #667eea;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .upload-btn:hover {
            background-color: #5a67d8;
        }

        .format-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .format-info h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .format-info ul {
            margin-left: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .upload-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🔧 Admin Dashboard - จัดการข้อมูล</h1>
            <a href="admin_logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['students']); ?></div>
                <div class="stat-label">นักเรียน</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['subjects']); ?></div>
                <div class="stat-label">รายวิชา</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-label">ครู</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['records']); ?></div>
                <div class="stat-label">บันทึก มส</div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h2 style="margin-bottom: 1.5rem; color: #333;">📁 นำเข้าข้อมูล CSV</h2>
            
            <div class="upload-grid">
                <!-- Students Upload -->
                <div class="upload-card">
                    <h3>👨‍🎓 ข้อมูลนักเรียน</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="table" value="students">
                        <div class="form-group">
                            <label>เลือกไฟล์ CSV นักเรียน</label>
                            <input type="file" name="csv_file" accept=".csv" required>
                        </div>
                        <button type="submit" class="upload-btn">อัพโหลดข้อมูลนักเรียน</button>
                    </form>
                    <div class="format-info">
                        <h4>รูปแบบไฟล์ CSV:</h4>
                        <ul>
                            <li>student_id (รหัสนักเรียน)</li>
                            <li>fullname (ชื่อ-สกุล)</li>
                            <li>class (ชั้น)</li>
                            <li>number (เลขที่)</li>
                            <li>id_card (เลขประจำตัวประชาชน)</li>
                        </ul>
                    </div>
                </div>

                <!-- Subjects Upload -->
                <div class="upload-card">
                    <h3>📚 ข้อมูลรายวิชา</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="table" value="subjects">
                        <div class="form-group">
                            <label>เลือกไฟล์ CSV รายวิชา</label>
                            <input type="file" name="csv_file" accept=".csv" required>
                        </div>
                        <button type="submit" class="upload-btn">อัพโหลดข้อมูลรายวิชา</button>
                    </form>
                    <div class="format-info">
                        <h4>รูปแบบไฟล์ CSV:</h4>
                        <ul>
                            <li>subject_id (รหัสวิชา)</li>
                            <li>subject_name (ชื่อวิชา)</li>
                            <li>grade_level (ระดับชั้น เช่น 1,2,3,4,5,6)</li>
                        </ul>
                    </div>
                </div>

                <!-- Users Upload -->
                <div class="upload-card">
                    <h3>👨‍🏫 ข้อมูลครู</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="table" value="users">
                        <div class="form-group">
                            <label>เลือกไฟล์ CSV ครู</label>
                            <input type="file" name="csv_file" accept=".csv" required>
                        </div>
                        <button type="submit" class="upload-btn">อัพโหลดข้อมูลครู</button>
                    </form>
                    <div class="format-info">
                        <h4>รูปแบบไฟล์ CSV:</h4>
                        <ul>
                            <li>teacher_id (รหัสครู)</li>
                            <li>teacher_name (ชื่อครู)</li>
                            <li>id_card_last (เลขท้ายบัตรประชาชน 6 หลัก)</li>
                            <li>created_at (วันที่สร้าง)</li>
                            <li>department (แผนก)</li>
                            <li>mobile_phone (เบอร์โทรศัพท์)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1rem; color: #333;">🔗 ลิงก์เพิ่มเติม</h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="admin.php" style="background-color: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">ดูรายงานทั้งหมด</a>
                <a href="index.php" style="background-color: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">หน้าหลักระบบ</a>
            </div>
        </div>
    </div>
</body>
</html>