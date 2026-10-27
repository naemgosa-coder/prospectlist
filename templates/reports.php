<?php
// ==========================================
// 1. เช็คสิทธิ์การใช้งาน (แอดมินและผู้บริหาร มีสิทธิ์เท่ากัน)
// ==========================================
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);
$current_user_id = $_SESSION['user_id'];

// รับค่าตัวกรองจาก HTTP GET พารามิเตอร์
$selected_year   = $_GET['year'] ?? '';
$selected_month  = $_GET['month'] ?? '';
$search_quote_no = trim($_GET['quote_no'] ?? '');
$selected_status = $_GET['job_status'] ?? '';
$selected_emp    = $is_admin ? ($_GET['employee_id'] ?? '') : $current_user_id;

$year_stmt = $pdo->query("SELECT DISTINCT YEAR(report_date) AS report_year FROM contacts WHERE report_date IS NOT NULL ORDER BY report_year DESC");
$available_years = $year_stmt->fetchAll(PDO::FETCH_COLUMN);

$emp_stmt = $pdo->query("SELECT DISTINCT e.id, e.sr_no, e.fullname FROM contacts ct INNER JOIN employees e ON ct.employee_id = e.id ORDER BY e.fullname ASC");
$available_emps = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);

$available_statuses = [
    "0 ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว",
    "1 เริ่มทำเสนอราคา ต้องการเครื่อง ของบไปไม่ทราบผล",
    "2 มีงบแล้วหรือเลือกเราแล้ว",
    "3 มีงบแล้วและเลือกเราแล้ว (รอ)ขั้นตอนการสั่งซื้อหรือเปิดของ",
    "4 เรียกเซ็นสัญญาแล้วหรือรอส่งของ",
    "5 รอเก็บเงิน",
    "6 สิ้นสุดการขาย จบการขาย ปิดจ๊อบ"
];

$thai_months = [
    "01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน",
    "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม",
    "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"
];

// ==========================================
// 3. ประกอบเงื่อนไข SQL WHERE Clause
// ==========================================
$conditions = ["1=1"];
$params = [];

if (!empty($selected_year)) { $conditions[] = "YEAR(ct.report_date) = ?"; $params[] = intval($selected_year); }
if (!empty($selected_month)) { $conditions[] = "MONTH(ct.report_date) = ?"; $params[] = intval($selected_month); }
if (!empty($selected_emp)) { $conditions[] = "ct.employee_id = ?"; $params[] = intval($selected_emp); }
if (!empty($search_quote_no)) {
    $conditions[] = "ct.quotation_no LIKE ?";
    $params[] = '%' . $search_quote_no . '%';
}
if (!empty($selected_status)) { $conditions[] = "ct.status = ?"; $params[] = $selected_status; }

$where_clause = "WHERE " . implode(" AND ", $conditions);

// 🛑 ดึงราคาแยกทีละรายการ (item_prices) และบังคับ ORDER BY ci.id ASC ให้ทุกคอลัมน์เรียงตรงกัน 100%
$sql_report = "
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
               (SELECT GROUP_CONCAT(CONCAT(FORMAT(IFNULL(ci.total_price, 0), 2), ' ฿') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci WHERE ci.contact_id = ct.id),
               CONCAT(FORMAT(IFNULL(ct.purchase_price, 0), 2), ' ฿')
           ) AS item_prices
    FROM contacts ct 
    LEFT JOIN employees e ON ct.employee_id = e.id 
    LEFT JOIN customers c ON ct.customer_id = c.id 
    LEFT JOIN provinces pr ON c.province_id = pr.id 
    $where_clause 
    ORDER BY ct.report_date DESC, ct.id DESC";
