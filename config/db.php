<?php
// อ่านค่าการเชื่อมต่อจากไฟล์ .env (อยู่นอก public_html จึงเปิดผ่านเว็บไม่ได้) ดูตัวอย่างที่ .env.example
$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW);
if ($env === false) {
    die("ไม่พบไฟล์ .env (คัดลอกจาก .env.example แล้วใส่ค่าฐานข้อมูล)");
}

$host = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? '';
$username = $env['DB_USER'] ?? '';
$password = $env['DB_PASS'] ?? '';

try {
    // เชื่อมต่อฐานข้อมูล
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // ตั้งค่าให้แจ้งเตือนเมื่อมี Error (มีประโยชน์มากตอนหาบั๊ก)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ตั้งค่าให้เวลาดึงข้อมูลมา ใช้ชื่อคอลัมน์เป็น Key ได้เลย (Associative Array)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // ถ้าเชื่อมต่อไม่สำเร็จ ให้หยุดทำงานและแสดงข้อความ Error
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
