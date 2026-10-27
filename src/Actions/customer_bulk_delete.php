<?php

// ป้องกันความปลอดภัยเช็คสิทธิ์ล็อกอินและตำแหน่งระดับผู้บริหาร
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];

if (!isset($_SESSION['user_id']) || !in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ดำเนินการลบข้อมูลชุดนี้ครับ";
    header("Location: index.php?page=customers");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_ids'])) {
    $ids = $_POST['customer_ids'];

    if (is_array($ids) && count($ids) > 0) {
        try {
            // ทำความสะอาดแปลงค่าไอดีทุกตัวให้เป็น Integer ชัวร์ๆ ป้องกัน SQL Injection
            $clean_ids = array_map('intval', $ids);
            
            // สร้างเครื่องหมายคำถาม (?) ตามจำนวนไอดีที่ส่งมา
            $placeholders = rtrim(str_repeat('?,', count($clean_ids)), ',');
            
            $sql = "DELETE FROM customers WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($clean_ids);
            
            $_SESSION['success'] = "ลบข้อมูลลูกค้าที่เลือกจำนวน " . count($clean_ids) . " รายการสำเร็จเรียบร้อยแล้ว!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถลบได้เนื่องจากข้อมูลลูกค้าถูกเชื่อมโยงอยู่กับรายงานการติดต่อ: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "กรุณาเลือกรายการที่ต้องการลบ";
    }
}

header("Location: index.php?page=customers");
exit;
?>