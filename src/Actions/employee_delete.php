<?php

// --- ตรวจสอบสิทธิ์การลบข้อมูล ---
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($_SESSION['position'], $allowed_roles)) {
    // ถ้าไม่ใช่แอดมินหรือผู้บริหาร ให้ส่งข้อความแจ้งเตือนและเด้งกลับทันที
    $_SESSION['error'] = "ปฏิเสธการเข้าถึง! เฉพาะผู้บริหารหรือแอดมินเท่านั้นที่สามารถลบข้อมูลได้";
    
    // เด้งกลับไปหน้าก่อนหน้า (หรือหน้าที่คุณต้องการ)
    header("Location: index.php"); 
    exit;
}
// ----------------------------

if (isset($_GET['id'])) {
    // 📌 ดักไว้ก่อน: ห้ามแอดมินกดลบชื่อตัวเองที่กำลังใช้ล็อกอินอยู่
    if ($_GET['id'] == $_SESSION['user_id']) {
        $_SESSION['error'] = "ไม่สามารถลบได้! คุณกำลังใช้งานบัญชีนี้อยู่ครับ";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            // 📌 จุดที่เพิ่ม: ส่งข้อความแจ้งเตือนเมื่อลบสำเร็จ
            $_SESSION['success'] = "ลบข้อมูลพนักงานสำเร็จเรียบร้อยแล้ว!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถลบได้ เนื่องจากพนักงานนี้มีประวัติการติดต่ออยู่ในระบบครับ";
        }
    }
}

header("Location: index.php?page=employees"); 
exit;
?>