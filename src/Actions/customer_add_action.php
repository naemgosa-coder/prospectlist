<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $province_id = $_POST['province_id'];
    $hospital_name = trim($_POST['hospital_name']);
    $department = trim($_POST['department']);
    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);

    // 🛑 [ใหม่] เช็คว่ามีลูกค้ารายนี้ (โรงพยาบาล+แผนก+ชื่อลูกค้า) อยู่ในระบบแล้วหรือยัง
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE hospital_name = ? AND department = ? AND customer_name = ?");
    $stmt_check->execute([$hospital_name, $department, $customer_name]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "มีข้อมูลลูกค้านี้ (โรงพยาบาล, แผนก, ชื่อลูกค้า) อยู่ในระบบแล้วครับ";
        header("Location: index.php?page=customer_add");
        exit;
    }

    try {
        $sql = "INSERT INTO customers (province_id, hospital_name, department, customer_name, phone) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$province_id, $hospital_name, $department, $customer_name, $phone]);

        $_SESSION['success'] = "เพิ่มข้อมูลลูกค้าสำเร็จ!";
        header("Location: index.php?page=customers");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=customer_add");
        exit;
    }
}
?>