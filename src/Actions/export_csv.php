<?php

// ป้องกันคนไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

// 1. ตรวจสอบสิทธิ์การใช้งาน
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);
$current_user_id = $_SESSION['user_id'];

// 2. รับค่าตัวกรองทั้งหมด
$selected_year   = $_GET['year'] ?? '';
$selected_month  = $_GET['month'] ?? '';
$search_quote_no = trim($_GET['quote_no'] ?? '');
$selected_status = $_GET['job_status'] ?? '';
$selected_emp    = $is_admin ? ($_GET['employee_id'] ?? '') : $current_user_id;
$search_keyword  = trim($_GET['search'] ?? '');

// 📌 รับค่า "คอลัมน์ที่เปิดอยู่" จากหน้าเว็บ
$visible_cols_str = $_GET['visible_cols'] ?? '';
$visible_cols = $visible_cols_str !== '' ? explode(',', $visible_cols_str) : null;

// สร้างชื่อไฟล์
$filename_suffix = "";
if (!empty($selected_year)) $filename_suffix .= "_Y" . $selected_year;
if (!empty($selected_month)) $filename_suffix .= "_M" . $selected_month;
if (!empty($selected_emp)) $filename_suffix .= "_Emp" . $selected_emp;
if (!empty($search_quote_no)) $filename_suffix .= "_Quote" . $search_quote_no;

// ตั้งค่า Header แจ้ง Browser ให้รองรับการขึ้นบรรทัดใหม่ใน Cell ของ Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=CRM_Report' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.csv');
echo "\xEF\xBB\xBF"; 

$output = fopen('php://output', 'w');

// ==============================================
// 3. จัดการหัวตาราง (Headers)
// ==============================================
$master_headers = [
    'พนักงานผู้รับผิดชอบ', 'ว/ด/ป', 'จังหวัด', 'ชื่อโรงพยาบาล', 'แผนก', 'รายชื่อลูกค้า', 'เบอร์ติดต่อ', 
    'เลขที่ใบเสนอราคา', 'แบรนด์สินค้า', 'Model', 'Detail', 'ราคา (แยกรายการ)', 'คู่แข่ง', 
    'สถานะงาน', 'กิจกรรมล่าสุด', 'แผนถัดไป', 'ปัญหา/อุปสรรค', 'สิ่งที่ต้องการสนับสนุน', 
    'คาดว่าจะปิดได้ (วัน/เดือน/ปี)', 'วันที่รายงาน'
];

$export_headers = [];
if ($visible_cols !== null) {
    // เลือกเฉพาะหัวตารางที่หน้าจอเปิดไว้
    foreach ($visible_cols as $index) {
        if (isset($master_headers[$index])) {
            $export_headers[] = $master_headers[$index];
        }
    }
} else {
    $export_headers = $master_headers;
}
fputcsv($output, $export_headers);

// ==============================================
// 4. สร้างเงื่อนไขตัวกรองเข้า SQL
// ==============================================
$conditions = [];
$params = [];

if (!empty($selected_year)) { $conditions[] = "YEAR(ct.report_date) = ?"; $params[] = $selected_year; }
if (!empty($selected_month)) { $conditions[] = "MONTH(ct.report_date) = ?"; $params[] = $selected_month; }
if (!empty($selected_emp)) { $conditions[] = "ct.employee_id = ?"; $params[] = $selected_emp; }
if (!empty($search_quote_no)) { 
    $conditions[] = "ct.quotation_no LIKE ?"; 
    $params[] = '%' . $search_quote_no . '%'; 
}
if (!empty($selected_status)) { $conditions[] = "ct.status = ?"; $params[] = $selected_status; }

if (!empty($search_keyword)) {
    $search_columns = [
        'e.sr_no', 'e.fullname', 'c.hospital_name', 'c.customer_name', 'c.phone', 'c.department',
        'pr.name_in_thai', 'ct.quotation_no',
        'ct.competitor', 'ct.status', 'ct.recent_activity', 'ct.next_plan',
        'ct.issues', 'ct.support_needed'
    ];
    $search_conditions = [];
    foreach ($search_columns as $col) {
        $search_conditions[] = "$col LIKE ?";
        $params[] = '%' . $search_keyword . '%';
    }
    
    // ค้นหาลึกไปถึง แบรนด์/รุ่น
    $search_conditions[] = "ct.id IN (SELECT ci.contact_id FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE pc.category_name LIKE ? OR p.model LIKE ? OR p.details LIKE ?)";
    $params[] = '%' . $search_keyword . '%';
    $params[] = '%' . $search_keyword . '%';
    $params[] = '%' . $search_keyword . '%';
    
    $conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
}

