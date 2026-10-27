<?php
// ==========================================
// 1. เช็คสิทธิ์ (ให้เฉพาะผู้บริหารและแอดมินดูได้)
// ==========================================
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);

if (!$is_admin) {
    echo "<div class='alert alert-danger text-center mt-5'>คุณไม่มีสิทธิ์เข้าถึงหน้ารายงานยอดขายเชิงลึกครับ</div>";
    exit;
}

// ==========================================
// 2. รับค่าตัวกรอง
// ==========================================
$selected_year  = $_GET['year'] ?? date('Y'); // ค่าเริ่มต้นคือปีปัจจุบัน
$selected_month = $_GET['month'] ?? '';

$year_stmt = $pdo->query("SELECT DISTINCT YEAR(report_date) AS report_year FROM contacts WHERE report_date IS NOT NULL ORDER BY report_year DESC");
$available_years = $year_stmt->fetchAll(PDO::FETCH_COLUMN);

$thai_months = [
    "01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน",
    "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม",
    "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"
];

// ==========================================
// 3. เงื่อนไข SQL ดึงเฉพาะงานที่ "ปิดการขายสำเร็จ (Status 6)"
// ==========================================
$conditions = ["(ct.status LIKE '6%' OR ct.status = 'Closed Won')"];
$params = [];

