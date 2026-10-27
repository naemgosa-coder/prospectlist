<?php
$tab = $_GET['tab'] ?? 'active';

// เช็คสิทธิ์ว่าเป็น Admin/ผู้บริหาร หรือไม่
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin = in_array($user_position, $allowed_roles);
$current_user_id = $_SESSION['user_id'];

$conditions = [];
$params = [];

if ($tab == 'active') {
    $conditions[] = "ct.status NOT LIKE '6%'";
} elseif ($tab == 'closed') {
    $conditions[] = "ct.status LIKE '6%'";
}

// ถ้าไม่ใช่ Admin ให้บังคับดึงข้อมูลเฉพาะของตัวเองเท่านั้น
if (!$is_admin) {
    $conditions[] = "ct.employee_id = ?";
    $params[] = $current_user_id;
}

$where_clause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// ดึงราคาแยกทีละรายการ และแยกคอลัมน์แบรนด์กับรุ่น
$sql = "
    SELECT ct.*, 
           e.fullname AS emp_name, 
           c.hospital_name, c.department, c.customer_name,
           COALESCE(
               (SELECT GROUP_CONCAT(IFNULL(REPLACE(REPLACE(pc.category_name, '\r', ''), '\n', ' '), 'ไม่มีแบรนด์') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE ci.contact_id = ct.id),
               'ไม่มีแบรนด์'
           ) AS category_name,
           COALESCE(
               (SELECT GROUP_CONCAT(IFNULL(REPLACE(REPLACE(p.model, '\r', ''), '\n', ' '), '-') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci LEFT JOIN products p ON ci.product_id = p.id WHERE ci.contact_id = ct.id),
               '-'
           ) AS model,
           COALESCE(
               (SELECT GROUP_CONCAT(CONCAT(FORMAT(IFNULL(ci.total_price, 0), 2), ' ฿') ORDER BY ci.id ASC SEPARATOR '\\n') FROM contact_items ci WHERE ci.contact_id = ct.id),
               CONCAT(FORMAT(IFNULL(ct.purchase_price, 0), 2), ' ฿')
           ) AS item_prices
    FROM contacts ct
    LEFT JOIN employees e ON ct.employee_id = e.id
    LEFT JOIN customers c ON ct.customer_id = c.id
    $where_clause
    ORDER BY ct.id DESC 
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    /* เอฟเฟกต์เวลาเอาเมาส์ชี้แถว */
    .clickable-row td:not(:first-child):not(:last-child) { cursor: pointer; }
    .clickable-row:hover td:not(:first-child):not(:last-child) { background-color: #fcf6f8 !important; }
    
    /* สไตล์ Tabs แคปซูล */
    .pill-tab {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .pill-tab.active { background: #c1466a; color: #fff; }
    .pill-tab.inactive { background: transparent; color: #8a7078; }
    .pill-tab.inactive:hover { background: #fdf5f7; color: #c1466a; }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-users"></use></svg> 
            เข้าพบลูกค้า
        </div>
        <div style="font-size:13.5px;color:#9a828a;margin-top:3px">บันทึกและติดตามการเข้าพบลูกค้า</div>
    </div>
    <a href="?page=contact_add" style="font-size:12.5px;padding:9px 16px;border-radius:11px;background:#c1466a;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
        <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-plus"></use></svg> เพิ่มการติดต่อ
    </a>
</div>

<!-- Tabs สลับหน้า -->
<div style="display:flex;gap:6px;margin-bottom:20px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 6px 18px rgba(150,90,110,.06);width:fit-content">
    <a href="?page=contacts&tab=active" class="pill-tab <?= $tab == 'active' ? 'active' : 'inactive' ?>">กำลังดำเนินการ</a>
    <a href="?page=contacts&tab=closed" class="pill-tab <?= $tab == 'closed' ? 'active' : 'inactive' ?>">ปิดจ๊อบ / ประวัติเก่า</a>
    <a href="?page=contacts&tab=all" class="pill-tab <?= $tab == 'all' ? 'active' : 'inactive' ?>">ทั้งหมด</a>
</div>

<form method="POST" id="bulkForm">
    <!-- แถบจัดการแบบกลุ่ม -->
    <div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-list"></use></svg> จัดการรายการที่เลือก:
        </span>
        
        <?php if ($tab == 'active'): ?>
            <input type="hidden" name="new_status" value="6 สิ้นสุดการขาย จบการขาย ปิดจ๊อบ">
            <button type="button" class="btn-bulk-action" data-action="index.php?route=contact_bulk_status" data-msg="ยืนยันการเคลียร์ข้อมูลเก่า? รายการที่เลือกทั้งหมดจะถูกเปลี่ยนเป็นสถานะ 6 (จบการขาย) ทันที" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:none;background:#2fa572;color:#fff;font-weight:600;cursor:pointer;">
                ปิดจ๊อบ
            </button>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <button type="button" class="btn-bulk-action" data-action="index.php?route=contact_bulk_delete" data-msg="คำเตือนระดับอันตราย: ยืนยันการลบรายการที่เลือกทั้งหมด? (ข้อมูลสินค้าและประวัติการติดตามทั้งหมดจะถูกลบทิ้งอย่างถาวร!)" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:1px solid #f4c9d5;color:#c1466a;background:#fff;font-weight:600;cursor:pointer;">
                ลบรายการที่เลือก
            </button>
        <?php endif; ?>
    </div>

    <!-- ตารางข้อมูล -->
    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
        <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
            <!-- 🛑 เปลี่ยน ID ตารางใหม่เป็น contactTableV2 เพื่อบังคับล้างแคชเรียงลำดับเก่าของเครื่องผู้ใช้ -->
            <table id="contactTableV2" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12px;">
                <thead>
                    <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <th width="3%" class="no-sort text-center"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th width="5%" class="text-center">ลำดับ</th>
                        <th width="10%">พนักงาน</th>
                        <th width="18%">โรงพยาบาล</th>
                        <th width="10%">แบรนด์สินค้า</th>
                        <th width="10%">รุ่น</th>
                        <th width="12%" class="text-end">ราคา (แยกรายการ)</th>
                        <th width="10%" class="text-center">วันที่เสนอราคา</th>
                        <th width="11%" class="text-center">เลขเสนอราคา</th>
                        <th width="14%">สถานะงาน</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody style="vertical-align:top">
                    <?php if (count($contacts) > 0): ?>
                        <?php foreach ($contacts as $ct):
                            $is_closed = (strpos($ct['status'], '0') === 0 || strpos($ct['status'], '6') === 0);
                            $row_link = $is_closed ? "?page=contact_history&id=" . $ct['id'] : "?page=contact_update&id=" . $ct['id'];
                        ?>
                            <tr class="clickable-row" data-href="<?= $row_link ?>">
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <input type="checkbox" name="contact_ids[]" value="<?= $ct['id'] ?>" class="form-check-input check-item">
                                </td>
                                
                                <!-- 🛑 แอบฝัง ID จริงไว้ที่ data-order เพื่อให้ DataTables รู้ว่าอันไหนใหม่สุด -->
                                <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;" data-order="<?= $ct['id'] ?>"></td>
                                
                                <td class="align-middle" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($ct['emp_name'] ?? 'ไม่ระบุ') ?></td>
                                <td class="align-middle" style="border-top:1px solid #f4ece2;">
                                    <span style="font-weight:600;color:#3a5a8a;"><?= htmlspecialchars($ct['hospital_name'] ?? 'ไม่ระบุโรงพยาบาล') ?></span><br>
                                    <span style="font-size:11px;color:#9a828a;">
                                        <?php if (!empty($ct['department'])): ?>
                                            <?= htmlspecialchars($ct['department']) ?> | 
                                        <?php endif; ?>
                                        <?= htmlspecialchars($ct['customer_name'] ?? 'ไม่ระบุชื่อ') ?>
                                    </span>
                                </td>
                                
                                <td class="align-middle" style="white-space: pre-line; border-top:1px solid #f4ece2; font-weight:600; color:#6b5760;"><?= trim(htmlspecialchars($ct['category_name'] ?? 'ไม่มีแบรนด์')) ?></td>
                                <td class="align-middle" style="white-space: pre-line; border-top:1px solid #f4ece2; color:#9a828a;"><?= trim(htmlspecialchars($ct['model'] ?? '-')) ?></td>
                                
                                <td class="align-middle text-end" style="border-top:1px solid #f4ece2;font-weight:700;color:#c1466a;white-space:pre-line;"><?= trim($ct['item_prices'] ?? '-') ?></td>
                                
                                <td class="text-center align-middle text-muted" style="border-top:1px solid #f4ece2;">
                                    <?= htmlspecialchars($ct['quotation_date'] ? date('d/m/Y', strtotime($ct['quotation_date'])) : '-') ?>
                                </td>
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;font-weight:600;color:#3a5a8a;">
                                    <?= htmlspecialchars($ct['quotation_no'] ?? '-') ?>
                                </td>
                                <td class="align-middle" style="border-top:1px solid #f4ece2;">
                                    <?php
                                    $badgeClass = 'bg-light text-secondary border';
                                    if (strpos($ct['status'], '1') === 0 || strpos($ct['status'], '2') === 0) $badgeClass = 'bg-pastel-warning text-dark';
                                    if (strpos($ct['status'], '3') === 0 || strpos($ct['status'], '4') === 0 || strpos($ct['status'], '5') === 0) $badgeClass = 'bg-pastel-primary text-primary';
                                    if (strpos($ct['status'], '6') === 0) $badgeClass = 'bg-pastel-success text-success';
                                    if (strpos($ct['status'], '0') === 0) $badgeClass = 'bg-pastel-danger text-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> text-wrap" style="line-height:1.4; padding:5px 8px; font-weight:600; text-align:left;">
                                        <?= htmlspecialchars($ct['status'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="align-middle text-center" style="border-top:1px solid #f4ece2;">
                                    <div style="display:flex;gap:5px;justify-content:center;">
                                        <a href="?page=contact_history&id=<?= $ct['id'] ?>" class="btn-icon-soft btn-icon-secondary" title="ดูประวัติการอัพเดท">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-clock"></use></svg>
                                        </a>
                                        <?php if (!$is_closed): ?>
                                            <a href="?page=contact_update&id=<?= $ct['id'] ?>" class="btn-icon-soft btn-icon-success" title="อัพเดทความคืบหน้า">
                                                <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-refresh"></use></svg>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?page=contact_edit&id=<?= $ct['id'] ?>" class="btn-icon-soft btn-icon-warning" title="แก้ไขใบเสนอราคา">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-edit"></use></svg>
                                        </a>
                                        <?php if ($is_admin): ?>
                                            <a href="index.php?route=contact_delete&id=<?= $ct['id'] ?>" class="btn-icon-soft btn-icon-danger" onclick="return confirm('ยืนยันการลบลำดับนี้?')" title="ลบข้อมูล">
                                                <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-trash"></use></svg>
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?route=contact_print&id=<?= $ct['id'] ?>" target="_blank" class="btn-icon-soft btn-icon-info" title="ดูเอกสาร PDF">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-file"></use></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        // 🛑 เปลี่ยนเป้าหมายเป็น #contactTableV2
        var table = $('#contactTableV2').DataTable({
            "stateSave": true,
            "stateDuration": -1, // จำสถานะไว้จนกว่าจะปิดเบราว์เซอร์ (ช่วยรักษาหน้า 4 เวลาแก้ไขเสร็จ)
            "language": {
                "sProcessing": "กำลังดำเนินการ...", "sLengthMenu": "แสดง _MENU_ รายการ", "sZeroRecords": "ไม่พบข้อมูล",
                "sEmptyTable": "ไม่มีข้อมูลในตาราง", "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sSearch": "", "searchPlaceholder": "ค้นหา...",
                "oPaginate": { "sFirst": "หน้าแรก", "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป", "sLast": "หน้าสุดท้าย" }
            },
            "columnDefs": [{
                "orderable": false,
                "targets": [0, 1, 10] // ล็อกคอลัมน์ Checkbox(0), ลำดับ(1) และ จัดการ(10)
            }],
            "order": [[1, 'desc']] // 🛑 สั่งให้เรียงตาม ID ใหม่ล่าสุด (คอลัมน์ที่ 1) ขึ้นเป็นอันดับแรกสุดเสมอ
        });

        function updateIndexColumn() {
            table.column(1, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }
        table.on('draw.dt', updateIndexColumn);
        updateIndexColumn();

        $('#contactTableV2 tbody').on('click', 'tr.clickable-row td', function(e) {
            var colIndex = $(this).index();
            // ห้ามคลิกแถวเพื่อข้ามหน้า ถ้าไปโดน Checkbox หรือ ปุ่มจัดการ
            if (colIndex === 0 || colIndex === 10) { return; }

            var link = $(this).closest('tr').data('href');
            if (link) { window.location.href = link; }
        });

        $('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        $('#contactTableV2 tbody').on('change', 'input[type="checkbox"]', function() {
            if (!this.checked) {
                var el = $('#selectAll').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
        });

        $('.btn-bulk-action').on('click', function(e) {
            e.preventDefault();
            var actionUrl = $(this).data('action');
            var confirmMsg = $(this).data('msg');
            var form = $('#bulkForm');
            
            var checkedCount = table.$('input[type="checkbox"]:checked').length;
            
            if (checkedCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาติ๊กถูกที่ช่องสี่เหลี่ยมด้านหน้า เพื่อเลือกรายการที่ต้องการจัดการ', confirmButtonColor: '#c1466a' });
                } else {
                    alert('กรุณาติ๊กถูกที่ช่องสี่เหลี่ยมด้านหน้า เพื่อเลือกรายการที่ต้องการจัดการ');
                }
                return false;
            }

            let btnColor = confirmMsg.includes('ลบ') || confirmMsg.includes('อันตราย') ? '#c1466a' : '#2fa572';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'ยืนยันการทำรายการ?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: btnColor,
                    cancelButtonColor: '#b09aa2',
                    confirmButtonText: '<i class="fa-solid fa-check me-1"></i> ใช่, ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitBulkForm(form, actionUrl);
                    }
                });
            } else {
                if (confirm(confirmMsg)) {
                    submitBulkForm(form, actionUrl);
                }
            }
        });

        function submitBulkForm(form, actionUrl) {
            table.$('input[type="checkbox"]:checked').each(function() {
                if (!$.contains(document, this)) {
                    form.append($('<input>').attr('type', 'hidden').attr('name', this.name).val(this.value));
                }
            });
            form.attr('action', actionUrl);
            form.get(0).submit();
        }
    });
</script>