$stmt_report = $pdo->prepare($sql_report); 
$stmt_report->execute($params); 
$reports = $stmt_report->fetchAll();
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .clickable-row { cursor: pointer; transition: background-color 0.2s; }
    .clickable-row:hover td { background-color: #f1f5f9 !important; }
    .won-row td { box-shadow: inset 0 0 0 9999px rgba(47, 165, 114, 0.04); border-top: 1.5px solid #2fa572 !important; border-bottom: 1.5px solid #2fa572 !important; }
    .won-row td:first-child { border-left: 4.5px solid #2fa572 !important; }
    .won-row td:last-child { border-right: 1.5px solid #2fa572 !important; }
    .won-row:hover td { background-color: rgba(47, 165, 114, 0.08) !important; }
    .lost-row td { box-shadow: inset 0 0 0 9999px rgba(193, 70, 106, 0.04); border-top: 1.5px solid #c1466a !important; border-bottom: 1.5px solid #c1466a !important; }
    .lost-row td:first-child { border-left: 4.5px solid #c1466a !important; }
    .lost-row td:last-child { border-right: 1.5px solid #c1466a !important; }
    .lost-row:hover td { background-color: rgba(193, 70, 106, 0.08) !important; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<!-- Header -->
<div style="margin-bottom:22px">
    <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
        <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-table"></use></svg> 
        รายงานการเข้าพบ
    </div>
    <div style="font-size:13.5px;color:#9a828a;margin-top:3px">รายงานแบบเต็มหน้า พร้อมตัวกรองและเลือกคอลัมน์</div>
</div>

<!-- FILTER BAR -->
<div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:24px">
    <form action="index.php" method="GET" id="reportFilterForm" class="m-0 d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="page" value="reports">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px" class="me-2">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-filter"></use></svg> กรองรายการแสดงผล:
        </span>

        <select name="year" class="form-select form-select-sm" style="width: auto; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#6b5760; font-size:12.5px;" onchange="this.form.submit()">
            <option value="">-- ทุกปี --</option>
            <?php foreach ($available_years as $yr): ?>
                <option value="<?= $yr ?>" <?= $selected_year == $yr ? 'selected' : '' ?>>ปี <?= $yr ?></option>
            <?php endforeach; ?>
        </select>

        <select name="month" class="form-select form-select-sm" style="width: auto; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#6b5760; font-size:12.5px;" onchange="this.form.submit()">
            <option value="">-- ทุกเดือน --</option>
            <?php foreach ($thai_months as $m_num => $m_name): ?>
                <option value="<?= $m_num ?>" <?= $selected_month == $m_num ? 'selected' : '' ?>><?= $m_name ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($is_admin): ?>
            <select name="employee_id" class="form-select form-select-sm" style="width: auto; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#6b5760; font-size:12.5px;" onchange="this.form.submit()">
                <option value="">-- พนักงานทั้งหมด --</option>
                <?php foreach ($available_emps as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $selected_emp == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['sr_no'] . ' - ' . $emp['fullname']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <select name="job_status" class="form-select form-select-sm" style="width: auto; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#6b5760; font-size:12.5px;" onchange="this.form.submit()">
            <option value="">-- สถานะงานทั้งหมด --</option>
            <?php foreach ($available_statuses as $st): ?>
                <option value="<?= $st ?>" <?= $selected_status == $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="quote_no" class="form-control form-control-sm" style="width: auto; flex:1; min-width: 140px; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#4a3b40; font-size:12.5px;" placeholder="เลขที่ใบเสนอราคา..." value="<?= htmlspecialchars($search_quote_no) ?>">

        <button type="submit" style="font-size:12.5px;padding:6px 16px;border-radius:10px;background:#c1466a;color:#fff;font-weight:600;border:none;display:flex;align-items:center;gap:6px">
            <svg width="14" height="14" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-search"></use></svg> ค้นหา
        </button>

        <?php if (!empty($selected_year) || !empty($selected_month) || ($is_admin && !empty($selected_emp)) || !empty($search_quote_no) || !empty($selected_status)): ?>
            <a href="?page=reports" class="btn btn-outline-secondary btn-sm" style="border-radius:10px; font-size: 12.5px; padding:6px 12px;">ล้างค่า</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABLE CARD -->
<div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:24px">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div style="font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="18" height="18" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-table"></use></svg> ผลการค้นหา
        </div>
        <a id="exportBtn" href="index.php?route=export_csv&year=<?= urlencode($selected_year) ?>&month=<?= urlencode($selected_month) ?>&employee_id=<?= urlencode($selected_emp) ?>&quote_no=<?= urlencode($search_quote_no) ?>&job_status=<?= urlencode($selected_status) ?>" style="font-size:12.5px;padding:8px 14px;border-radius:10px;background:#2fa572;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-download"></use></svg> Export ตามที่กรอง
        </a>
    </div>

    <!-- COLUMN TOGGLER (เปลี่ยน Checkbox ให้เป็นปุ่มแคปซูล) -->
    <div style="background:#fdfbfc; border:1px solid #f4e6ec; border-radius:12px; padding:12px; margin-bottom: 16px;">
        <div style="font-size:12.5px; color:#8a7078; font-weight:600; margin-bottom:10px;"><i class="fa-solid fa-eye me-1"></i> เลือกซ่อน/แสดงคอลัมน์:</div>
        <div id="columnToggleContainer" class="d-flex flex-wrap gap-2 no-scrollbar" style="max-height: 100px; overflow-y:auto;"></div>
    </div>

    <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
        <table id="reportTable" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12.5px; min-width: 1200px;">
            <thead>
                <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">พนักงานผู้รับผิดชอบ</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">ว/ด/ป (เสนอราคา)</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">จังหวัด</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">ชื่อโรงพยาบาล</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">แผนก</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">รายชื่อลูกค้า</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">เบอร์ติดต่อ</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">เลขที่ใบเสนอราคา</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">แบรนด์สินค้า</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">รุ่น</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">รายละเอียด</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap;text-align:right">ราคา (แยกรายการ)</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">วันที่รายงาน</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">คู่แข่ง</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">สถานะงาน</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">กิจกรรมล่าสุด</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">แผนถัดไป</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">ปัญหา/อุปสรรค</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">สิ่งที่ต้องการสนับสนุน</th>
                    <th style="padding:11px 12px;font-weight:600;white-space:nowrap">คาดว่าจะปิดได้</th>
                </tr>
            </thead>
            <tbody style="vertical-align:top;">
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $row): 
                        $is_won = (strpos($row['status'], '6') === 0 || strpos($row['status'], 'Closed Won') !== false);
                        $is_lost = (strpos($row['status'], '0') === 0 || strpos($row['status'], 'Closed Lost') !== false);
                        
                        $is_closed = $is_won || $is_lost;
                        $row_link = $is_closed ? "?page=contact_history&id=" . $row['id'] : "?page=contact_update&id=" . $row['id'];
                        
                        $row_class = 'clickable-row';
                        if ($is_won) $row_class .= ' won-row';
                        if ($is_lost) $row_class .= ' lost-row';
                    ?>
                        <tr class="<?= $row_class ?>" data-href="<?= $row_link ?>">
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;"><?= htmlspecialchars(($row['emp_sr_no'] ?? '') . ' - ' . ($row['emp_name'] ?? '-')) ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-center"><?= htmlspecialchars($row['quotation_date'] ? date('d/m/Y', strtotime($row['quotation_date'])) : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-center"><?= htmlspecialchars($row['province_name'] ?? 'ไม่ระบุ') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="fw-bold"><?= htmlspecialchars($row['hospital_name'] ?? '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;"><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;"><?= htmlspecialchars($row['customer_name'] ?? '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-center"><?= htmlspecialchars($row['customer_phone'] ?? '-') ?></td>
                            
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;"><span style="color:#3a5a8a;font-weight:600"><?= htmlspecialchars($row['quotation_no'] ?? '-') ?></span></td>
                            <!-- ลบช่องว่าง/การขึ้นบรรทัดใหม่จาก HTML ออกไปให้หมด เพื่อความเป๊ะ -->
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2; white-space: pre-line;" class="fw-bold text-secondary"><?= trim(htmlspecialchars($row['category_name'] ?? 'ไม่มีแบรนด์')) ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2; white-space: pre-line;" class="text-secondary"><?= trim(htmlspecialchars($row['model'] ?? '-')) ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" title="รายละเอียดเต็ม"><div style="max-height: 80px; overflow-y: auto; white-space: pre-line; color:#6b5760;"><?= trim(htmlspecialchars($row['product_detail'] ?? '-')) ?></div></td>
                            
                            <!-- 🛑 แสดงราคาแยกทีละรายการและขึ้นบรรทัดใหม่ -->
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;text-align:right;font-weight:700;white-space:pre-line;color:#c1466a;"><?= trim($row['item_prices'] ?? '-') ?></td>

                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-center text-muted"><?= htmlspecialchars($row['report_date'] ? date('d/m/Y', strtotime($row['report_date'])) : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;"><?= htmlspecialchars($row['competitor'] ?? '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;max-width:180px">
                                <?php
                                $badgeClass = 'bg-light text-secondary border';
                                if (strpos($row['status'], '1') === 0 || strpos($row['status'], '2') === 0) $badgeClass = 'bg-pastel-warning text-dark';
                                if (strpos($row['status'], '3') === 0 || strpos($row['status'], '4') === 0 || strpos($row['status'], '5') === 0) $badgeClass = 'bg-pastel-primary text-primary';
                                if ($is_won) $badgeClass = 'bg-pastel-success text-success';
                                if ($is_lost) $badgeClass = 'bg-pastel-danger text-danger';
                                ?>
                                <span class="badge <?= $badgeClass ?> text-wrap" style="line-height:1.4; padding:5px 8px; font-weight:600; text-align:left;"><?= htmlspecialchars($row['status']) ?></span>
                            </td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" title="<?= htmlspecialchars($row['recent_activity'] ?? '') ?>"><?= htmlspecialchars($row['recent_activity'] ? mb_substr($row['recent_activity'], 0, 20) . '...' : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" title="<?= htmlspecialchars($row['next_plan'] ?? '') ?>"><?= htmlspecialchars($row['next_plan'] ? mb_substr($row['next_plan'], 0, 20) . '...' : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-danger" title="<?= htmlspecialchars($row['issues'] ?? '') ?>"><?= htmlspecialchars($row['issues'] ? mb_substr($row['issues'], 0, 20) . '...' : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-success" title="<?= htmlspecialchars($row['support_needed'] ?? '') ?>"><?= htmlspecialchars($row['support_needed'] ? mb_substr($row['support_needed'], 0, 20) . '...' : '-') ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" class="text-center text-danger fw-bold"><?= !empty($row['expected_close_date']) && $row['expected_close_date'] != '0000-00-00' ? date('d/m/Y', strtotime($row['expected_close_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#reportTable').DataTable({
			"stateSave": true,
            "language": {
                "sProcessing": "กำลังดำเนินการ...", "sLengthMenu": "แสดง _MENU_ รายการ", "sZeroRecords": "ไม่พบข้อมูล",
                "sEmptyTable": "ไม่มีข้อมูลรายงานตามตัวกรองที่เลือก", "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ", "sSearch": "", "searchPlaceholder": "ค้นหาด่วนในตาราง...",
                "oPaginate": { "sFirst": "หน้าแรก", "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป", "sLast": "หน้าสุดท้าย" }
            },
            "order": [[ 19, "desc" ]], 
            
            // ซ่อนคอลัมน์ที่ไม่จำเป็นต้องโชว์แต่แรก
            "columnDefs": [{ 
                "targets": [0, 1, 4, 6, 13, 15, 16, 17, 18], 
                "visible": false 
            }]
        });

        $('#reportTable tbody').on('click', 'tr.clickable-row', function() {
            var link = $(this).attr('data-href');
            if (link) { window.location.href = link; }
        });

        // 📌 [ใหม่!] ส่งค่าคำค้นหาจากช่อง DataTables ให้ปุ่ม Export
        $('#exportBtn').on('click', function(e) {
            e.preventDefault();
            var dtSearch = table.search(); // ดึงคำค้นหาที่พิมพ์ในช่อง "ค้นหาด่วน"
            var href = $(this).attr('href');
            
            // หาว่ามีคอลัมน์ไหนกำลังแสดงผลอยู่บ้าง
            var visibleCols = [];
            table.columns().every(function(index) {
                if (this.visible()) {
                    visibleCols.push(index);
                }
            });

            // ถอดพารามิเตอร์ search อันเก่าออกก่อน (กันมันซ้อนกัน)
            href = href.split('&search=')[0].split('&visible_cols=')[0]; 
            
            if (dtSearch) {
                href += '&search=' + encodeURIComponent(dtSearch);
            }
            if (visibleCols.length > 0) {
                href += '&visible_cols=' + visibleCols.join(',');
            }
            window.location.href = href; // สั่งดาวน์โหลด
        });

        // สร้างปุ่มซ่อน/แสดงคอลัมน์แบบแคปซูล
        table.columns().every(function(index) {
            var col = this;
            var title = $(col.header()).text().replace(' ↕', '');
            
            var bgStyle = col.visible() 
                ? 'background:#c1466a; color:#fff; border-color:#c1466a;' 
                : 'background:#fdfbfc; color:#6b5760; border-color:#eddbe1;';

            var html = `<span class="toggle-vis cursor-pointer d-inline-block" data-column="${index}" 
                              style="padding:6px 14px; border-radius:20px; font-size:12px; border:1px solid transparent; ${bgStyle} transition:all 0.2s;">
                              ${title}
                        </span>`;
            $('#columnToggleContainer').append(html);
        });

        // กดปุ่มแคปซูลเพื่อซ่อน/แสดงคอลัมน์
        $('.toggle-vis').on('click', function(e) {
            var columnIdx = $(this).attr('data-column');
            var column = table.column(columnIdx);
            
            column.visible(!column.visible());
            
            if(column.visible()){
                $(this).attr('style', 'padding:6px 14px; border-radius:20px; font-size:12px; border:1px solid transparent; background:#c1466a; color:#fff; border-color:#c1466a; transition:all 0.2s;');
            } else {
                $(this).attr('style', 'padding:6px 14px; border-radius:20px; font-size:12px; border:1px solid transparent; background:#fdfbfc; color:#6b5760; border-color:#eddbe1; transition:all 0.2s;');
            }
        });
    });
</script>