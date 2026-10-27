<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าข้อมูลหลัก
    $id = $_POST['id'];
    $employee_id = $_POST['employee_id'] ?? $_SESSION['user_id'];
    $customer_id = $_POST['customer_id'];
    $quote_date = !empty($_POST['quote_date']) ? $_POST['quote_date'] : NULL;
    $quote_number = $_POST['quote_number'] ?? '';
    
    // 2. รับค่าเรื่องเงิน (ถ้าไม่มีให้เป็น 0)
    $subtotal = $_POST['subtotal'] ?? 0;
    $discount = $_POST['discount'] ?? 0;
    $vat = $_POST['vat'] ?? 0;
    $grand_total = $_POST['grand_total'] ?? 0;

    // 3. รับค่าการติดตามงาน
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    $competitor = $_POST['competitor'] ?? '';
    $job_status = $_POST['job_status'] ?? '';
    $recent_activity = trim($_POST['recent_activity'] ?? '');
    $next_plan = trim($_POST['next_plan'] ?? '');
    $problems = trim($_POST['problems'] ?? '');
    $support_needed = trim($_POST['support_needed'] ?? '');
    $expected_close_date = !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : NULL;

    // 4. รับค่าอาร์เรย์รายการสินค้า (ตะกร้าสินค้า)
    $product_ids = $_POST['product_id'] ?? [];
    $item_prices = $_POST['item_price'] ?? [];
    $item_qtys = $_POST['item_qty'] ?? [];

    try {
        // เริ่มต้น Transaction ป้องกันข้อมูลบันทึกไม่ครบ
        $pdo->beginTransaction();

        //  5. อัปเดตข้อมูลตารางหลัก (contacts)
        // หมายเหตุ: บังคับใส่ product_id ตัวแรกไว้เผื่อระบบเก่าเรียกใช้ (กันบั๊ก)
        $first_product_id = !empty($product_ids) ? $product_ids[0] : NULL;

        $sql_main = "UPDATE contacts SET 
            employee_id = ?, customer_id = ?, quotation_date = ?, quotation_no = ?,
            subtotal = ?, discount = ?, vat = ?, grand_total = ?, purchase_price = ?,
            report_date = ?, competitor = ?, status = ?, recent_activity = ?, next_plan = ?,
            issues = ?, support_needed = ?, expected_close_date = ?, product_id = ?
            WHERE id = ?";
        
        $stmt_main = $pdo->prepare($sql_main);
        $stmt_main->execute([
            $employee_id, $customer_id, $quote_date, $quote_number,
            $subtotal, $discount, $vat, $grand_total, $grand_total, // ใส่ grand_total ลง purchase_price ด้วยเผื่อไว้
            $report_date, $competitor, $job_status, $recent_activity, $next_plan,
            $problems, $support_needed, $expected_close_date, $first_product_id,
            $id
        ]);

        //  6. จัดการรายการสินค้า (contact_items)
        // 6.1 ลบสินค้าเก่าในบิลนี้ทิ้งให้หมดก่อน
        $stmt_del = $pdo->prepare("DELETE FROM contact_items WHERE contact_id = ?");
        $stmt_del->execute([$id]);

        // 6.2 วนลูปบันทึกสินค้าชุดใหม่เข้าไปแทน
        if (!empty($product_ids)) {
            $sql_item = "INSERT INTO contact_items (contact_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $pdo->prepare($sql_item);

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = $product_ids[$i];
                $qty = $item_qtys[$i] ?? 1;
                $price = $item_prices[$i] ?? 0;
                $total = $qty * $price;

                $stmt_item->execute([$id, $pid, $qty, $price, $total]);
            }
        }

        //  7. บันทึกประวัติการอัปเดตลงตาราง Timeline (contact_updates)
        $sql_history = "INSERT INTO contact_updates (contact_id, status, recent_activity, next_plan, issues, support_needed, expected_close_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([
            $id, $job_status, $recent_activity, $next_plan, $problems, $support_needed, $expected_close_date
        ]);

        // ยืนยันการบันทึกข้อมูล
        $pdo->commit();

        $_SESSION['success'] = "แก้ไขข้อมูลใบเสนอราคาและรายการสินค้าเรียบร้อยแล้ว!";
        header("Location: index.php?page=contacts");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); // ถ้ายกเลิกกลางคัน ให้ย้อนกลับข้อมูลทั้งหมด
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=contact_edit&id=" . $id);
        exit;
    }
}
?>