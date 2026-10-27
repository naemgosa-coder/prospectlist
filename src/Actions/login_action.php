<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ค้นหาพนักงานจากฐานข้อมูล
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // ตรวจสอบว่าเจอผู้ใช้ และ รหัสผ่านตรงกันหรือไม่ (ใช้ password_verify สำหรับรหัสที่เข้ารหัสไว้)
    if ($user && password_verify($password, $user['password'])) {
        // ล็อกอินสำเร็จ -> เก็บข้อมูลลง Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['position'] = $user['position'];
        
        // ส่งไปหน้าหลัก
        header("Location: index.php?page=dashboard");
        exit;
    } else {
        // ล็อกอินไม่สำเร็จ -> ส่งกลับไปหน้า login พร้อมข้อความ Error
        $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!";
        header("Location: index.php?page=login");
        exit;
    }
} else {
    // ถ้าไม่ได้ส่งข้อมูลแบบ POST ให้เด้งกลับไปหน้า login
    header("Location: index.php?page=login");
    exit;
}
?>