$where_clause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// ==============================================
// 5. ดึงข้อมูลจากฐานข้อมูล
// ==============================================
// 🛑 เอา ' ฿' ออกจากการแสดงราคา
$sql = "
    SELECT ct.*, 
           e.sr_no AS emp_sr_no, e.fullname AS emp_name,
           c.hospital_name, c.customer_name, c.phone AS customer_phone, c.department,
           pr.name_in_thai AS province_name,
           COALESCE(
               (SELECT GROUP_CONCAT(IFNULL(REPLACE(REPLACE(pc.category_name, '\r', ''), '\n', ' '), 'ไม่มีแบรนด์') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE ci.contact_id = ct.id),
               (SELECT REPLACE(REPLACE(pc.category_name, '\r', ''), '\n', ' ') FROM products p LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE p.id = ct.product_id)
           ) AS category_name,
           COALESCE(
               (SELECT GROUP_CONCAT(IFNULL(REPLACE(REPLACE(p.model, '\r', ''), '\n', ' '), '-') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id WHERE ci.contact_id = ct.id),
               (SELECT REPLACE(REPLACE(p.model, '\r', ''), '\n', ' ') FROM products p WHERE p.id = ct.product_id)
           ) AS model,
           COALESCE(
               (SELECT GROUP_CONCAT(IFNULL(REPLACE(REPLACE(p.details, '\r', ''), '\n', ' '), '-') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id WHERE ci.contact_id = ct.id),
               (SELECT REPLACE(REPLACE(p.details, '\r', ''), '\n', ' ') FROM products p WHERE p.id = ct.product_id)
           ) AS product_detail,
           COALESCE(
               (SELECT GROUP_CONCAT(FORMAT(IFNULL(ci.total_price, 0), 2) ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci WHERE ci.contact_id = ct.id),
               FORMAT(IFNULL(ct.purchase_price, 0), 2)
           ) AS item_prices
    FROM contacts ct
    LEFT JOIN employees e ON ct.employee_id = e.id
    LEFT JOIN customers c ON ct.customer_id = c.id
    LEFT JOIN provinces pr ON c.province_id = pr.id 
    $where_clause
    ORDER BY ct.report_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// ==============================================
// 6. วนลูปนำข้อมูลใส่ CSV (ตามคอลัมน์ที่เปิด)
// ==============================================
$total_grand_price = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // บวกราคาสุทธิสะสมไว้สำหรับโชว์บรรทัดสุดท้าย
    $final_price = ($row['grand_total'] > 0) ? $row['grand_total'] : $row['purchase_price'];
    $total_grand_price += (float) $final_price;

    $emp_display = !empty($row['emp_sr_no']) ? $row['emp_sr_no'] . ' - ' . ($row['emp_name'] ?? '') : ($row['emp_name'] ?? '-');

    $master_row = [
        $emp_display,
        $row['quotation_date'] ? date('d/m/Y', strtotime($row['quotation_date'])) : '-',
        $row['province_name'] ?? 'ไม่ระบุ',
        $row['hospital_name'] ?? '-',
        $row['department'] ?? '-',
        $row['customer_name'] ?? '-',
        $row['customer_phone'] ?? '-',
        $row['quotation_no'] ?? '-',
        $row['category_name'] ?? 'ไม่มีแบรนด์', 
        $row['model'] ?? '-',
        $row['product_detail'] ?? '-',
        $row['item_prices'] ?? '-', 
        $row['competitor'] ?? '-',
        $row['status'] ?? '-',
        $row['recent_activity'] ?? '-',
        $row['next_plan'] ?? '-',
        $row['issues'] ?? '-', 
        $row['support_needed'] ?? '-',
        $row['expected_close_date'] && $row['expected_close_date'] != '0000-00-00' ? date('d/m/Y', strtotime($row['expected_close_date'])) : '-',
        $row['report_date'] ? date('d/m/Y', strtotime($row['report_date'])) : '-'
    ];

    $export_row = [];
    if ($visible_cols !== null) {
        foreach ($visible_cols as $index) {
            if (isset($master_row[$index])) {
                $export_row[] = $master_row[$index];
            }
        }
    } else {
        $export_row = $master_row;
    }
    fputcsv($output, $export_row);
}

// ==============================================
// 7. แถวสรุปยอดรวม "ราคาสุทธิทั้งหมด" ด้านล่างตาราง
// ==============================================
fputcsv($output, []); // เว้น 1 บรรทัด
$footer_row = array_fill(0, count($export_headers), ''); // สร้างแถวว่างๆ

// 🛑 เอา ' ฿' ออกจากการแสดงยอดรวมเช่นกัน
if ($visible_cols !== null) {
    $price_pos = array_search(11, $visible_cols);
    if ($price_pos !== false) {
        if ($price_pos > 0) {
            $footer_row[$price_pos - 1] = 'รวมราคาสุทธิทั้งหมดในหน้าค้นหา'; 
        } else {
            $footer_row[0] = 'รวมราคาสุทธิทั้งหมดในหน้าค้นหา';
        }
        $footer_row[$price_pos] = number_format($total_grand_price, 2);
    }
} else {
    $footer_row[10] = 'รวมราคาสุทธิทั้งหมดในหน้าค้นหา';
    $footer_row[11] = number_format($total_grand_price, 2);
}

// เขียนเฉพาะถ้ามีการแสดงคอลัมน์ราคา
if ($visible_cols === null || array_search(11, $visible_cols) !== false) {
    fputcsv($output, $footer_row);
}

fclose($output);
exit;
?>