<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Customer_Import_Template.csv');
echo "\xEF\xBB\xBF"; 

$output = fopen('php://output', 'w');

// หัวตาราง (ตัดอำเภอและอีเมลทิ้ง)
fputcsv($output, [
    'จังหวัด', 
    'โรงพยาบาล / บริษัท', 
    'แผนก', 
    'ชื่อลูกค้า / ผู้ติดต่อ', 
    'เบอร์ติดต่อ (ใส่ขีดด้วย)' // แนะนำให้เซลส์รู้ว่าควรใส่ขีด
]);

// ข้อมูลตัวอย่าง (ใส่ขีด - เข้าไปเพื่อให้ Excel ไม่ตัดเลข 0)
fputcsv($output, [
    'กรุงเทพมหานคร', 
    'รพ.ศิริราช', 
    'จัดซื้อ', 
    'คุณสมชาย ใจดี', 
    '0812345678' 
]);

fclose($output);
exit;
?>