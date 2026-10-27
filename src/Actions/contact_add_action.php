<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. รับค่าเงินรวมและการคํานวณหลัก
    $employee_id         = !empty($_POST['employee_id']) ? $_POST['employee_id'] : NULL;
    $customer_id         = !empty($_POST['customer_id']) ? $_POST['customer_id'] : NULL;
    $quotation_no        = !empty($_POST['quote_number']) ? trim($_POST['quote_number']) : NULL;
    $quotation_date      = !empty($_POST['quote_date']) ? $_POST['quote_date'] : NULL;
    $report_date         = !empty($_POST['report_date']) ? $_POST['report_date'] : NULL;
    $competitor          = !empty($_POST['competitor']) ? $_POST['competitor'] : NULL;
    $status              = !empty($_POST['job_status']) ? $_POST['job_status'] : 'Pending';
    $recent_activity     = !empty($_POST['recent_activity']) ? $_POST['recent_activity'] : NULL;
    $next_plan           = !empty($_POST['next_plan']) ? $_POST['next_plan'] : NULL;
    $issues              = !empty($_POST['problems']) ? $_POST['problems'] : NULL;
    $support_needed      = !empty($_POST['support_needed']) ? $_POST['support_needed'] : NULL;
    $expected_close_date = !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : NULL;

    // รับยอดสรุปเงินรวมหลัก
    $subtotal    = !empty($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.00;
    $discount    = !empty($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
    $vat         = !empty($_POST['vat']) ? floatval($_POST['vat']) : 0.00;
    $grand_total = !empty($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0.00;

    // รับอาเรย์รายการสินค้าจากตาราง HTML
    $product_ids = $_POST['product_id'] ?? [];
    $item_prices = $_POST['item_price'] ?? [];
    $item_qtys   = $_POST['item_qty'] ?? [];

    if (empty($product_ids)) {
        $_SESSION['error'] = "กรุณาเลือกและเพิ่มสินค้าลงตารางอย่างน้อย 1 รายการ";
        header("Location: index.php?page=contact_add"); exit;
    }

    // 🛑 [ใหม่] เช็คป้องกันเลขที่ใบเสนอราคาซ้ำ
    if (!empty($quotation_no) && $quotation_no !== '-') {
        $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE quotation_no = ?");
        $stmt_dup->execute([$quotation_no]);
        if ($stmt_dup->fetchColumn() > 0) {
            $_SESSION['error'] = "เลขที่ใบเสนอราคานี้ ($quotation_no) มีอยู่ในระบบแล้ว กรุณาตรวจสอบอีกครั้ง";
            header("Location: index.php?page=contact_add"); exit;
        }
    }

    try {
        // เริ่มระบบ Transaction ป้องกันข้อมูลบันทึกครึ่งๆ กลางๆ (ความปลอดภัยระดับสูง)
        $pdo->beginTransaction();

        // 2. บันทึกลงตารางหลัก contacts (ผูกยอดเงินรวมลงไปด้วย)
        $sql_main = "INSERT INTO contacts 
                (employee_id, customer_id, product_id, purchase_price, subtotal, discount, vat, grand_total, 
                 quotation_no, quotation_date, report_date, competitor, status, recent_activity, next_plan, issues, support_needed, expected_close_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // สำหรับฟิลด์เก่าดั้งเดิม (product_id, purchase_price) ให้หยิบตัวแรกมาเซฟไว้เพื่อไม่ให้ระบบเก่าพัง
        $first_prod_id = $product_ids[0];
        $first_price   = floatval($item_prices[0]);

        $stmt_main = $pdo->prepare($sql_main);
        $stmt_main->execute([
            $employee_id, $customer_id, $first_prod_id, $first_price, $subtotal, $discount, $vat, $grand_total,
            $quotation_no, $quotation_date, $report_date, $competitor, $status, $recent_activity, $next_plan, $issues, $support_needed, $expected_close_date
        ]);

        // ดึง ID ล่าสุดที่เพิ่งบันทึกไปสดๆ ร้อนๆ เพื่อนำไปผูกกับสินค้าตัวลูก
        $contact_id = $pdo->lastInsertId();

        // 3. วนลูปบันทึกรายการสินค้าทั้งหมดลงตารางแยก contact_items
        $sql_item = "INSERT INTO contact_items (contact_id, product_id, unit_price, quantity, total_price) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);

        foreach ($product_ids as $index => $prod_id) {
            $u_price   = floatval($item_prices[$index] ?? 0);
            $qty       = intval($item_qtys[$index] ?? 1);
            $row_total = $u_price * $qty;

            $stmt_item->execute([$contact_id, $prod_id, $u_price, $qty, $row_total]);
        }

        // ยืนยันบันทึกสำเร็จทั้งหมดพร้อมกัน
        $pdo->commit();
        $_SESSION['success'] = "บันทึกข้อมูลการเข้าพบลูกค้าและรายการสินค้าสำเร็จเรียบร้อยแล้ว!";
        header("Location: index.php?page=contacts"); exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); // ถอนข้อมูลคืนทั้งหมดหากเกิดข้อผิดพลาดฐานข้อมูลไม่พังแน่นอน
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
        header("Location: index.php?page=contact_add"); exit;
    }
}
?>