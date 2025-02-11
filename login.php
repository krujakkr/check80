<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=knwacth_Check80;charset=utf8", "knwacth_Check80", "Nb4z1k7?7");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $teacher_id = $_POST['teacher_id'];
        $id_card_last = $_POST['id_card_last'];

        if (strlen($teacher_id) !== 3) {
            $error = "รหัสครูต้องมี 3 หลัก";
        } elseif (strlen($id_card_last) !== 6) {
            $error = "เลขท้ายบัตรประชาชนต้องมี 6 หลัก";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE teacher_id = ? AND id_card_last = ?");
            $stmt->execute([$teacher_id, $id_card_last]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['teacher_id'] = $teacher_id;
                header('Location: form.php');
                exit();
            } else {
                $error = "ข้อมูลไม่ถูกต้อง";
            }
        }
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'logout') {
    $success = "ออกจากระบบสำเร็จ";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ระบบบันทึกนักเรียนมีเวลาไม่ถึง 80%</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 1.6;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .school-name {
            text-align: center;
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .system-name {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #43cea2;
            box-shadow: 0 0 0 3px rgba(67, 206, 162, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(45deg, #43cea2, #185a9d);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(74, 222, 128, 0.2);
            border: 1px solid rgb(74, 222, 128);
            color: rgb(22, 101, 52);
        }

        .alert-error {
            background-color: rgba(248, 113, 113, 0.2);
            border: 1px solid rgb(248, 113, 113);
            color: rgb(153, 27, 27);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }

            .school-name {
                font-size: 1.3rem;
            }

            .system-name {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="school-name">โรงเรียนแก่นนครวิทยาลัย</h1>
        <h2 class="system-name">ระบบบันทึกนักเรียนมีเวลาเรียนไม่ถึง 80%</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label>รหัสครู (3 หลัก)</label>
                <input type="text" name="teacher_id" maxlength="3" pattern=".{3,3}" required>
            </div>
            
            <div class="form-group">
                <label>เลขท้ายบัตรประชาชน (6 หลัก)</label>
                <input type="password" name="id_card_last" maxlength="6" pattern=".{6,6}" required>
            </div>
            
            <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
        </form>
    </div>

    <script>
    function validateForm() {
        var teacherId = document.getElementsByName('teacher_id')[0].value;
        var idCardLast = document.getElementsByName('id_card_last')[0].value;

        if (teacherId.length !== 3) {
            alert('รหัสครูต้องมี 3 หลัก');
            return false;
        }

        if (idCardLast.length !== 6) {
            alert('เลขท้ายบัตรประชาชนต้องมี 6 หลัก');
            return false;
        }

        return true;
    }
    </script>
</body>
</html>