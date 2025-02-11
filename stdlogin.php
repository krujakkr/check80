<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = new PDO("mysql:host=localhost;dbname=knwacth_Check80;charset=utf8", "knwacth_Check80", "Nb4z1k7?7");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $student_id = $_POST['student_id'];
        $id_card = $_POST['id_card'];
        
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND id_card = ?");
        $stmt->execute([$student_id, $id_card]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['student_id'] = $student_id;
            header('Location: student_view.php');
            exit();
        } else {
            $error = "ข้อมูลไม่ถูกต้อง";
        }
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>เข้าสู่ระบบสำหรับนักเรียน</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #43cea2, #185a9d);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            opacity: 0.9;
        }
        .error {
            color: #ff0000;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>เข้าสู่ระบบสำหรับนักเรียน</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>รหัสนักเรียน</label>
                <input type="text" name="student_id" required maxlength="5">
            </div>
            <div class="form-group">
                <label>เลขประจำตัวประชาชน</label>
                <input type="password" name="id_card" required maxlength="13">
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>