<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ตรวจสอบว่ามีการล็อกอินและมี id ที่ต้องการดู
if (!isset($_GET['id'])) {
    exit('Missing ID parameter');
}

// ตรวจสอบว่ามีการล็อกอิน (ทั้งครูและนักเรียน)
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['student_id'])) {
    exit('Access Denied - Not logged in');
}

// Include config file
require_once 'config.php';

// Get PDO connection using the function
$pdo = getPDO();

try {
    // ดึงข้อมูลบันทึก
    $stmt = $pdo->prepare("
        SELECT ar.*, s.fullname, s.class, s.number, sub.subject_name, u.teacher_name
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.student_id
        JOIN subjects sub ON ar.subject_id = sub.subject_id
        JOIN users u ON ar.teacher_id = u.teacher_id
        WHERE ar.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch();

    if (!$record) {
        exit('Record not found');
    }

    // ตรวจสอบสิทธิ์การเข้าถึง
    if (isset($_SESSION['student_id'])) {
        // ถ้าเป็นนักเรียน ต้องดูได้เฉพาะข้อมูลของตัวเอง
        if ($record['student_id'] !== $_SESSION['student_id']) {
            exit('Access Denied - Not your record');
        }
    }

    require_once(__DIR__ . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public function Header() {
            // เพิ่มระยะห่างจากขอบบน
            $this->SetY(15); // ปรับตำแหน่ง Y ให้ต่ำลงมา
            $this->SetFont('thsarabunnew', 'B', 18); // ลดขนาดตัวอักษรลง
            $this->Cell(0, 10, 'แบบคำร้องขอมีสิทธิ์สอบปลายภาค', 0, 1, 'C');
        }
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('ระบบเช็คชื่อ');
    $pdf->SetTitle('แบบคำร้องขอมีสิทธิ์สอบปลายภาค');
    $pdf->SetMargins(15, 35, 15); // เพิ่มระยะขอบบนเป็น 35
    $pdf->AddPage();
    $pdf->SetFont('thsarabunnew', '', 16);


    // วันที่ปัจจุบัน
    $thai_month = array(
        "01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน",
        "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม",
        "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"
    );
    
    $date = date('d');
    $month = $thai_month[date('m')];
    $year = date('Y') + 543;


// ... โค้ดส่วนบนเหมือนเดิม ...

    $pdf->SetMargins(15, 15, 15); // ลดระยะขอบกระดาษ
    
    $html = <<<EOD
    <style>
        * {
            line-height: 0.8;
            padding: 0;
            margin: 0;
        }
        p {
            margin: 0;
            padding: 0;
        }
        .date { text-align: right; margin-bottom: 2px; }
        .title { margin-bottom: 1px; }
        /* แก้ไขส่วน indent ให้ใช้ text-indent แทน margin-left */
        p.indent { 
            text-indent: 50px;
            padding-right: 10px;
        }
    </style>

     <div style="text-align: right; margin-top: 10px;">วันที่ {$date} เดือน {$month} พ.ศ. {$year}</div>
    <div style="margin-top: 20px;">เรื่อง  ขอมีสิทธิ์สอบปลายภาค</div>
    <div>เรียน  ผู้อำนวยการโรงเรียนเด็กดีวิทยาคม</div>
    <div style="text-indent: 50px;">ด้วยข้าพเจ้า {$record['fullname']} นักเรียนชั้น {$record['class']} เลขที่ {$record['number']} เลขประจำตัว {$record['student_id']}</div>
    <div style="text-indent: 50px;">รับทราบจากประกาศโรงเรียนว่าไม่มีสิทธิ์สอบปลายภาค ภาคเรียนที่ ................ ปีการศึกษา ............................</div>
    <div>รหัสวิชา {$record['subject_id']} รายวิชา {$record['subject_name']} คุณครูผู้สอน {$record['teacher_name']}</div>
    <div>ทั้งนี้เพราะข้าพเจ้ามีเหตุจำเป็น คือ....................................................................................................</div>
        
    <p class="indent">ข้าพเจ้าได้มอบหลักฐานเพื่อประกอบการพิจารณาคือ</p>
    <p class="indent">1. ใบรับรองแพทย์ จำนวน .............. ฉบับ</p>
    <p class="indent">2. หลักฐานอื่น ๆ คือ ...............................................................................................</p>
        
    <p class="indent">จึงเรียนมาเพื่อโปรดพิจารณาให้ข้าพเจ้ามีสิทธิ์สอบปลายภาคในรายวิชาดังกล่าวที่มีเวลาเรียน</p>
    <p>คิดเป็นร้อยละ {$record['attendance_percent']} ของเวลาเรียนทั้งหมด</p>

    <div style="text-align: center; margin: 2px 0;">ขอแสดงความนับถือ</div>
    <div style="text-align: center;">
        ลงชื่อ ............................................. โทร................................<br>
        ({$record['fullname']})
    </div>

    <div style="margin: 2px 0;">
        ข้าพเจ้า...........................................................เป็นผู้ปกครองของ {$record['fullname']}<br>
        ขอรับรองว่าข้อความและหลักฐานข้างต้นเป็นจริงทุกประการ
    </div>
    <div style="text-align: right;">
        ลงชื่อ....................................... โทร................................<br>
        (............................................)
    </div>

       <table cellpadding="1" style="width: 100%;" border="1">
        <tr style="height: 85px;">
            <td width="33%" style="font-size: 14px; padding: 3px;">
                1. ครูผู้สอน<br><br>
                □ อนุญาตให้เข้าสอบ<br>
                □ ไม่อนุญาตให้สอบ<br><br>
                ลงชื่อ ........................................<br>
                (...................................................)<br>
                วันที่ ..... เดือน ............... พ.ศ.256....
            </td>
            <td width="33%" style="font-size: 14px; padding: 3px;">
                2. หัวหน้ากลุ่มสาระการเรียนรู้<br><br>
                □ อนุญาตให้เข้าสอบ<br>
                □ ไม่อนุญาตให้สอบ<br><br>
                ลงชื่อ ........................................<br>
                (...................................................)<br>
                วันที่ ..... เดือน ............... พ.ศ.256....
            </td>
            <td width="33%" style="font-size: 14px; padding: 3px;">
                3. รองผู้อำนวยการกลุ่มวิชาการ<br><br>
                □ อนุญาตให้เข้าสอบ<br>
                □ ไม่อนุญาตให้สอบ<br><br>
                ลงชื่อ ........................................<br>
                (...................................................)<br>
                วันที่ ..... เดือน ............... พ.ศ.256....
            </td>
        </tr>
    </table>
    <br>
    <br>

    <div style="text-align: center; margin: 4px 0;">
        □ อนุญาตให้เข้าสอบ&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;□ ไม่อนุญาตให้สอบ<br><br>
        ลงชื่อ ................................................<br>
        (นายอำนวยสุข อำนวยการ)<br>
        ผู้อำนวยการโรงเรียนเด็กดีวิทยาคม
    </div>
EOD;


    // เพิ่มส่วนนี้เพื่อสร้าง PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // ส่งไฟล์ PDF
    $pdf->Output('คำร้องขอมีสิทธิ์สอบ_' . $record['student_id'] . '.pdf', 'I');  

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>