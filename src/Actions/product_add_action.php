<?php

// ป้องกันคนไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $_POST['category_id'] ?? NULL;
    $model       = !empty($_POST['model']) ? trim($_POST['model']) : NULL;
    $details     = !empty($_POST['details']) ? trim($_POST['details']) : NULL;

    // 🛑 [ดักจับที่ 1] ป้องกันการพิมพ์ตัวหนังสือลงในช่องแบรนด์ บังคับต้องเป็นรหัสตัวเลข (ID) เท่านั้น
    if (!is_numeric($category_id)) {
        $_SESSION['error'] = "กรุณาเลือกแบรนด์สินค้าจากตัวเลือกที่มีให้เท่านั้นครับ (ห้ามพิมพ์ชื่อใหม่เอง)";
        header("Location: index.php?page=product_add");
        exit;
    }

    // 🛑 [ดักจับที่ 2] เช็คให้ชัวร์ว่ารหัสแบรนด์นี้ มีอยู่จริงในตารางแบรนด์ (product_categories)
    $stmt_check_cat = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE id = ?");
    $stmt_check_cat->execute([$category_id]);
    if ($stmt_check_cat->fetchColumn() == 0) {
        $_SESSION['error'] = "ไม่พบแบรนด์สินค้านี้ในระบบ กรุณาตรวจสอบอีกครั้ง";
        header("Location: index.php?page=product_add");
        exit;
    }

    // ใช้ IFNULL เพื่อป้องกันกรณีที่ช่อง Model หรือ Details เป็นค่าว่าง (NULL) แล้วเกิดการซ้ำ
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) FROM products 
        WHERE category_id = ? 
        AND IFNULL(model, '') = IFNULL(?, '') 
        AND IFNULL(details, '') = IFNULL(?, '')
    ");
    $stmt_check->execute([$category_id, $model, $details]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "มีสินค้ารุ่นนี้และรายละเอียดนี้อยู่ในระบบแล้วครับ (ห้ามรายละเอียดซ้ำ)";
        header("Location: index.php?page=product_add");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, model, details) VALUES (?, ?, ?)");
        $stmt->execute([$category_id, $model, $details]);
        $_SESSION['success'] = "เพิ่มข้อมูลรุ่นสินค้าสำเร็จเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
header("Location: index.php?page=products");
exit;
?>