<?php

$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];

if (!isset($_SESSION['user_id']) || !in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหรือทำลายชุดข้อมูลสินค้านี้";
    header("Location: index.php?page=products");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_ids'])) {
    $ids = $_POST['product_ids'];

    if (is_array($ids) && count($ids) > 0) {
        try {
            $clean_ids = array_map('intval', $ids);
            $placeholders = rtrim(str_repeat('?,', count($clean_ids)), ',');
            
            $sql = "DELETE FROM products WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($clean_ids);
            
            $_SESSION['success'] = "ลบรายการสินค้าที่เลือกจำนวน " . count($clean_ids) . " รายการสำเร็จเรียบร้อยแล้ว!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถลบสินค้าบางชิ้นได้เนื่องจากสินค้านั้นถูกเลือกใช้ไปในรายงานใบเสนอราคาแล้ว: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "กรุณาเลือกรายการสินค้าที่ต้องการลบ";
    }
}

header("Location: index.php?page=products");
exit;
?>