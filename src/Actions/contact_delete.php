<?php

// --- ตรวจสอบสิทธิ์การลบข้อมูล ---
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($_SESSION['position'], $allowed_roles)) {
    // ถ้าไม่ใช่แอดมินหรือผู้บริหาร ให้ส่งข้อความแจ้งเตือนและเด้งกลับทันที
    $_SESSION['error'] = "ปฏิเสธการเข้าถึง! เฉพาะผู้บริหารหรือแอดมินเท่านั้นที่สามารถลบข้อมูลได้";
    header("Location: index.php"); 
    exit;
}
// ----------------------------

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $pdo->beginTransaction();

        // 1. ลบรายการสินค้าที่ผูกกับบิลนี้ (ป้องกัน Error)
        $pdo->prepare("DELETE FROM contact_items WHERE contact_id = ?")->execute([$id]);

        // 2. ลบประวัติการติดตามงาน
        $pdo->prepare("DELETE FROM contact_updates WHERE contact_id = ?")->execute([$id]);

        // 3. ลบบิลหลัก
        $pdo->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);

        $pdo->commit();
        
        // 📌 จุดที่แก้ไข: เพิ่มบรรทัดนี้ เพื่อให้มีข้อความเด้งเตือนมุมขวาล่าง
        $_SESSION['success'] = "ลบข้อมูลการเข้าพบลูกค้าสำเร็จเรียบร้อยแล้ว!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}

header("Location: index.php?page=contacts"); 
exit;
?>