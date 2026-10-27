<?php
// ==========================================
// 1. เช็คสิทธิ์ (แอดมินและผู้บริหาร มีสิทธิ์เท่ากัน)
// ==========================================
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);
$current_user_id = $_SESSION['user_id'];

// ==========================================
// 2. รับค่าตัวกรองจาก HTTP GET พารามิเตอร์
// ==========================================
$selected_year   = $_GET['year'] ?? '';
$selected_month  = $_GET['month'] ?? '';
$search_quote_no = trim($_GET['quote_no'] ?? '');
$selected_status = $_GET['job_status'] ?? '';

$selected_emp = $is_admin ? ($_GET['employee_id'] ?? '') : $current_user_id;

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
// 3. ดึงข้อมูลตัวเลขสรุป (KPIs)
// ==========================================
if ($is_admin) {
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_contacts = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $closed_won = $pdo->query("SELECT COUNT(*) FROM contacts WHERE status LIKE '6%' OR status = 'Closed Won'")->fetchColumn();
} else {
    $stmt_cust = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM contacts WHERE employee_id = ?");
    $stmt_cust->execute([$current_user_id]);
    $total_customers = $stmt_cust->fetchColumn();

    $total_products = 0;

    $stmt_ct = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE employee_id = ?");
    $stmt_ct->execute([$current_user_id]);
    $total_contacts = $stmt_ct->fetchColumn();

    $stmt_won = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE (status LIKE '6%' OR status = 'Closed Won') AND employee_id = ?");
    $stmt_won->execute([$current_user_id]);
    $closed_won = $stmt_won->fetchColumn();
}

// ==========================================
// 4. สร้างเงื่อนไขตัวกรอง (Filters)
// ==========================================
$cond_recent = ["1=1"]; $cond_upcoming = ["ct.expected_close_date >= CURDATE()", "ct.status NOT LIKE '6%'", "ct.status NOT LIKE '0%'"]; $cond_report = ["1=1"];
$params_recent = []; $params_upcoming = []; $params_report = [];

if (!empty($selected_year)) {
    $cond_recent[] = "YEAR(cu.created_at) = ?"; $cond_upcoming[] = "YEAR(ct.expected_close_date) = ?"; $cond_report[] = "YEAR(ct.report_date) = ?";
    $params_recent[] = $selected_year; $params_upcoming[] = $selected_year; $params_report[] = $selected_year;
}
if (!empty($selected_month)) {
    $cond_recent[] = "MONTH(cu.created_at) = ?"; $cond_upcoming[] = "MONTH(ct.expected_close_date) = ?"; $cond_report[] = "MONTH(ct.report_date) = ?";
    $params_recent[] = $selected_month; $params_upcoming[] = $selected_month; $params_report[] = $selected_month;
}
if (!empty($selected_emp)) {
    $cond_recent[] = "ct.employee_id = ?"; $cond_upcoming[] = "ct.employee_id = ?"; $cond_report[] = "ct.employee_id = ?";
    $params_recent[] = $selected_emp; $params_upcoming[] = $selected_emp; $params_report[] = $selected_emp;
}
if (!empty($search_quote_no)) {
    $cond_recent[] = "ct.quotation_no LIKE ?"; $cond_upcoming[] = "ct.quotation_no LIKE ?"; $cond_report[] = "ct.quotation_no LIKE ?";
    $term = '%' . $search_quote_no . '%';
    $params_recent[] = $term; $params_upcoming[] = $term; $params_report[] = $term;
}
if (!empty($selected_status)) {
    $cond_recent[] = "ct.status = ?"; $cond_upcoming[] = "ct.status = ?"; $cond_report[] = "ct.status = ?";
    $params_recent[] = $selected_status; $params_upcoming[] = $selected_status; $params_report[] = $selected_status;
}

// ==========================================
// 5. ดึงข้อมูล 3 ส่วน
// ==========================================
$sql_recent = "SELECT cu.recent_activity, cu.created_at, cu.status, c.hospital_name, e.fullname AS emp_name
    FROM contact_updates cu JOIN contacts ct ON cu.contact_id = ct.id JOIN customers c ON ct.customer_id = c.id JOIN employees e ON ct.employee_id = e.id
    WHERE " . implode(" AND ", $cond_recent) . " ORDER BY cu.created_at DESC LIMIT 5";
$stmt_recent = $pdo->prepare($sql_recent); $stmt_recent->execute($params_recent);
$recent_updates = $stmt_recent->fetchAll();

$sql_upcoming = "SELECT ct.id, c.hospital_name, ct.expected_close_date, ct.status, e.fullname AS emp_name
    FROM contacts ct JOIN customers c ON ct.customer_id = c.id JOIN employees e ON ct.employee_id = e.id
    WHERE " . implode(" AND ", $cond_upcoming) . " ORDER BY ct.expected_close_date ASC LIMIT 5";
$stmt_upcoming = $pdo->prepare($sql_upcoming); $stmt_upcoming->execute($params_upcoming);
$upcoming_deals = $stmt_upcoming->fetchAll();

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
    WHERE " . implode(" AND ", $cond_report) . " ORDER BY ct.report_date DESC, ct.id DESC";
