<?php

// ดักจับตรวจสอบสิทธิ์เพื่อความปลอดภัยก่อนบันทึกข้อมูล
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!isset($_SESSION['user_id']) || !in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ดำเนินการจัดการข้อมูลสินค้าครับ";
    header("Location: index.php?page=products");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id          = intval($_POST['id']);
    $category_id = (!empty($_POST['category_id'])) ? intval($_POST['category_id']) : NULL;
    $model       = !empty($_POST['model']) ? trim($_POST['model']) : NULL;
    $details     = !empty($_POST['details']) ? trim($_POST['details']) : NULL;

    try {
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, model = ?, details = ? WHERE id = ?");
        $stmt->execute([$category_id, $model, $details, $id]);
        $_SESSION['success'] = "อัปเดตข้อมูลรุ่นสินค้าสำเร็จเรียบร้อยแล้ว!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
header("Location: index.php?page=products");
exit;
