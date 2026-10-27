<?php

// ป้องกันคนไม่ได้ล็อกอินเข้ามา
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าจากฟอร์ม
    $sr_no = trim($_POST['sr_no']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 🛑 [ใหม่] เช็คว่ารหัสพนักงาน (SR No.), Username หรือ Email ซ้ำหรือไม่
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE username = ? OR sr_no = ? OR email = ?");
    $stmt_check->execute([$username, $sr_no, $email]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "รหัสพนักงาน (SR No.), Username หรือ Email นี้มีผู้ใช้งานแล้วในระบบครับ";
        header("Location: index.php?page=employee_add");
        exit;
    }

    // 2. เข้ารหัสรหัสผ่านเพื่อความปลอดภัย (สำคัญมาก!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 3. เตรียมคำสั่ง SQL บันทึกลงตาราง
        $sql = "INSERT INTO employees (sr_no, fullname, email, position, username, password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // 4. สั่ง Execute
        $stmt->execute([$sr_no, $fullname, $email, $position, $username, $hashed_password]);

        $_SESSION['success'] = "เพิ่มข้อมูลพนักงานสำเร็จเรียบร้อยแล้ว!";
        header("Location: index.php?page=employees");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        header("Location: index.php?page=employee_add");
        exit;
    }
}
?>