<?php

// ป้องกันความปลอดภัยเช็คสิทธิ์ล็อกอินก่อนดาวน์โหลด
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนดำเนินการครับ");
}

// ตั้งค่า Header บังคับให้ Browser ดาวน์โหลดไฟล์เป็น CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Product_Import_Template.csv');

// ใส่รหัส BOM เพื่อป้องกันปัญหาภาษาไทยแสดงผลเป็นภาษาต่างดาวใน Microsoft Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

//  1. เขียนหัวตาราง (Header) ให้ตรงกับโครงสร้างระบบใหม่ที่มี "หมวดหมู่" นำหน้า
fputcsv($output, [
    'ชื่อสินค้า / แบรนด์', 
    'รุ่น (Model)', 
    'รายละเอียดสินค้าเพิ่มเติม'
]);

//  2. เขียนข้อมูลตัวอย่าง (Dummy Data) แนะนำวิธีพิมพ์ให้ผู้ใช้งานดูเป็นแนวทาง
fputcsv($output, [
    'CONWELL', 
    'CW-100', 
    'เครื่องวัดความดันโลหิตแบบดิจิตอล หน้าจอสัมผัส'
]);

fputcsv($output, [
    'SONY', 
    'UP-D898MD', 
    'เครื่องพิมพ์ภาพขาวดำสำหรับงานอัลตราซาวด์'
]);

fputcsv($output, [
    'SEIRIN', 
    'J-Type 0.20x30mm', 
    'เข็มฝังเข็มปราศจากเชื้อ ชนิดมีหลอดนำเข็ม'
]);

fclose($output);
exit;
?>