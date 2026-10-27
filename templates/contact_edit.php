<?php
$id = $_GET['id'] ?? 0;

// 1. ดึงข้อมูลหลักจากตาราง contacts
$stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
$stmt->execute([$id]);
$ct = $stmt->fetch();
if (!$ct) {
    header("Location: ?page=contacts");
    exit;
}

$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);

//  2. ดึงรายการสินค้า: ให้ JOIN ตาราง product_categories มาดึงแบรนด์
$stmt_items = $pdo->prepare("
    SELECT ci.*, pc.category_name, p.model, p.details
FROM contact_items ci
LEFT JOIN products p ON ci.product_id = p.id
LEFT JOIN product_categories pc ON p.category_id = pc.id
WHERE ci.contact_id = ?
ORDER BY ci.id ASC
");
$stmt_items->execute([$id]);
$saved_items = $stmt_items->fetchAll();

// 3. ดึงข้อมูลมาสเตอร์สำหรับ Dropdown ตัวกรอง
$employees = $pdo->query("SELECT id, sr_no, fullname FROM employees ORDER BY fullname ASC")->fetchAll();
$customers = $pdo->query("SELECT id, hospital_name, customer_name, phone, department FROM customers ORDER BY hospital_name ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_name ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id ORDER BY c.category_name ASC, p.model ASC")->fetchAll();

// เตรียมตัวแปรเช็คค่าปัญหา/สนับสนุน
$prob_options = ["ลูกค้ายังไม่ชัดเจนงบ", "TOR อาจล็อกสเปค", "มีผู้มีอำนาจตัดสินใจหลายคน", "สินค้ามีราคาสูงกว่าคู่แข่ง"];
$curr_prob = htmlspecialchars($ct['issues'] ?? '');
$is_other_prob = $curr_prob && !in_array($curr_prob, $prob_options);

$supp_options = ["ต้องการผู้เชี่ยวชาญเข้า Demo", "ต้องการปรับราคา/ส่วนลดพิเศษ", "ต้องการเอกสารเทคนิคเพิ่มเติม"];
$curr_supp = htmlspecialchars($ct['support_needed'] ?? '');
$is_other_supp = $curr_supp && !in_array($curr_supp, $supp_options);
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .card-pastel-edit {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 18px rgba(150,90,110,.08);
        overflow: hidden;
        margin-bottom: 20px;
        border: none;
    }
    .header-gradient-warn {
        background: linear-gradient(135deg, #f0b429, #e09a1f);
        color: #fff;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    /* สไตล์ช่อง Input แบบใหม่ให้เข้ากับดีไซน์ส่วนที่ 2 */
    .input-pastel {
        display: block; width: 100%; box-sizing: border-box; border: 1px solid #eddbe1; 
        border-radius: 10px; padding: 9px 12px; font-size: 12.5px; background: #fdfbfc; 
        color: #4a3b40; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-pastel:focus {
        border-color: #c1466a; box-shadow: 0 0 0 3px rgba(193, 70, 106, 0.1);
    }
    .input-pastel-fw { font-weight: 600; }
    .input-pastel::placeholder { color: #c3a9b3; }
    
    /* ซ่อน scrollbar สำหรับตัว Funnel */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#c98a1e;"><use href="#i-edit"></use></svg> 
            แก้ไขข้อมูลการเข้าพบลูกค้า
        </div>
    </div>
    <a href="?page=contacts" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการติดต่อ</a>
</div>

<form action="index.php?route=contact_edit_action" method="POST" id="contactForm">
    <input type="hidden" name="id" value="<?= $ct['id'] ?>">

    <!-- ส่วนที่ 1 -->
    <div class="card-pastel-edit">
        <div class="header-gradient-warn">
            <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-file"></use></svg>
            <div>
                <div style="font-size:16px;font-weight:600">ส่วนที่ 1 · ข้อมูลการเสนอราคาและรายการสินค้า (แก้ไข)</div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">พนักงานผู้รับผิดชอบ</label>
                    <?php if ($is_admin): ?>
                        <select class="form-select" name="employee_id" id="employee_select" required>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= ($ct['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['sr_no'] . ' - ' . $emp['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php
                        $emp_name_display = '';
                        foreach ($employees as $emp) {
                            if ($emp['id'] == $ct['employee_id']) {
                                $emp_name_display = $emp['sr_no'] . ' - ' . $emp['fullname'];
                                break;
                            }
                        }
                        ?>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($emp_name_display) ?>" readonly>
                        </div>
                        <input type="hidden" name="employee_id" value="<?= htmlspecialchars($ct['employee_id']) ?>">
                    <?php endif; ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">วันที่เสนอราคา</label>
                    <input type="date" class="form-control" name="quote_date" value="<?= htmlspecialchars($ct['quotation_date'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">เลขที่ใบเสนอราคา</label>
                    <input type="text" class="form-control" name="quote_number" value="<?= htmlspecialchars($ct['quotation_no'] ?? '') ?>">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">โรงพยาบาล / แผนก / ชื่อลูกค้า / เบอร์ติดต่อ</label>
                    <select class="form-select" name="customer_id" id="customer_select" required>
                        <?php foreach ($customers as $cus):
                            $h_name = $cus['hospital_name'] ?: 'ไม่ระบุโรงพยาบาล';
                            $dept   = $cus['department'] ?: 'ไม่ระบุแผนก';
                            $c_name = $cus['customer_name'] ?: '-';
                            $phone  = $cus['phone'] ?: 'ไม่มีเบอร์ติดต่อ';
                        ?>
                            <option value="<?= $cus['id'] ?>" <?= ($ct['customer_id'] ?? '') == $cus['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($h_name . ' | แผนก: ' . $dept . ' |  ' . $c_name . ' (' . $phone . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- กล่องเพิ่มสินค้า -->
            <div style="background:#fdf5f7;border-radius:12px;padding:16px;margin-bottom:20px;">
                <h6 style="font-weight:600;color:#c1466a;margin-bottom:12px;font-size:14px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;margin-right:4px;"><use href="#i-box"></use></svg> 
                    เลือกสินค้าเพิ่มเติมเข้าตาราง
                </h6>
                <div class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="form-label" style="font-size:11px;color:#8a7078;font-weight:600;">1. เลือกแบรนด์สินค้า</label>
                        <select class="form-select" id="category_filter_select">
                            <option value="">-- แสดงทั้งหมด --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label" style="font-size:11px;color:#8a7078;font-weight:600;">2. เลือกรุ่นสินค้า</label>
                        <select class="form-select" id="product_picker_select" placeholder="เลือกรุ่นเพื่อเพิ่ม...">
                            <option value=""></option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>" data-category="<?= $prod['category_id'] ?>">
                                    <?= htmlspecialchars($prod['category_name'] . ' ' . ($prod['model'] ?: '-') . ' ' . ($prod['details'] ?: '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" style="width:100%;font-size:13px;padding:9px;border-radius:10px;background:#c1466a;color:#fff;font-weight:600;border:none;" id="btn_add_to_table">
                            <i class="fa-solid fa-plus"></i> เพิ่มสินค้า
                        </button>
                    </div>
                </div>
            </div>

            <!-- ตารางสินค้า -->
            <div class="table-responsive mb-4" style="border:1px solid #f4e6ec;border-radius:12px">
                <table class="table table-hover align-middle m-0" id="invoiceTable" style="font-size:12.5px;">
                    <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <tr>
                            <th width="5%" style="padding:11px 13px;font-weight:600;">ลำดับ</th>
                            <th style="padding:11px 13px;font-weight:600;">แบรนด์สินค้า / รุ่น</th>
                            <th width="15%" style="padding:11px 13px;font-weight:600;text-align:right">ราคา/หน่วย (บาท)</th>
                            <th width="12%" style="padding:11px 13px;font-weight:600;text-align:center">จำนวน</th>
                            <th width="18%" style="padding:11px 13px;font-weight:600;text-align:right">ราคารวม (บาท)</th>
                            <th width="8%" style="padding:11px 13px;font-weight:600;text-align:center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="invoice_items_body" style="vertical-align:top">
                        <?php if (count($saved_items) > 0): ?>
                            <?php foreach ($saved_items as $index => $item): ?>
                                <tr class="item-calculation-row">
                                    <td class="text-center align-middle fw-bold text-muted row-index-number" style="border-top:1px solid #f4ece2;"><?= $index + 1 ?></td>
                                    <td style="border-top:1px solid #f4ece2;">
                                 <span class="fw-bold text-dark">
                                        <?= htmlspecialchars(
                                            ($item['category_name'] ?? 'ไม่ระบุ') . ' ' .
                                            (!empty($item['model']) ? $item['model'] : '-') . ' ' .
                                            (!empty($item['details']) ? $item['details'] : '-')
                                        ) ?>
                                    </span>
                                        <input type="hidden" name="product_id[]" value="<?= $item['product_id'] ?>" class="row-product-id">
                                    </td>
                                    <td style="border-top:1px solid #f4ece2;"><input type="text" inputmode="decimal" class="form-control form-control-sm text-end row-unit-price" name="item_price[]" value="<?= number_format($item['unit_price'], 2) ?>" required></td>
                                    <td style="border-top:1px solid #f4ece2;"><input type="number" class="form-control form-control-sm text-center row-quantity" name="item_qty[]" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" required></td>
                                    <td class="text-end fw-bold text-secondary align-middle" style="border-top:1px solid #f4ece2;"><span class="txt-row-total"><?= number_format($item['total_price'], 2) ?></span> </td>
                                    <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                        <button type="button" class="btn-icon-soft btn-icon-danger btn-remove-item-row"><svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;pointer-events:none;"><use href="#i-trash"></use></svg></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row-placeholder">
                                <td colspan="6" class="text-center py-4" style="color:#a89aa0;border-top:1px solid #f4ece2;">ยังไม่มีรายการสินค้าในตาราง</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- สรุปราคา -->
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div style="font-size:13px; color:#6b5760;">
                        <div style="display:flex;justify-content:space-between;padding:6px 0">
                            <span>ราคาสินค้า (Subtotal):</span>
                            <span class="fw-bold"><span id="txt_subtotal"><?= number_format($ct['subtotal'] ?? 0, 2) ?></span> </span>
                            <input type="hidden" name="subtotal" id="input_subtotal" value="<?= number_format($ct['subtotal'] ?? 0, 2, '.', '') ?>">
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:6px 0;align-items:center;">
                            <span class="text-danger fw-bold">ส่วนลดพิเศษ (Discount):</span>
                            <div style="width: 120px;">
                                <input type="text" inputmode="decimal" class="form-control form-control-sm text-end fw-bold text-danger" name="discount" id="input_discount" value="<?= number_format($ct['discount'] ?? 0, 2) ?>">
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:6px 0">
                            <span>ภาษีมูลค่าเพิ่ม (VAT 7%):</span>
                            <span class="fw-bold"><span id="txt_vat"><?= number_format($ct['vat'] ?? 0, 2) ?></span> </span>
                            <input type="hidden" name="vat" id="input_vat" value="<?= number_format($ct['vat'] ?? 0, 2, '.', '') ?>">
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:1px solid #f0dce3;margin-top:4px;font-weight:700;font-size:15px;color:#c1466a">
                            <span>ราคาสุทธิ (Grand Total):</span>
                            <span><span id="txt_grand_total"><?= number_format($ct['grand_total'] ?? 0, 2) ?></span> </span>
                            <input type="hidden" name="grand_total" id="input_grand_total" value="<?= number_format($ct['grand_total'] ?? 0, 2, '.', '') ?>">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ส่วนที่ 2 แบบใหม่ (Sales Funnel UI) -->
    <div class="card-pastel-edit">
        <div style="background:linear-gradient(90deg,#2f9fb8,#38b6cf);color:#fff;padding:14px 20px;display:flex;align-items:center;gap:10px;">
            <span><svg width="20" height="20" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;flex:none;display:inline-block"><use href="#i-refresh"></use></svg></span>
            <div>
                <div style="font-size:15px;font-weight:600;color:#fff;">ส่วนที่ 2 · การติดตามและสถานะงาน (แก้ไข)</div>
                <div style="font-size:11.5px;color:rgba(255,255,255,0.9);">อัปเดตความคืบหน้าและ Sales Funnel</div>
            </div>
        </div>
        <div style="padding:22px;">
            
            <!-- ระบบ Sales Funnel กราฟิก -->
            <div style="font-size:12px;color:#8a7078;margin-bottom:6px">สถานะงาน (Sales Funnel) <span class="text-danger">*</span></div>
            <div style="background:#fdfbfc;border:1px solid #f0dce3;border-radius:14px;padding:16px 14px 12px;margin-bottom:20px; overflow-x: auto;" class="no-scrollbar">
                <input type="hidden" name="job_status" id="job_status_input" value="<?= htmlspecialchars($ct['status'] ?? '0 ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว') ?>" required>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin:6px 0 4px; min-width: 600px;" id="funnel_container">
                    <!-- Javascript จะสร้างวงกลมสถานะตรงนี้อัตโนมัติ -->
                </div>
            </div>

            <!-- แถวที่ 1 -->
            <div class="row mb-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">วันที่รายงาน <span class="text-danger">*</span></div>
                    <input type="date" class="input-pastel input-pastel-fw" name="report_date" value="<?= htmlspecialchars($ct['report_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">คู่แข่ง</div>
                    <input type="text" class="input-pastel input-pastel-fw" name="competitor" value="<?= htmlspecialchars($ct['competitor'] ?? '') ?>" placeholder="ชื่อบริษัทคู่แข่ง หรือพิมพ์ 'ไม่มี'">
                </div>
                <div class="col-md-4">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">วันที่คาดว่าจะปิดได้</div>
                    <input type="date" class="input-pastel input-pastel-fw" style="color:#c1466a; border-color:#f4c9d5;" name="expected_close_date" value="<?= !empty($ct['expected_close_date']) && $ct['expected_close_date'] != '0000-00-00' ? htmlspecialchars($ct['expected_close_date']) : '' ?>">
                </div>
            </div>

            <!-- แถวที่ 2 -->
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">กิจกรรมล่าสุด <span class="text-danger">*</span></div>
                    <textarea class="input-pastel" name="recent_activity" rows="2" required placeholder="เช่น ได้เข้าพบเพื่ออธิบายการใช้งานเพิ่มเติม..."><?= htmlspecialchars($ct['recent_activity'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">แผนถัดไป</div>
                    <textarea class="input-pastel" name="next_plan" rows="2" placeholder="เช่น นัดสาธิตเครื่องจริงสัปดาห์หน้า..."><?= htmlspecialchars($ct['next_plan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- แถวที่ 3 -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">ปัญหา / อุปสรรค</div>
                    <select class="input-pastel" id="problems_select" style="color:#a89aa0;">
                        <option value="">-- ไม่มี / เลือกปัญหา --</option>
                        <?php foreach ($prob_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $curr_prob === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                        <option value="อื่นๆ" <?= $is_other_prob ? 'selected' : '' ?>>อื่นๆ (โปรดระบุ)</option>
                    </select>
                    <input type="text" class="input-pastel mt-2 <?= $is_other_prob ? '' : 'd-none' ?>" id="problems_other" value="<?= $is_other_prob ? $curr_prob : '' ?>" placeholder="โปรดระบุปัญหา / อุปสรรค...">
                    <input type="hidden" name="problems" id="final_problems" value="<?= $curr_prob ?>">
                </div>
                <div class="col-md-6">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">สิ่งที่ต้องการสนับสนุน</div>
                    <select class="input-pastel" id="support_select" style="color:#a89aa0;">
                        <option value="">-- ไม่มี / เลือกสิ่งที่ต้องการ --</option>
                        <?php foreach ($supp_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $curr_supp === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                        <option value="อื่นๆ" <?= $is_other_supp ? 'selected' : '' ?>>อื่นๆ (โปรดระบุ)</option>
                    </select>
                    <input type="text" class="input-pastel mt-2 <?= $is_other_supp ? '' : 'd-none' ?>" id="support_other" value="<?= $is_other_supp ? $curr_supp : '' ?>" placeholder="โปรดระบุสิ่งที่ต้องการสนับสนุน...">
                    <input type="hidden" name="support_needed" id="final_support" value="<?= $curr_supp ?>">
                </div>
            </div>

            <!-- จัดปุ่มชิดขวา และเอาเส้นขอบบนออก -->
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px;">
                <a href="?page=contacts" style="font-size:12.5px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;cursor:pointer;">ยกเลิก</a>
                <button type="submit" style="font-size:12.5px;padding:10px 22px;border-radius:11px;background:#e09a1f;color:#fff;font-weight:600;border:none;cursor:pointer;">
                    <i class="fa-solid fa-save"></i> บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        function fmtMoney(n) { return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function parseMoney(str) { return parseFloat(String(str).replace(/,/g, '')) || 0; }

        if (document.getElementById('employee_select')) { new TomSelect("#employee_select", { create: false }); }
        new TomSelect("#customer_select", { create: false });
        var productPicker = new TomSelect("#product_picker_select", { create: false, maxOptions: null });

        var allProductOptions = Array.from(document.querySelectorAll("#product_picker_select option"));
        document.getElementById('category_filter_select').addEventListener('change', function() {
            var catId = this.value;
            productPicker.clear();
            productPicker.clearOptions();
            allProductOptions.forEach(opt => {
                if (opt.value === "") return;
                if (!catId || opt.getAttribute('data-category') === catId) {
                    productPicker.addOption({ value: opt.value, text: opt.text });
                }
            });
            productPicker.refreshOptions(false);
        });

        function updateRowIndices() {
            let rows = document.querySelectorAll('.item-calculation-row');
            rows.forEach((row, index) => {
                row.querySelector('.row-index-number').innerText = index + 1;
            });
        }

        document.getElementById('btn_add_to_table').addEventListener('click', function() {
            var prodId = productPicker.getValue();
            var prodText = productPicker.options[prodId] ? productPicker.options[prodId].text : '';

            if (!prodId || prodId === "") { Swal.fire({icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกสินค้าก่อนครับ'}); return; }
            if (document.querySelector(`input[value="${prodId}"].row-product-id`)) { Swal.fire({icon: 'info', title: 'สินค้าซ้ำ', text: 'สินค้ารายการนี้อยู่ในตารางแล้วครับ'}); return; }

            var placeholder = document.querySelector('.empty-row-placeholder');
            if (placeholder) placeholder.remove();

            var tbody = document.getElementById('invoice_items_body');
            var tr = document.createElement('tr');
            tr.className = "item-calculation-row";
            tr.innerHTML = `
                <td class="text-center align-middle fw-bold text-muted row-index-number" style="border-top:1px solid #f4ece2;"></td>
                <td style="border-top:1px solid #f4ece2;"><span class="fw-bold text-dark">${prodText}</span><input type="hidden" name="product_id[]" value="${prodId}" class="row-product-id"></td>
                <td style="border-top:1px solid #f4ece2;"><input type="text" inputmode="decimal" class="form-control form-control-sm text-end row-unit-price" name="item_price[]" value="0.00" required></td>
                <td style="border-top:1px solid #f4ece2;"><input type="number" class="form-control form-control-sm text-center row-quantity" name="item_qty[]" value="1" min="1" required></td>
                <td class="text-end fw-bold text-secondary align-middle" style="border-top:1px solid #f4ece2;"><span class="txt-row-total">0.00</span> ฿</td>
                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;"><button type="button" class="btn-icon-soft btn-icon-danger btn-remove-item-row"><svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;pointer-events:none;"><use href="#i-trash"></use></svg></button></td>
            `;
            tbody.appendChild(tr);

            updateRowIndices();
            productPicker.clear();
            calculateInvoiceMoney();
        });

        document.getElementById('invoice_items_body').addEventListener('input', function(e) {
            if (e.target.classList.contains('row-unit-price') || e.target.classList.contains('row-quantity')) calculateInvoiceMoney();
        });

        document.getElementById('invoice_items_body').addEventListener('blur', function(e) {
            if (e.target.classList.contains('row-unit-price')) { e.target.value = fmtMoney(parseMoney(e.target.value)); }
        }, true);

        document.getElementById('invoice_items_body').addEventListener('focus', function(e) {
            if (e.target.classList.contains('row-unit-price')) {
                var raw = parseMoney(e.target.value);
                e.target.value = raw === 0 ? '' : raw;
            }
        }, true);

        document.getElementById('invoice_items_body').addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-item-row') || e.target.closest('.btn-remove-item-row')) {
                e.target.closest('tr').remove();
                if (document.querySelectorAll('.item-calculation-row').length === 0) {
                    document.getElementById('invoice_items_body').innerHTML = `<tr class="empty-row-placeholder"><td colspan="6" class="text-center py-4" style="color:#a89aa0;border-top:1px solid #f4ece2;">ยังไม่มีรายการสินค้าในตาราง</td></tr>`;
                } else {
                    updateRowIndices();
                }
                calculateInvoiceMoney();
            }
        });

        document.getElementById('input_discount').addEventListener('input', calculateInvoiceMoney);
        document.getElementById('input_discount').addEventListener('blur', function(e) { e.target.value = fmtMoney(parseMoney(e.target.value)); });
        document.getElementById('input_discount').addEventListener('focus', function(e) { var raw = parseMoney(e.target.value); e.target.value = raw === 0 ? '' : raw; });

        function calculateInvoiceMoney() {
            var subtotal = 0;
            document.querySelectorAll('.item-calculation-row').forEach(row => {
                var price = parseMoney(row.querySelector('.row-unit-price').value);
                var qty = parseInt(row.querySelector('.row-quantity').value) || 0;
                var rowTotal = price * qty;
                subtotal += rowTotal;
                row.querySelector('.txt-row-total').innerText = fmtMoney(rowTotal);
            });
            var discount = parseMoney(document.getElementById('input_discount').value);
            var afterDiscount = Math.max(0, subtotal - discount);
            var vat = afterDiscount * 0.07;
            var grandTotal = afterDiscount + vat;

            document.getElementById('txt_subtotal').innerText = fmtMoney(subtotal);
            document.getElementById('txt_vat').innerText = fmtMoney(vat);
            document.getElementById('txt_grand_total').innerText = fmtMoney(grandTotal);
            document.getElementById('input_subtotal').value = subtotal.toFixed(2);
            document.getElementById('input_vat').value = vat.toFixed(2);
            document.getElementById('input_grand_total').value = grandTotal.toFixed(2);
        }

        // ==========================================
        // ระบบ Sales Funnel กราฟิก (ส่วนที่ 2)
        // ==========================================
        const statuses = [
            "0 ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว",
            "1 เริ่มทำเสนอราคา ต้องการเครื่อง ของบไปไม่ทราบผล",
            "2 มีงบแล้วหรือเลือกเราแล้ว",
            "3 มีงบแล้วและเลือกเราแล้ว (รอ)ขั้นตอนการสั่งซื้อ",
            "4 เรียกเซ็นสัญญาแล้วหรือรอส่งของ",
            "5 รอเก็บเงิน",
            "6 สิ้นสุดการขาย จบการขาย ปิดจ๊อบ"
        ];
        const statusLabels = ["ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว", "เริ่มทำเสนอราคา ต้องการเครื่อง ของบไปไม่ทราบผล", "มีงบแล้วหรือเลือกเราแล้ว", "มีงบแล้วและเลือกเราแล้ว (รอ)ขั้นตอนการสั่งซื้อ",
							  			"เรียกเซ็นสัญญาแล้วหรือรอส่งของ", "รอเก็บเงิน", "สิ้นสุดการขาย จบการขาย ปิดจ๊อบ"];

        function renderFunnel(selectedIndex) {
            let html = '';
            for(let i=0; i<7; i++) {
                let isPassed = i <= selectedIndex;
                let isCurrent = i === selectedIndex;
                let isLast = i === 6;

                let lineHtml = '';
                if (!isLast) {
                    let lineColor = i < selectedIndex ? '#c1466a' : '#f0dce3';
                    lineHtml = `<div style="position:absolute;top:15px;left:50%;width:100%;height:3px;background:${lineColor}"></div>`;
                }

                let circleStyle = isPassed ? 'background:#c1466a;border:2px solid #c1466a;color:#fff;' : 'background:#fff;border:2px solid #e6cdd6;color:#c3a9b3;';
                let shadowStyle = isCurrent ? 'box-shadow:0 0 0 5px #fbe4ea;' : '';
                let textStyle = isCurrent ? 'color:#c1466a;font-weight:600;' : (isPassed ? 'color:#9a828a;font-weight:400;' : 'color:#9a828a;font-weight:400;');

                html += `
                <div class="funnel-step" data-index="${i}" style="flex:1;display:flex;flex-direction:column;align-items:center;position:relative;cursor:pointer;min-width:0;">
                    ${lineHtml}
                    <div style="width:31px;height:31px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;position:relative;z-index:1;transition:all 0.2s;${circleStyle}${shadowStyle}">${i}</div>
                    <div style="font-size:10.5px;margin-top:7px;text-align:center;transition:all 0.2s;${textStyle}max-width:78px">${statusLabels[i]}</div>
                </div>`;
            }
            document.getElementById('funnel_container').innerHTML = html;

            document.querySelectorAll('.funnel-step').forEach(step => {
                step.addEventListener('click', function() {
                    let idx = parseInt(this.getAttribute('data-index'));
                    document.getElementById('job_status_input').value = statuses[idx];
                    renderFunnel(idx);
                });
            });
        }

        let jobStatusInput = document.getElementById('job_status_input');
        if (jobStatusInput) {
            let initStatus = jobStatusInput.value;
            let initIndex = statuses.indexOf(initStatus);
            if(initIndex === -1) initIndex = 1;
            jobStatusInput.value = statuses[initIndex];
            renderFunnel(initIndex);
        }

        // ==========================================
        // การจัดการฟิลด์ "อื่นๆ (โปรดระบุ)"
        // ==========================================
        function toggleOtherInput(selectId, inputId) {
            let selectEl = document.getElementById(selectId);
            let inputEl = document.getElementById(inputId);
            if (selectEl.value === 'อื่นๆ') {
                inputEl.classList.remove('d-none');
                inputEl.required = true;
                inputEl.focus();
            } else {
                inputEl.classList.add('d-none');
                inputEl.required = false;
            }
        }
        document.getElementById('problems_select').addEventListener('change', function() { toggleOtherInput('problems_select', 'problems_other'); });
        document.getElementById('support_select').addEventListener('change', function() { toggleOtherInput('support_select', 'support_other'); });

        document.getElementById('contactForm').addEventListener('submit', function(e) {
            if (document.querySelectorAll('.item-calculation-row').length === 0) {
                e.preventDefault();
                Swal.fire({icon: 'error', title: 'ข้อผิดพลาด', text: 'กรุณาเลือกและเพิ่มสินค้าลงตารางอย่างน้อย 1 รายการก่อนบันทึกข้อมูลครับ'});
                return false;
            }
            let probSel = document.getElementById('problems_select').value;
            let probOther = document.getElementById('problems_other').value;
            document.getElementById('final_problems').value = (probSel === 'อื่นๆ') ? probOther : probSel;
            
            let suppSel = document.getElementById('support_select').value;
            let suppOther = document.getElementById('support_other').value;
            document.getElementById('final_support').value = (suppSel === 'อื่นๆ') ? suppOther : suppSel;

            document.querySelectorAll('.row-unit-price').forEach(input => { input.value = parseMoney(input.value); });
            document.getElementById('input_discount').value = parseMoney(document.getElementById('input_discount').value);

            let submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'กำลังบันทึกข้อมูล...';
            }
        });
    });
</script>