<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contact_id = $_POST['contact_id'];
    $status = $_POST['job_status'];
    $recent_activity = trim($_POST['recent_activity']);
    $next_plan = trim($_POST['next_plan']) ?: NULL;
    $issues = trim($_POST['problems']) ?: NULL;
    $support_needed = trim($_POST['support_needed']) ?: NULL;
    $expected_close_date = !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : NULL;

    try {
        // 1. เริ่มต้นการทำงานแบบกลุ่ม (Transaction)
        $pdo->beginTransaction();

        // 2. อัปเดตข้อมูลล่าสุดทับลงในตารางหลัก (contacts)
        $sql_main = "UPDATE contacts SET 
                 status = ?, 
                 recent_activity = ?, 
                 next_plan = ?, 
                 issues = ?, 
                 support_needed = ?, 
                 expected_close_date = ? 
                 WHERE id = ?";
        $stmt_main = $pdo->prepare($sql_main);
        $stmt_main->execute([$status, $recent_activity, $next_plan, $issues, $support_needed, $expected_close_date, $contact_id]);

        // 3. เพิ่มประวัติใหม่ลงในตารางไทม์ไลน์ (contact_updates)
        $sql_history = "INSERT INTO contact_updates 
                    (contact_id, status, recent_activity, next_plan, issues, support_needed, expected_close_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([$contact_id, $status, $recent_activity, $next_plan, $issues, $support_needed, $expected_close_date]);

        // 4. บันทึกยืนยันทั้ง 2 คำสั่งลงฐานข้อมูล (Commit)
        $pdo->commit();

        $_SESSION['success'] = "อัปเดตความคืบหน้าสำเร็จ!";
        header("Location: index.php?page=contacts");
        exit;
    } catch (PDOException $e) {
        // 5. ถ้ามีอันไหนดพัง ให้ยกเลิกทั้งหมด (Rollback) ข้อมูลจะได้ไม่เพี้ยน
        $pdo->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: index.php?page=contact_update&id=" . $contact_id);
        exit;
    }
}