$stmt_report = $pdo->prepare($sql_report); 
$stmt_report->execute($params_report);
$reports = $stmt_report->fetchAll();
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .clickable-row td { cursor: pointer; }
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
        <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-chart"></use></svg> 
        ภาพรวมระบบ (Dashboard)
    </div>
    <div style="font-size:13.5px;color:#9a828a;margin-top:3px">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['fullname'] ?? 'ผู้ใช้งาน') ?></div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <?php $kpi_col = $is_admin ? 'col-md-3 col-sm-6' : 'col-md-4 col-sm-6'; ?>
    
    <div class="<?= $kpi_col ?> mb-3">
        <div style="background:linear-gradient(150deg,#3f74c4,#3a63aa);color:#fff;border-radius:18px;padding:18px 20px;box-shadow:0 8px 20px rgba(58,90,150,.22);height:100%;display:flex;flex-direction:column;justify-content:space-between;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <div style="font-size:12.5px;opacity:.92"><?= $is_admin ? 'ลูกค้า / โรงพยาบาล' : 'ลูกค้าที่ดูแล' ?></div>
                    <div style="font-size:36px;font-weight:700;line-height:1.1;margin:2px 0"><?= number_format($total_customers) ?></div>
                </div>
                <span style="font-size:24px;opacity:.8"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-building"></use></svg></span>
            </div>
            <a href="?page=customers" style="font-size:12px;color:#fff;opacity:.9;text-decoration:none;border-top:1px solid rgba(255,255,255,.25);padding-top:8px;margin-top:12px;display:block">ดูรายละเอียด →</a>
        </div>
    </div>

    <?php if ($is_admin): ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <div style="background:linear-gradient(150deg,#38b6cf,#2f9fb8);color:#fff;border-radius:18px;padding:18px 20px;box-shadow:0 8px 20px rgba(47,159,184,.22);height:100%;display:flex;flex-direction:column;justify-content:space-between;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="font-size:12.5px;opacity:.92">สินค้าในระบบ</div>
                        <div style="font-size:36px;font-weight:700;line-height:1.1;margin:2px 0"><?= number_format($total_products) ?></div>
                    </div>
                    <span style="font-size:24px;opacity:.8"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-box"></use></svg></span>
                </div>
                <a href="?page=products" style="font-size:12px;color:#fff;opacity:.9;text-decoration:none;border-top:1px solid rgba(255,255,255,.25);padding-top:8px;margin-top:12px;display:block">ดูรายละเอียด →</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?= $kpi_col ?> mb-3">
        <div style="background:linear-gradient(150deg,#f0b429,#e09a1f);color:#fff;border-radius:18px;padding:18px 20px;box-shadow:0 8px 20px rgba(224,154,31,.22);height:100%;display:flex;flex-direction:column;justify-content:space-between;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <div style="font-size:12.5px;opacity:.95">ดีล / การเข้าพบทั้งหมด</div>
                    <div style="font-size:36px;font-weight:700;line-height:1.1;margin:2px 0"><?= number_format($total_contacts) ?></div>
                </div>
                <span style="font-size:24px;opacity:.85"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-users"></use></svg></span>
            </div>
            <a href="?page=contacts" style="font-size:12px;color:#fff;opacity:.95;text-decoration:none;border-top:1px solid rgba(255,255,255,.3);padding-top:8px;margin-top:12px;display:block">ดูรายละเอียด →</a>
        </div>
    </div>

    <div class="<?= $kpi_col ?> mb-3">
        <div style="background:linear-gradient(150deg,#2fa572,#268a5f);color:#fff;border-radius:18px;padding:18px 20px;box-shadow:0 8px 20px rgba(38,138,95,.22);height:100%;display:flex;flex-direction:column;justify-content:space-between;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <div style="font-size:12.5px;opacity:.92">ปิดการขายสำเร็จ</div>
                    <div style="font-size:36px;font-weight:700;line-height:1.1;margin:2px 0"><?= number_format($closed_won) ?></div>
                </div>
                <span style="font-size:24px;opacity:.8"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-award"></use></svg></span>
            </div>
            <a href="?page=contacts" style="font-size:12px;color:#fff;opacity:.9;text-decoration:none;border-top:1px solid rgba(255,255,255,.25);padding-top:8px;margin-top:12px;display:block">ดูรายละเอียด →</a>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:24px">
    <form action="index.php" method="GET" id="dashboardFilterForm" class="m-0 d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="page" value="dashboard">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px" class="me-2">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-filter"></use></svg> กรองรายการ:
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
            <a href="?page=dashboard" class="btn btn-outline-secondary btn-sm" style="border-radius:10px; font-size: 12.5px; padding:6px 12px;">ล้างค่า</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABLE CARD -->
