<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $sr_no = trim($_POST['sr_no']);
    $fullname = trim($_POST['fullname']);
    $position = trim($_POST['position']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    try {
        if (!empty($_POST['password'])) {
            // มีการเปลี่ยนรหัสผ่านใหม่
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE employees SET sr_no=?, fullname=?, position=?, email=?, username=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sr_no, $fullname, $position, $email, $username, $hashed_password, $id]);
        } else {
            // ไม่เปลี่ยนรหัสผ่าน
            $sql = "UPDATE employees SET sr_no=?, fullname=?, position=?, email=?, username=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sr_no, $fullname, $position, $email, $username, $id]);
        }
        
        // 📌 จุดที่เพิ่ม: ส่งข้อความแจ้งเตือนเมื่ออัปเดตข้อมูลสำเร็จ
        $_SESSION['success'] = "อัปเดตข้อมูลพนักงานสำเร็จเรียบร้อยแล้ว!";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
    }
}

header("Location: index.php?page=employees"); 
exit;
?>