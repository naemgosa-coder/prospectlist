<?php

// เช็คสิทธิ์ (ให้เฉพาะแอดมินหรือผู้บริหารเท่านั้นที่ลบได้)
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];

if (!in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ในการลบข้อมูลครับ";
    header("Location: index.php?page=contacts");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['contact_ids'])) {
    $ids = $_POST['contact_ids'];
    
    // สร้างเครื่องหมาย ? ตามจำนวน ID ที่เลือก
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        // เริ่มต้น Transaction ป้องกันข้อมูลลบไม่สมบูรณ์
        $pdo->beginTransaction();

        // 1. ลบรายการสินค้าที่อยู่ในบิลนี้ก่อน (ตาราง contact_items) ป้องกัน Foreign Key Error
        $stmt_items = $pdo->prepare("DELETE FROM contact_items WHERE contact_id IN ($placeholders)");
        $stmt_items->execute($ids);

        // 2. ลบประวัติการติดตามงาน (ตาราง contact_updates)
        $stmt_updates = $pdo->prepare("DELETE FROM contact_updates WHERE contact_id IN ($placeholders)");
        $stmt_updates->execute($ids);

        // 3. ลบข้อมูลหลัก (ตาราง contacts)
        $stmt_main = $pdo->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
        $stmt_main->execute($ids);

        // บันทึกยืนยันการลบ
        $pdo->commit();

        $_SESSION['success'] = "ลบข้อมูลการเข้าพบลูกค้าจำนวน " . count($ids) . " รายการ สำเร็จเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "กรุณาเลือกอย่างน้อย 1 รายการเพื่อลบครับ";
}

// กลับไปหน้าเดิม พร้อมแนบพารามิเตอร์ tab กลับไปด้วย
$ref_url = "index.php?page=contacts";
if (isset($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'tab=closed') !== false) {
        $ref_url .= "&tab=closed";
    } elseif (strpos($_SERVER['HTTP_REFERER'], 'tab=all') !== false) {
        $ref_url .= "&tab=all";
    }
}

header("Location: " . $ref_url);
exit;
?>