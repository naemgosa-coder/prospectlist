<?php

// ดักจับตรวจสอบความปลอดภัยสิทธิ์ระดับแอดมินหรือผู้บริหารสูงสุด
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];

if (!isset($_SESSION['user_id']) || !in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหรือดำเนินการทำลายข้อมูลชุดนี้ครับ";
    header("Location: index.php?page=employees");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_ids'])) {
    $ids = $_POST['employee_ids'];

    if (is_array($ids) && count($ids) > 0) {
        try {
            // ดักความปลอดภัย: ป้องกันไม่ให้แอดมินเผลอกดลบรหัสไอดีที่ตัวเองกำลังใช้ล็อกอินอยู่!
            if (in_array($_SESSION['user_id'], $ids)) {
                // ตัดไอดีตัวเองออกจากชุดลบแบบกลุ่มเพื่อความปลอดภัยเซฟตี้ระบบ
                $ids = array_diff($ids, [$_SESSION['user_id']]);
                $self_deleted_alert = true;
            } else {
                $self_deleted_alert = false;
            }

            if (count($ids) > 0) {
                // ล้างค่าแปลงไอดีเป็นตัวเลข Integer เพื่อสกัด SQL Injection
                $clean_ids = array_map('intval', $ids);
                
                // สร้างกล่องสัญลักษณ์เครื่องหมายคำถาม (?,?,?) ตามจำนวนไอดีจริง
                $placeholders = rtrim(str_repeat('?,', count($clean_ids)), ',');
                
                $sql = "DELETE FROM employees WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($clean_ids);
                
                $msg = "ลบข้อมูลรายชื่อพนักงานสำเร็จจำนวน " . count($clean_ids) . " รายการ!";
                if ($self_deleted_alert) {
                    $msg .= " (หมายเหตุ: ระบบได้ข้ามการลบชื่อบัญชีของคุณโดยอัตโนมัติเพื่อป้องกันระบบล็อกอินค้าง)";
                }
                $_SESSION['success'] = $msg;
            } else {
                $_SESSION['error'] = "ไม่สามารถลบได้เนื่องจากรายการเดียวที่เลือกคือบัญชีของคุณที่กำลังใช้งานอยู่";
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถลบรายชื่อพนักงานบางคนได้ เนื่องจากมีประวัติถูกผูกใช้ในตารางดีลรายงานการติดต่อลูกค้าแล้ว: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "กรุณาเลือกรายชื่อพนักงานที่ต้องการดำเนินการลบ";
    }
}

header("Location: index.php?page=employees");
exit;
?>