<div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:24px">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div style="font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="18" height="18" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-table"></use></svg> สรุปรายงานการพบลูกค้า
        </div>
        <a id="exportBtn" href="index.php?route=export_csv&year=<?= urlencode($selected_year) ?>&month=<?= urlencode($selected_month) ?>&employee_id=<?= urlencode($selected_emp) ?>&job_status=<?= urlencode($selected_status) ?>&quote_no=<?= urlencode($search_quote_no) ?>" style="font-size:12.5px;padding:8px 14px;border-radius:10px;background:#2fa572;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
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
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2; white-space: pre-line;" class="fw-bold text-secondary"><?= trim(htmlspecialchars($row['category_name'] ?? 'ไม่มีแบรนด์')) ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2; white-space: pre-line;" class="text-secondary"><?= trim(htmlspecialchars($row['model'] ?? '-')) ?></td>
                            <td style="padding:11px 12px;border-top:1px solid #f4ece2;" title="รายละเอียดเต็ม"><div style="max-height: 80px; overflow-y: auto; white-space: pre-line; color:#6b5760;"><?= trim(htmlspecialchars($row['product_detail'] ?? '-')) ?></div></td>
                            
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

<!-- BOTTOM WIDGETS -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08);height:100%;">
            <div style="font-size:14.5px;font-weight:600;margin-bottom:14px;color:#c1466a">
                <svg width="16" height="16" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-clock"></use></svg> งานที่ใกล้ถึงกำหนดปิดการขาย
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php if (count($upcoming_deals) > 0): ?>
                    <?php foreach ($upcoming_deals as $deal): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;background:#fdf5f7;border-radius:12px;padding:11px 14px">
                            <div>
                                <a href="?page=contact_update&id=<?= $deal['id'] ?>" style="font-size:13px;font-weight:600;text-decoration:none;color:#3a5a8a;"><?= htmlspecialchars($deal['hospital_name']) ?></a>
                                <div style="font-size:11px;color:#9a828a"><?= htmlspecialchars($deal['emp_name']) ?> · <?= htmlspecialchars(mb_substr($deal['status'], 0, 35)) ?>...</div>
                            </div>
                            <span style="font-size:12px;color:#c1466a;font-weight:700"><?= date('d/m/Y', strtotime($deal['expected_close_date'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; padding: 20px; font-size:12px; color:#a89aa0;">ไม่มีงานที่ใกล้ครบกำหนด หรือไม่พบในตัวกรองนี้</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08);height:100%;">
            <div style="font-size:14.5px;font-weight:600;margin-bottom:14px">
                <svg width="16" height="16" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-refresh"></use></svg> กิจกรรมอัปเดตล่าสุด
            </div>
            <div style="display:flex;flex-direction:column;gap:0;font-size:12px">
                <?php if (count($recent_updates) > 0): ?>
                    <?php 
                        $colors = ['#c1466a', '#e88aa5', '#f0b8c8', '#a89aa0', '#d9c3cb'];
                        foreach ($recent_updates as $index => $update): 
                            $dot_color = $colors[$index % count($colors)];
                            $is_last = ($index === count($recent_updates) - 1);
                    ?>
                        <div style="display:flex;gap:11px;padding-bottom:<?= $is_last ? '0' : '11px' ?>">
                            <div style="display:flex;flex-direction:column;align-items:center">
                                <div style="width:9px;height:9px;border-radius:50%;background:<?= $dot_color ?>;margin-top:4px"></div>
                                <?php if (!$is_last): ?><div style="flex:1;width:2px;background:#f2e2e7"></div><?php endif; ?>
                            </div>
                            <div style="flex:1; padding-bottom: <?= $is_last ? '0' : '8px' ?>">
                                <div style="display:flex;justify-content:space-between">
                                    <span style="font-weight:600;color:#3a5a8a;"><?= htmlspecialchars($update['hospital_name']) ?></span>
                                    <span style="color:#b09aa2;font-size:11px"><?= date('d/m H:i', strtotime($update['created_at'])) ?></span>
                                </div>
                                <div style="color:#7a626a; margin:3px 0;"><?= htmlspecialchars($update['recent_activity']) ?></div>
                                <div style="color:#b09aa2;font-size:11px"><?= htmlspecialchars($update['emp_name']) ?> · <?= htmlspecialchars(mb_substr($update['status'], 0, 25)) ?>...</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; padding: 20px; font-size:12px; color:#a89aa0;">ยังไม่มีกิจกรรมอัปเดต หรือไม่พบในตัวกรองนี้</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* เสริม CSS สำหรับ Badge อัตโนมัติ */
    .bg-pastel-warning { background-color: #fdf3e0; }
    .bg-pastel-primary { background-color: #e6eefc; }
    .bg-pastel-success { background-color: #e3f4ea; }
    .bg-pastel-danger { background-color: #fbe4ea; }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#reportTable').DataTable({
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

        $('#exportBtn').on('click', function(e) {
            e.preventDefault();
            var dtSearch = table.search();
            var href = $(this).attr('href');
            
            var visibleCols = [];
            table.columns().every(function(index) {
                if (this.visible()) {
                    visibleCols.push(index);
                }
            });

            href = href.split('&search=')[0].split('&visible_cols=')[0]; 
            
            if (dtSearch) { href += '&search=' + encodeURIComponent(dtSearch); }
            if (visibleCols.length > 0) { href += '&visible_cols=' + visibleCols.join(','); }
            
            window.location.href = href;
        });

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