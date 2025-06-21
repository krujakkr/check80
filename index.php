<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ มส</title>
    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            text-align: center;
            padding: 2rem;
            width: 100%;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .school-name {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
        }

        .system-name {
            font-size: 1.4rem;
            color: #666;
            margin: 0.5rem 0;
        }

        .container {
            display: flex;
            gap: 2rem;
            padding: 1rem;
            max-width: 1200px;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            width: 300px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-card h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #2196F3;
        }

        .login-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            width: 80%;
            margin-top: 1rem;
        }

        .login-btn:hover {
            background-color: #1976D2;
        }

        .teacher-card {
            border-top: 5px solid #2196F3;
        }

        .student-card {
            border-top: 5px solid #4CAF50;
        }

        .admin-card {
            border-top: 5px solid #9C27B0;
        }

        .student-card .icon {
            color: #4CAF50;
        }

        .admin-card .icon {
            color: #9C27B0;
        }

        .student-card .login-btn {
            background-color: #4CAF50;
        }

        .student-card .login-btn:hover {
            background-color: #388E3C;
        }

        .admin-card .login-btn {
            background-color: #9C27B0;
        }

        .admin-card .login-btn:hover {
            background-color: #7B1FA2;
        }

        .description {
            color: #666;
            margin: 1rem 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: center;
            }

            .login-card {
                width: 90%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="school-name">โรงเรียนเด็กดีวิทยาคม</h1>
        <div class="system-name">ระบบจัดการนักเรียนที่มีเวลาเรียนไม่ถึง 80%</div>
    </div>

    <div class="container">
        <div class="login-card teacher-card">
            <div class="icon">👨‍🏫</div>
            <h2>สำหรับครู</h2>
            <p class="description">
                - บันทึกข้อมูลนักเรียนที่มีเวลาเรียนไม่ถึง 80%<br>
                - ตรวจสอบและอนุมัติคำร้อง<br>
                - ดูรายงานสรุป
            </p>
            <a href="login.php" class="login-btn">เข้าสู่ระบบสำหรับครู</a>
        </div>

        <div class="login-card student-card">
            <div class="icon">👨‍🎓</div>
            <h2>สำหรับนักเรียน</h2>
            <p class="description">
                - ตรวจสอบรายวิชาที่มีเวลาเรียนไม่ถึง 80%<br>
                - ยื่นคำร้องขอมีสิทธิ์สอบ<br>
                - ติดตามสถานะคำร้อง
            </p>
            <a href="stdlogin.php" class="login-btn">เข้าสู่ระบบสำหรับนักเรียน</a>
        </div>

        <div class="login-card admin-card">
            <div class="icon">🔧</div>
            <h2>สำหรับ Admin</h2>
            <p class="description">
                - นำเข้าข้อมูลนักเรียน ครู รายวิชา<br>
                - จัดการฐานข้อมูลระบบ<br>
                - ดูสถิติการใช้งาน
            </p>
            <a href="admin_login.php" class="login-btn">เข้าสู่ระบบ Admin</a>
        </div>
    </div>
</body>
</html>