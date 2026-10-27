<?php

function safe_convert_utf8($text) {
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['importCustomer'])) {
    $file = $_FILES['importCustomer']['tmp_name'];
    
    if (is_uploaded_file($file)) {
        ini_set('auto_detect_line_endings', TRUE);

        if (($handle = fopen($file, "r")) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $first_line = fgets($handle);
            if ($first_line !== false) {
                $comma_count = substr_count($first_line, ',');
                $semicolon_count = substr_count($first_line, ';');
                $delimiter = ($semicolon_count > $comma_count) ? ';' : ',';
                
                rewind($handle);
                if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
                fgetcsv($handle, 10000, $delimiter); 
            } else {
                $delimiter = ',';
            }

            $success_count = 0;
            $error_count = 0;
            $skipped_count = 0;
            
            $stmt_prov = $pdo->prepare("SELECT id FROM provinces WHERE TRIM(name_in_thai) = ? LIMIT 1");
            $stmt_insert = $pdo->prepare("INSERT INTO customers (province_id, hospital_name, department, customer_name, phone) VALUES (?, ?, ?, ?, ?)");
            
            // 🛑 คำสั่งเช็คข้อมูลซ้ำเตรียมไว้ใช้งานในลูป
            $stmt_check_dup = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE hospital_name = ? AND department = ? AND customer_name = ?");

            while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                if (empty($data[0]) && empty($data[1])) continue;

                $province_name  = isset($data[0]) ? safe_convert_utf8($data[0]) : '';
                $hospital_name  = isset($data[1]) ? safe_convert_utf8($data[1]) : '';
                $department     = isset($data[2]) ? safe_convert_utf8($data[2]) : '';
                $customer_name  = isset($data[3]) ? safe_convert_utf8($data[3]) : '';
                $phone          = isset($data[4]) ? safe_convert_utf8($data[4]) : '';

                // 🛑 [ใหม่] ถอดขีด (-) ช่องว่าง และวงเล็บออกจากเบอร์โทรศัพท์
                $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

                // เช็คว่ามีลูกค้ารายนี้ซ้ำอยู่แล้วหรือไม่ (ข้ามการ Insert ถ้ามีแล้ว)
                $stmt_check_dup->execute([$hospital_name, $department, $customer_name]);
                if ($stmt_check_dup->fetchColumn() > 0) {
                    $skipped_count++;
                    continue; 
                }

                $stmt_prov->execute([$province_name]);
                $prov_res = $stmt_prov->fetch();
                $province_id = $prov_res ? $prov_res['id'] : null;

                if ($province_id && !empty($hospital_name)) {
                    $stmt_insert->execute([$province_id, $hospital_name, $department, $customer_name, $phone]);
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            fclose($handle);
            
            $msg = "นำเข้าสำเร็จ {$success_count} รายการ";
            if ($skipped_count > 0) $msg .= ", ข้ามข้อมูลที่ซ้ำแล้ว {$skipped_count} รายการ";
            if ($error_count > 0) $msg .= ", ข้อมูลไม่ถูกต้อง {$error_count} รายการ";

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

header("Location: index.php?page=customer_add");
exit;
?>