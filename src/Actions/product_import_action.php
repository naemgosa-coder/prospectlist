<?php

// ป้องกันความปลอดภัยสิทธิ์เข้าใช้งานระดับแอดมิน
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!isset($_SESSION['user_id']) || !in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ดำเนินการนำเข้าข้อมูลระบบนี้ครับ";
    header("Location: index.php?page=products");
    exit;
}

// ฟังก์ชันสำหรับแปลงภาษาต่างดาวของภาษาไทยให้กลายเป็น UTF-8 สมบูรณ์แบบ
function safe_convert_utf8($text)
{
    if (empty($text)) return '';
    if (function_exists('mb_check_encoding') && @mb_check_encoding($text, 'UTF-8')) {
        return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($text));
    }
    $converted = false;
    if (function_exists('iconv')) {
        $converted = @iconv('TIS-620', 'UTF-8//IGNORE', $text);
    }
    if (!$converted && function_exists('mb_convert_encoding')) {
        try {
            $converted = mb_convert_encoding($text, 'UTF-8', 'TIS-620');
        } catch (\Throwable $e) {
            $converted = false;
        }
    }
    if ($converted) {
        return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($converted));
    }
    return trim($text);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['importFile'])) {
    $file = $_FILES['importFile']['tmp_name'];

    if (is_uploaded_file($file)) {
        ini_set('auto_detect_line_endings', TRUE);

        if (($handle = fopen($file, "r")) !== FALSE) {

            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            $first_line = fgets($handle);
            if ($first_line !== false) {
                $comma_count = substr_count($first_line, ',');
                $semicolon_count = substr_count($first_line, ';');
                $delimiter = ($semicolon_count > $comma_count) ? ';' : ',';

                rewind($handle);
                if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
                fgetcsv($handle, 10000, $delimiter); // ข้ามแถวหัวข้อ (Header)
            } else {
                $delimiter = ',';
            }

            $success_count = 0;
            $error_count = 0;
            $skipped_count = 0;

            // 🛑 เช็คเฉพาะแบรนด์ที่มีในระบบเท่านั้น ห้ามสร้างแบรนด์ใหม่เด็ดขาด
            $stmt_check_cat = $pdo->prepare("SELECT id FROM product_categories WHERE TRIM(category_name) = ? LIMIT 1");
            $stmt_product = $pdo->prepare("INSERT INTO products (category_id, model, details) VALUES (?, ?, ?)");

            // 🛑 [แก้ใหม่] เช็คป้องกันข้อมูลซ้ำเป๊ะๆ (ถ้า แบรนด์เดิม + รุ่นเดิม แต่ "รายละเอียดต่างกัน" จะยอมให้บันทึกเป็นรายการใหม่ทันที)
            $stmt_check_dup = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND model = ? AND details = ?");

            while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                if (empty($data[0]) && empty($data[1]) && empty($data[2])) continue;

                // โครงสร้าง CSV: 1. แบรนด์, 2. รุ่น, 3. รายละเอียด
                $cat_name = isset($data[0]) ? safe_convert_utf8($data[0]) : '';
                $model    = isset($data[1]) ? safe_convert_utf8($data[1]) : '';
                $details  = isset($data[2]) ? safe_convert_utf8($data[2]) : '';

                // บังคับว่าต้องกรอกทั้งแบรนด์และรุ่นในไฟล์ CSV
                if (empty($cat_name) || empty($model)) {
                    $error_count++;
                    continue; 
                }

                try {
                    // ค้นหา ID แบรนด์จากชื่อที่ส่งมา
                    $stmt_check_cat->execute([$cat_name]);
                    $category_id = $stmt_check_cat->fetchColumn();

                    // 🛑 ถ้าไม่พบแบรนด์ในระบบ ให้ตีเป็น Error ทันที
                    if (!$category_id) {
                        $error_count++;
                        continue;
                    }

                    // เช็คข้อมูลซ้ำแบบ 3 เงื่อนไข (ต้องเหมือนเป๊ะทั้ง 3 ช่อง ถึงจะเด้งข้าม)
                    $stmt_check_dup->execute([$category_id, $model, $details]);
                    if ($stmt_check_dup->fetchColumn() > 0) {
                        $skipped_count++;
                        continue;
                    }

                    // นำเข้าข้อมูลสินค้าใหม่ (อนุญาตให้ชื่อแบรนด์/รุ่น ซ้ำได้ถ้ารายละเอียดต่างกัน)
                    $stmt_product->execute([$category_id, $model, $details]);
                    $success_count++;
                } catch (PDOException $e) {
                    $error_count++;
                }
            }

            fclose($handle);

            $msg = "นำเข้าสำเร็จ {$success_count} รายการ";
            if ($skipped_count > 0) $msg .= ", ข้ามข้อมูลซ้ำ (แบรนด์, รุ่น, รายละเอียด เหมือนกันเป๊ะแล้ว) {$skipped_count} รายการ";
            if ($error_count > 0) $msg .= ", ข้อมูลไม่ครบ/ไม่มีแบรนด์นี้ในระบบ {$error_count} รายการ";

            if ($error_count > 0) {
                $_SESSION['error'] = $msg;
            } else {
                $_SESSION['success'] = $msg;
            }
        } else {
            $_SESSION['error'] = "ไม่สามารถอ่านข้อมูลในไฟล์ได้";
        }
    } else {
        $_SESSION['error'] = "กรุณาเลือกไฟล์ข้อมูลที่ถูกต้อง";
    }
}

header("Location: index.php?page=product_add");
exit;
?>