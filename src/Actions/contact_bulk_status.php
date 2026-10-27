<?php

// ป้องกันคนไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ดึงรหัสไอดีทั้งหมดที่ส่งมาจาก Checkbox
    $contact_ids = $_POST['contact_ids'] ?? [];
    $new_status = $_POST['new_status'] ?? '';

    if (!empty($contact_ids) && !empty($new_status)) {
        try {
            $pdo->beginTransaction();

            // สร้างเครื่องหมายคำถาม (?,?,?) ตามจำนวนไอดีที่ถูกเลือก
            $placeholders = implode(',', array_fill(0, count($contact_ids), '?'));
            
            // 1. อัปเดตสถานะเคสทั้งหมดที่ถูกติ๊กให้เป็นข้อมูลเก่า
            $sql = "UPDATE contacts SET status = ? WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            
            // นำเอาสถานะใหม่ไปวางไว้ข้างหน้า แล้วต่อท้ายด้วยอาร์เรย์ไอดีทั้งหมด
            $params = array_merge([$new_status], $contact_ids);
            $stmt->execute($params);

            // 2. บันทึกประวัติความคืบหน้า (Timeline) ให้แต่ละเคสอัตโนมัติด้วย เพื่อแจ้งว่าปิดเคสแบบกลุ่ม
            $sql_log = "INSERT INTO contact_updates (contact_id, status, recent_activity) VALUES (?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            foreach ($contact_ids as $id) {
                $stmt_log->execute([$id, $new_status, "ระบบอัปเดตสถานะปิดงานแบบกลุ่มอัตโนมัติ"]);
            }

            $pdo->commit();
            $_SESSION['success'] = "ปรับปรุงสถานะข้อมูลเก่าสำเร็จทั้งหมด " . count($contact_ids) . " รายการ!";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "กรุณาเลือกรายการและเลือกสถานะให้ถูกต้อง";
    }
}

// วิ่งกลับไปที่หน้าการติดต่อลูกค้า
header("Location: index.php?page=contacts");
exit;
?>