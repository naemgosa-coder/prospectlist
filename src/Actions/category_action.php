<?php

$action = $_REQUEST['action'] ?? '';

try {
    if ($action == 'add') {
        $name = trim($_POST['category_name'] ?? '');
        
        // 🛑 ป้องกันการกดย้ำ (Double Submit) ด้วย Session
        $token = md5($name . time()); 
        if (isset($_SESSION['last_add_cat']) && $_SESSION['last_add_cat'] === $name) {
            // ถ้าพยายามเพิ่มชื่อเดิมซ้ำๆ ติดกัน ให้เด้งกลับไปเลยโดยไม่ต้องฟ้อง Error ก็ได้ (กันคนกดย้ำรัวๆ)
            header("Location: index.php?page=categories");
            exit;
        }

        // เช็คชื่อแบรนด์ซ้ำในฐานข้อมูล
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_name = ?");
        $stmt_check->execute([$name]);
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['error'] = "มีแบรนด์ / ชื่อสินค้านี้อยู่ในระบบแล้วครับ";
        } else {
            $pdo->prepare("INSERT INTO product_categories (category_name) VALUES (?)")->execute([$name]);
            $_SESSION['success'] = "เพิ่มแบรนด์ / ชื่อสินค้าสำเร็จเรียบร้อยแล้ว";
            
            // จดจำค่าที่พึ่งแอดไปล่าสุด เพื่อดักการกดย้ำ
            $_SESSION['last_add_cat'] = $name;
        }
    } 
    elseif ($action == 'edit') {
        $id = $_POST['id'];
        $name = trim($_POST['category_name'] ?? '');
        
        // เช็คชื่อแบรนด์ซ้ำ (ยกเว้นตัวมันเอง)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_name = ? AND id != ?");
        $stmt_check->execute([$name, $id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['error'] = "มีแบรนด์ / ชื่อสินค้านี้อยู่ในระบบแล้วครับ";
        } else {
            $pdo->prepare("UPDATE product_categories SET category_name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success'] = "อัปเดตข้อมูลแบรนด์ / ชื่อสินค้าสำเร็จ";
        }
    } 
    
    // ส่วนลบข้อมูล 1 รายการ
    elseif ($action == 'delete') {
        $id = $_GET['id'];
        
        // 1. เช็คก่อนว่ามีสินค้ารุ่นไหนใช้แบรนด์นี้อยู่หรือไม่
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_check->execute([$id]);
        $used_count = $stmt_check->fetchColumn();

        if ($used_count > 0) {
            $_SESSION['error'] = "ไม่สามารถลบได้! เนื่องจากมีสินค้าจำนวน $used_count รายการ กำลังใช้แบรนด์นี้อยู่ (กรุณาลบหรือย้ายแบรนด์ของสินค้านั้นก่อนครับ)";
        } else {
            $pdo->prepare("DELETE FROM product_categories WHERE id = ?")->execute([$id]);
            $_SESSION['success'] = "ลบแบรนด์ / ชื่อสินค้าสำเร็จ";
        }
    }
    
    // ส่วนลบข้อมูลหลายรายการ (Bulk Delete)
    elseif ($action == 'bulk_delete') {
        $ids = $_POST['category_ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $stmt_check = $pdo->prepare("SELECT category_id, COUNT(*) as qty FROM products WHERE category_id IN ($placeholders) GROUP BY category_id");
            $stmt_check->execute($ids);
            
            $used_cats = $stmt_check->fetchAll(PDO::FETCH_KEY_PAIR); 

            $ids_to_delete = [];
            $skipped_count = 0;

            foreach ($ids as $cat_id) {
                if (isset($used_cats[$cat_id]) && $used_cats[$cat_id] > 0) {
                    $skipped_count++; 
                } else {
                    $ids_to_delete[] = $cat_id; 
                }
            }

            if (count($ids_to_delete) > 0) {
                $del_placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
                $stmt_delete = $pdo->prepare("DELETE FROM product_categories WHERE id IN ($del_placeholders)");
                $stmt_delete->execute($ids_to_delete);
            }

            if ($skipped_count > 0 && count($ids_to_delete) > 0) {
                $_SESSION['success'] = "ลบสำเร็จ " . count($ids_to_delete) . " รายการ <br><small>(ข้าม $skipped_count รายการที่ไม่สามารถลบได้เพราะมีรุ่นสินค้าผูกอยู่)</small>";
            } elseif ($skipped_count > 0 && count($ids_to_delete) == 0) {
                $_SESSION['error'] = "ไม่สามารถลบได้เลย เนื่องจากแบรนด์ที่เลือกทั้งหมดมีรุ่นสินค้าผูกใช้งานอยู่ครับ";
            } else {
                $_SESSION['success'] = "ลบแบรนด์จำนวน " . count($ids_to_delete) . " รายการ สำเร็จเรียบร้อยแล้ว";
            }
        } else {
            $_SESSION['error'] = "กรุณาเลือกอย่างน้อย 1 รายการเพื่อลบครับ";
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header("Location: index.php?page=categories");
exit;
?>