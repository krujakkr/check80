<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('freeserif', 'B', 20);
        $this->Cell(0, 15, 'รายงานการเข้าเรียน', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(20);
    }
}

session_start();

if (!isset($_SESSION['teacher_id']) || !isset($_GET['id'])) {
    exit('Access Denied');
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=knwacth_Check80;charset=utf8", "knwacth_Check80", "Nb4z1k7?7");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT ar.*, s.fullname, s.class, s.number, sub.subject_name 
        FROM attendance_records ar
        JOIN students s ON ar.student_id = s.student_id
        JOIN subjects sub ON ar.subject_id = sub.subject_id
        WHERE ar.id = ? AND ar.teacher_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['teacher_id']]);
    $record = $stmt->fetch();

    if (!$record) {
        exit('Record not found');
    }

    // สร้าง PDF
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // ตั้งค่าเอกสาร
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ระบบบันทึกเวลาเรียน');
    $pdf->SetTitle('รายงานการเข้าเรียน - ' . $record['student_id']);
    $pdf->SetMargins(15, 35, 15);

    // เพิ่มหน้า
    $pdf->AddPage();

    // ตั้งค่าฟอนต์
    $pdf->SetFont('freeserif', '', 14);

    // สร้าง HTML content
    $html = <<<EOD
    <style>
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; }
        .label { font-weight: bold; width: 30%; }
        .content { width: 70%; }
    </style>
    <table border="1" cellpadding="5">
        <tr>
            <td class="label">รหัสนักเรียน</td>
            <td class="content">{$record['student_id']}</td>
        </tr>
        <tr>
            <td class="label">ชื่อ-สกุล</td>
            <td class="content">{$record['fullname']}</td>
        </tr>
        <tr>
            <td class="label">ชั้น</td>
            <td class="content">{$record['class']}</td>
        </tr>
        <tr>
            <td class="label">เลขที่</td>
            <td class="content">{$record['number']}</td>
        </tr>
        <tr>
            <td class="label">วิชา</td>
            <td class="content">{$record['subject_name']} ({$record['subject_id']})</td>
        </tr>
        <tr>
            <td class="label">ร้อยละเวลาเรียน</td>
            <td class="content">{$record['attendance_percent']}%</td>
        </tr>
        <tr>
            <td class="label">วันที่บันทึก</td>
            <td class="content">{$record['created_at']}</td>
        </tr>
    </table>
    EOD;

    $pdf->writeHTML($html, true, false, true, false, '');

    // ส่งไฟล์ PDF
    $pdf->Output('attendance_' . $record['student_id'] . '.pdf', 'I');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>