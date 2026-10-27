<?php

// ป้องกันคนไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $id = $_POST['id'];
    $province_id = $_POST['province_id'];
    $hospital_name = trim($_POST['hospital_name']);
    $department = trim($_POST['department']);
    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email'] ?? ''); // 🛑 เพิ่มการรับค่าอีเมล

    try {
        // 🛑 เพิ่ม email=? ลงไปในคำสั่งอัปเดต
        $sql = "UPDATE customers 
                SET province_id=?, hospital_name=?, department=?, customer_name=?, phone=?, email=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        // 🛑 เพิ่ม $email เข้าไปใน Array สำหรับ execute
        $stmt->execute([$province_id, $hospital_name, $department, $customer_name, $phone, $email, $id]);
        
        // เมื่อบันทึกสำเร็จ แจ้งเตือนและกลับไปหน้าข้อมูลลูกค้า (Customer List)
        $_SESSION['success'] = "อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว!";
        header("Location: index.php?page=customers"); 
        exit;
        
    } catch (PDOException $e) {
        // ถ้ามี Error คืนค่ากลับไปที่หน้าแก้ไขของลูกค้ารายนั้น
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=customer_edit&id=" . $id);
        exit;
    }
}
?>