if (!empty($selected_year)) {
    $conditions[] = "YEAR(ct.report_date) = ?";
    $params[] = $selected_year;
}
if (!empty($selected_month)) {
    $conditions[] = "MONTH(ct.report_date) = ?";
    $params[] = $selected_month;
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// ==========================================
// 4. ดึงข้อมูลทั้งหมดเพื่อนำมาจัดกลุ่มใน PHP
// ==========================================
$sql = "
    SELECT 
        IFNULL(pc.category_name, 'ไม่ระบุแบรนด์') AS brand, 
        IFNULL(p.model, 'ไม่ระบุรุ่น') AS model, 
        p.details AS product_details,
        p.id AS product_id,
        IFNULL(c.hospital_name, 'ไม่ระบุโรงพยาบาล') AS hospital_name,
        c.id AS customer_id,
        SUM(ci.quantity) AS total_qty, 
        SUM(ci.total_price) AS total_revenue
    FROM contact_items ci
    JOIN contacts ct ON ci.contact_id = ct.id
    LEFT JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    LEFT JOIN customers c ON ct.customer_id = c.id
    $where_clause
    GROUP BY p.id, pc.category_name, p.model, c.hospital_name, c.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 5. จัดกลุ่มข้อมูล (Group By) 2 มุมมอง
// ==========================================
$by_product = [];
$by_hospital = [];
$grand_total_revenue = 0;
$grand_total_qty = 0;

foreach ($raw_data as $row) {
    $grand_total_revenue += $row['total_revenue'];
    $grand_total_qty += $row['total_qty'];

    // --- จัดกลุ่มตาม: สินค้า ---
    $p_key = !empty($row['product_id']) ? 'P'.$row['product_id'] : 'P_'.md5($row['brand'].$row['model']); 
    
    if (!isset($by_product[$p_key])) {
        $by_product[$p_key] = [
            'id' => $p_key,
            'brand' => $row['brand'],
            'model' => $row['model'],
            'details' => $row['product_details'], // FIX: เก็บรายละเอียดสินค้าไว้ด้วย ไม่งั้นคอลัมน์นี้จะว่างเสมอ
            'total_qty' => 0,
            'total_revenue' => 0,
            'hospitals' => []
        ];
    }
    $by_product[$p_key]['total_qty'] += $row['total_qty'];
    $by_product[$p_key]['total_revenue'] += $row['total_revenue'];
    $by_product[$p_key]['hospitals'][] = [
        'name' => $row['hospital_name'],
        'qty' => $row['total_qty'],
        'revenue' => $row['total_revenue']
    ];

    // --- จัดกลุ่มตาม: โรงพยาบาล ---
    $h_key = !empty($row['customer_id']) ? 'H'.$row['customer_id'] : 'H_'.md5($row['hospital_name']);
    
    if (!isset($by_hospital[$h_key])) {
        $by_hospital[$h_key] = [
            'id' => $h_key,
            'hospital_name' => $row['hospital_name'],
            'total_qty' => 0,
            'total_revenue' => 0,
            'products' => []
        ];
    }
    $by_hospital[$h_key]['total_qty'] += $row['total_qty'];
    $by_hospital[$h_key]['total_revenue'] += $row['total_revenue'];
    $by_hospital[$h_key]['products'][] = [
        'brand' => $row['brand'],
        'model' => $row['model'],
        'details' => $row['product_details'],
        'qty' => $row['total_qty'],
        'revenue' => $row['total_revenue']
    ];
}

// เรียงลำดับจากยอดเงินรวมมากไปน้อย
usort($by_product, function($a, $b) { return $b['total_revenue'] <=> $a['total_revenue']; });
usort($by_hospital, function($a, $b) { return $b['total_revenue'] <=> $a['total_revenue']; });
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    /* ปรับแต่ง Tabs แบบแคปซูลนุ่มนวลตามดีไซน์ใหม่ */
    .nav-pills-custom .nav-link {
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 600;
        padding: 8px 16px;
        color: #8a7078;
        background: transparent;
        transition: all 0.2s ease;
    }
    .nav-pills-custom .nav-link.active {
        background: #c1466a;
        color: #fff;
    }
    .nav-pills-custom .nav-link:hover:not(.active) {
        background: #fdf5f7;
        color: #c1466a;
    }
</style>

<!-- Header -->
<div style="margin-bottom:22px">
    <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
        <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-chart"></use></svg> 
        สรุปยอดขายเชิงลึก
    </div>
    <div style="font-size:13.5px;color:#9a828a;margin-top:3px">นับเฉพาะงานที่ปิดการขายสำเร็จเท่านั้น</div>
</div>

<!-- FILTER BAR -->
<div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:24px">
    <form action="index.php" method="GET" id="salesFilterForm" class="m-0 d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="page" value="report_products">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px" class="me-2">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-filter"></use></svg> เลือกช่วงเวลา:
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

        <?php if (!empty($selected_year) || !empty($selected_month)): ?>
            <a href="?page=report_products" class="btn btn-outline-secondary btn-sm" style="border-radius:10px; font-size: 12.5px; padding:6px 12px;">ล้างค่า</a>
        <?php endif; ?>
    </form>
</div>

<!-- กล่องสรุปยอดรวม (KPIs) ดีไซน์ใหม่ -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div style="background:linear-gradient(150deg,#c1466a,#dd6d8e);color:#fff;border-radius:18px;padding:22px 24px;flex:1;box-shadow:0 8px 20px rgba(150,90,110,.18); height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div style="font-size:13px;opacity:.92">ยอดขายรวมสุทธิ</div>
                <span style="opacity:.85"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-award"></use></svg></span>
            </div>
            <div style="font-size:38px;font-weight:700;line-height:1.1;margin-top:6px">
                <?= number_format($grand_total_revenue, 2) ?> <span style="font-size:15px;font-weight:500;opacity:.9">บาท</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div style="background:linear-gradient(150deg,#3f74c4,#4a86d6);color:#fff;border-radius:18px;padding:22px 24px;flex:1;box-shadow:0 8px 20px rgba(58,90,150,.18); height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div style="font-size:13px;opacity:.92">จำนวนสินค้าที่ขายออกไป</div>
                <span style="opacity:.85"><svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-box"></use></svg></span>
            </div>
            <div style="font-size:38px;font-weight:700;line-height:1.1;margin-top:6px">
                <?= number_format($grand_total_qty) ?> <span style="font-size:15px;font-weight:500;opacity:.9">ชิ้น</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabs สลับมุมมอง -->
<div style="display:flex;gap:6px;margin-bottom:16px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 6px 18px rgba(150,90,110,.06);width:fit-content">
    <ul class="nav nav-pills nav-pills-custom mb-0" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="product-tab" data-bs-toggle="pill" data-bs-target="#product" type="button" role="tab">
                มุมมองตามสินค้า
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="hospital-tab" data-bs-toggle="pill" data-bs-target="#hospital" type="button" role="tab">
                มุมมองตามโรงพยาบาล
            </button>
        </li>
    </ul>
</div>

<!-- เนื้อหาของ Tabs -->
<div class="tab-content" id="reportTabsContent">
    
    <!-- TAB 1: มุมมองตามสินค้า -->
    <div class="tab-pane fade show active" id="product" role="tabpanel">
        <div style="background:#fff;border-radius:18px;box-shadow:0 6px 18px rgba(150,90,110,.08);padding:18px 20px">
            <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
                <table id="tableProduct" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12.5px">
                    <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <tr>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap" width="5%" class="text-center">อันดับ</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap">แบรนด์สินค้า</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap">รุ่น (Model)</th>
							<th style="padding:11px 13px;font-weight:600;white-space:nowrap">รายละเอียดสินค้า</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:right">จำนวนที่ขายได้</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:right">ยอดขายรวม (บาท)</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:center" width="15%" class="no-sort">เจาะลึกข้อมูล</th>
                        </tr>
                    </thead>
                    <tbody style="vertical-align:top">
                        <?php if (count($by_product) > 0): ?>
                            <?php foreach ($by_product as $index => $row): ?>
                                <tr>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center fw-bold text-muted"></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="fw-bold text-primary"><?= htmlspecialchars($row['brand']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-secondary"><?= htmlspecialchars($row['model']) ?></td>
									 <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-secondary"><?= htmlspecialchars($row['details'] ?? '') ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold"><?= number_format($row['total_qty']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold text-success"><?= number_format($row['total_revenue'], 2) ?> ฿</td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center">
                                        <button class="btn-icon-soft btn-icon-info" data-bs-toggle="modal" data-bs-target="#modalProd_<?= $row['id'] ?>" style="width:auto; padding: 4px 12px;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;margin-right:5px;"><use href="#i-search"></use></svg> ดูโรงพยาบาล
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="padding:34px;text-align:center;color:#a89aa0;font-size:12.5px">— ยังไม่มีดีลที่ปิดการขายสำเร็จ จึงยังไม่มีข้อมูลยอดขาย —</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: มุมมองตามโรงพยาบาล -->
    <div class="tab-pane fade" id="hospital" role="tabpanel">
        <div style="background:#fff;border-radius:18px;box-shadow:0 6px 18px rgba(150,90,110,.08);padding:18px 20px">
            <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
                <table id="tableHospital" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12.5px">
                    <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <tr>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap" width="5%" class="text-center">อันดับ</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap">ชื่อโรงพยาบาล</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:right">จำนวนที่ซื้อ (ชิ้น)</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:right">ยอดซื้อรวม (บาท)</th>
                            <th style="padding:11px 13px;font-weight:600;white-space:nowrap;text-align:center" width="15%" class="no-sort">เจาะลึกข้อมูล</th>
                        </tr>
                    </thead>
                    <tbody style="vertical-align:top">
                        <?php if (count($by_hospital) > 0): ?>
                            <?php foreach ($by_hospital as $index => $row): ?>
                                <tr>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center fw-bold text-muted"></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="fw-bold text-dark"><?= htmlspecialchars($row['hospital_name']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold"><?= number_format($row['total_qty']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold text-success"><?= number_format($row['total_revenue'], 2) ?> ฿</td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center">
                                        <button class="btn-icon-soft btn-icon-warning" data-bs-toggle="modal" data-bs-target="#modalHosp_<?= $row['id'] ?>" style="width:auto; padding: 4px 12px; color:#c98a1e;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;margin-right:5px;"><use href="#i-search"></use></svg> ดูสินค้าที่ซื้อ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="padding:34px;text-align:center;color:#a89aa0;font-size:12.5px">— ยังไม่มีดีลที่ปิดการขายสำเร็จ จึงยังไม่มีข้อมูลยอดขาย —</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ส่วนของการสร้าง Modal (ป๊อปอัพหน้าต่างเจาะลึก) -->
<!-- ========================================== -->

<!-- Modals สำหรับมุมมองตามสินค้า -->
<?php foreach ($by_product as $row): ?>
<div class="modal fade" id="modalProd_<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden; border:none; box-shadow:0 10px 40px rgba(120,60,80,.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#3f74c4,#4a86d6); color:#fff; border-bottom:none;">
                <h5 class="modal-title" style="font-weight:600; font-size:16px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;margin-right:6px;"><use href="#i-building"></use></svg> 
                    รายชื่อโรงพยาบาลที่ซื้อ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#fdfbfc; border:1px solid #e6eefc; border-radius:12px; padding:12px 16px; margin-bottom:16px; color:#3a63aa;">
                    <div><strong>สินค้า:</strong> <?= htmlspecialchars($row['brand'] . '  ' . $row['model'] . '') ?></div>
                    <?php if (!empty($row['details'])): ?>
                        <div class="text-muted" style="font-size:12.5px; margin-top:4px; white-space:normal;"><?= htmlspecialchars($row['details']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
                    <table class="table table-hover mb-0" style="font-size:12.5px">
                        <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                            <tr>
                                <th style="padding:11px 13px;font-weight:600;">ชื่อโรงพยาบาล</th>
                                <th style="padding:11px 13px;font-weight:600;text-align:center" width="20%">จำนวน (ชิ้น)</th>
                                <th style="padding:11px 13px;font-weight:600;text-align:right" width="30%">ยอดขาย (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            usort($row['hospitals'], function($a, $b) { return $b['revenue'] <=> $a['revenue']; });
                            foreach ($row['hospitals'] as $hosp): 
                            ?>
                                <tr>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;"><?= htmlspecialchars($hosp['name']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center"><?= number_format($hosp['qty']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold text-success"><?= number_format($hosp['revenue'], 2) ?> ฿</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#faf5f7;">
                                <td style="padding:11px 13px;border-top:2px solid #eddbe1;" class="fw-bold">รวมทั้งหมด</td>
                                <td style="padding:11px 13px;border-top:2px solid #eddbe1;" class="text-center fw-bold"><?= number_format($row['total_qty']) ?></td>
                                <td style="padding:11px 13px;border-top:2px solid #eddbe1;" class="text-end fw-bold text-success"><?= number_format($row['total_revenue'], 2) ?> ฿</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modals สำหรับมุมมองตามโรงพยาบาล -->
<?php foreach ($by_hospital as $row): ?>
<div class="modal fade" id="modalHosp_<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden; border:none; box-shadow:0 10px 40px rgba(120,60,80,.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0b429,#e09a1f); color:#fff; border-bottom:none;">
                <h5 class="modal-title" style="font-weight:600; font-size:16px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;margin-right:6px;"><use href="#i-box"></use></svg> 
                    รายการสินค้าที่โรงพยาบาลซื้อ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#fdfbfc; border:1px solid #fdf3e0; border-radius:12px; padding:12px 16px; margin-bottom:16px; color:#c98a1e;">
                    <strong></strong> <?= htmlspecialchars($row['hospital_name']) ?>
                </div>
                <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
                    <table class="table table-hover mb-0" style="font-size:12.5px">
                        <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                            <tr>
                                <th style="padding:11px 13px;font-weight:600;">แบรนด์ / รุ่น</th>
                                <th style="padding:11px 13px;font-weight:600;text-align:center" width="20%">จำนวน (ชิ้น)</th>
                                <th style="padding:11px 13px;font-weight:600;text-align:right" width="30%">ยอดซื้อ (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            usort($row['products'], function($a, $b) { return $b['revenue'] <=> $a['revenue']; });
                            foreach ($row['products'] as $prod): 
                            ?>
                                <tr>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;">
                                        <div class="fw-bold"><?= htmlspecialchars($prod['brand'] . ' ' . $prod['model']) ?></div>
                                        <?php if (!empty($prod['details'])): ?>
                                            <div class="text-muted" style="font-size:11.5px; white-space:normal;"><?= htmlspecialchars($prod['details']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-center"><?= number_format($prod['qty']) ?></td>
                                    <td style="padding:11px 13px;border-top:1px solid #f4ece2;" class="text-end fw-bold text-success"><?= number_format($prod['revenue'], 2) ?> ฿</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#fdf8ee;">
                                <td style="padding:11px 13px;border-top:2px solid #fdf3e0;" class="fw-bold">รวมทั้งหมด</td>
                                <td style="padding:11px 13px;border-top:2px solid #fdf3e0;" class="text-center fw-bold"><?= number_format($row['total_qty']) ?></td>
                                <td style="padding:11px 13px;border-top:2px solid #fdf3e0;" class="text-end fw-bold text-success"><?= number_format($row['total_revenue'], 2) ?> ฿</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var tableOptions = {
            "language": {
                "sLengthMenu": "แสดง _MENU_ รายการ", "sZeroRecords": "ไม่พบข้อมูลที่ค้นหา",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", "sSearch": "", "searchPlaceholder": "ค้นหาด่วน...",
                "oPaginate": { "sFirst": "หน้าแรก", "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป", "sLast": "หน้าสุดท้าย" }
            },
            "pageLength": 10
        };

        if ($('#tableProduct tbody tr td').length > 1) {
            var tableProd = $('#tableProduct').DataTable($.extend({}, tableOptions, { 
                "order": [[4, "desc"]],
                "columnDefs": [{ "orderable": false, "targets": [0, 5] }]
            }));
            
            // ให้คอลัมน์แรก (อันดับ) รันตัวเลขใหม่ทุกครั้งที่มีการจัดเรียงหรือค้นหา
            tableProd.on('order.dt search.dt', function () {
                let i = 1;
                tableProd.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                    this.data(i++);
                });
            }).draw();
        }
        
        if ($('#tableHospital tbody tr td').length > 1) {
            var tableHosp = $('#tableHospital').DataTable($.extend({}, tableOptions, { 
                "order": [[3, "desc"]],
                "columnDefs": [{ "orderable": false, "targets": [0, 4] }]
            }));
            
            // ให้คอลัมน์แรก (อันดับ) รันตัวเลขใหม่ทุกครั้งที่มีการจัดเรียงหรือค้นหา
            tableHosp.on('order.dt search.dt', function () {
                let i = 1;
                tableHosp.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                    this.data(i++);
                });
            }).draw();
        }
    });
</script>