<?php
// บล็อกสิทธิ์พนักงานทั่วไปตั้งแต่เริ่มเข้าหน้าเพจ
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้าจัดการข้อมูลสินค้าครับ";
    header("Location: index.php?page=dashboard");
    exit;
}

//  1. ดึงข้อมูลแบรนด์ / ชื่อสินค้าทั้งหมดมาทำตัวกรอง (Dropdown Filter)
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_name ASC")->fetchAll();

//  2. แก้ไข SQL ให้เชื่อมตาราง (JOIN) เพื่อดึงชื่อแบรนด์ / ชื่อสินค้า (category_name) ออกมาแสดง
$sql = "
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN product_categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
";
$products = $pdo->query($sql)->fetchAll();
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    /* โหลดดิ้งตรงกลางตอนที่ตารางกำลังจัดหน้า */
    #tableLoader {
        text-align: center;
        padding: 40px 0;
        color: #2fa572;
        font-weight: 600;
        font-size: 14px;
    }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <i class="fa-solid fa-boxes-stacked" style="color:#2fa572;"></i> 
            ข้อมูลสินค้าและรุ่น
        </div>
        <div style="font-size:13.5px;color:#9a828a;margin-top:3px">จัดการรุ่นสินค้าและรายละเอียดสำหรับเสนอราคา</div>
    </div>
    <div style="display:flex;gap:9px">
        <a href="?page=product_add" style="font-size:12.5px;padding:9px 16px;border-radius:11px;background:#2fa572;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
            <i class="fa-solid fa-plus"></i> เพิ่มสินค้าใหม่
        </a>
    </div>
</div>

<!-- FILTER BAR -->
<div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px" class="me-2">
            <i class="fa-solid fa-filter"></i> ตัวกรองแบรนด์:
        </span>
        <select class="form-select form-select-sm" id="categoryFilter" style="width: auto; border-radius:10px; border:1px solid #eddbe1; background-color:#fdfbfc; color:#6b5760; font-size:12.5px;">
            <option value="">-- แสดงทั้งหมด --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['category_name']) ?>">
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<form action="index.php?route=product_bulk_delete" method="POST" id="bulkProductForm">
    <div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px">
            <i class="fa-solid fa-list-check"></i> จัดการรายการที่เลือก:
        </span>
        <button type="submit" onclick="return confirm('ยืนยันทำลายลบข้อมูลสินค้ากลุ่มที่เลือกทิ้งถาวร?')" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:1px solid #f4c9d5;color:#c1466a;background:#fff;font-weight:600;cursor:pointer;">
            ลบรายการสินค้าที่เลือก
        </button>
    </div>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
        
        <!-- โชว์ตัวโหลดระหว่างรอจัดหน้า -->
        <div id="tableLoader"><i class="fa-solid fa-spinner fa-spin me-2"></i> กำลังโหลดและจัดเรียงข้อมูล...</div>

        <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px; display:none;" id="tableContainer">
            <!-- 🛑 เพิ่ม style="width:100%" เพื่อบังคับให้ตารางกางเต็มหน้าจอตั้งแต่แรก -->
            <table id="productTable" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:13px; width:100%;">
                <thead>
                    <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <th width="3%" class="text-center"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th width="7%" class="text-center">ลำดับ</th>
                        <th width="20%">แบรนด์ / ชื่อสินค้า</th>
                        <th width="20%">รุ่น (Model)</th>
                        <th width="40%">รายละเอียดสินค้า</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody style="vertical-align:top;">
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                <input type="checkbox" name="product_ids[]" value="<?= $prod['id'] ?>" class="form-check-input check-item">
                            </td>
                            <!--  ปล่อยช่องลำดับให้ว่างไว้ ให้ JS รันให้ -->
                            <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;"></td>
                            <td class="align-middle fw-bold text-primary" style="border-top:1px solid #f4ece2; color:#3a5a8a !important;"><?= htmlspecialchars($prod['category_name'] ?? 'ไม่มีแบรนด์ / ชื่อสินค้า') ?></td>
                            <td class="align-middle text-secondary fw-bold" style="border-top:1px solid #f4ece2; color:#6b5760 !important;"><?= htmlspecialchars($prod['model'] ?? '-') ?></td>
                            
                            <td class="align-middle text-muted small" style="border-top:1px solid #f4ece2; white-space:pre-line; max-width:250px; overflow:hidden; text-overflow:ellipsis;"><?= trim(htmlspecialchars($prod['details'] ?? '-')) ?></td>
                            
                            <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                <div style="display:flex;gap:5px;justify-content:center;">
                                    <a href="?page=product_edit&id=<?= $prod['id'] ?>" class="btn-icon-soft btn-icon-warning" title="แก้ไขข้อมูล">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="index.php?route=product_delete&id=<?= $prod['id'] ?>" class="btn-icon-soft btn-icon-danger" onclick="return confirm('ยืนยันการลบสินค้าชิ้นนี้?')" title="ลบข้อมูล">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
        var table = $('#productTable').DataTable({
			"stateSave": true,
            "stateDuration": -1, // จำสถานะไว้จนกว่าจะปิดเบราว์เซอร์
            "language": {
                "sProcessing": "กำลังดำเนินการ...",
                "sLengthMenu": "แสดง _MENU_ รายการ",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sEmptyTable": "ไม่มีข้อมูลสินค้าในระบบ",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sSearch": "", "searchPlaceholder": "ค้นหาสินค้าด่วน...",
                "oPaginate": { "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป" }
            },
            "columnDefs": [{
                "orderable": false,
                "targets": [0, 1, 5] //  ล็อกไม่ให้คลิกเรียงที่คอลัมน์ ลำดับ(1) Checkbox(0) และจัดการ(5)
            }],
            "order": [[2, 'asc']], // ให้เรียงจากแบรนด์เป็นค่าเริ่มต้น
            "initComplete": function() {
                // 🛑 เมื่อโหลดและจัดหน้าเสร็จ ให้ซ่อนตัวโหลด แล้วโชว์ตารางแบบนุ่มนวล
                $('#tableLoader').hide();
                $('#tableContainer').fadeIn('fast');
            }
        });

// ✅ โค้ดใหม่ เร็วกว่า 10 เท่า และไม่ทำให้การจำหน้า (State) พัง
        function updateIndexColumn() {
            // หมายเหตุ: เลข 1 คือตำแหน่งคอลัมน์ของ "ลำดับ" (นับ 0, 1, 2...) ให้แก้ให้ตรงกับหน้าเว็บนั้นๆ
            table.column(1, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }
        table.on('draw.dt', updateIndexColumn);
        updateIndexColumn();

        //  ระบบค้นหาผ่าน Dropdown แบรนด์ / ชื่อสินค้า
        $('#categoryFilter').on('change', function() {
            var selectedCategory = $(this).val();
            if (selectedCategory) {
                // เพิ่มการ Escape อักขระพิเศษสำหรับ Regex ป้องกันการ Error ค้นหาไม่เจอ
                var escapedCategory = $.fn.dataTable.util.escapeRegex(selectedCategory);
                table.column(2).search('^' + escapedCategory + '$', true, false).draw();
            } else {
                table.column(2).search('').draw();
            }
        });

        $('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        $('#bulkProductForm').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            var checkedCount = table.$('input[type="checkbox"]:checked').length;
            if (checkedCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการก่อนทำการลบครับ', confirmButtonColor: '#c1466a' });
                } else {
                    alert('กรุณาเลือกสินค้าอย่างน้อย 1 รายการก่อนทำการลบครับ');
                }
                return false;
            }
            table.$('input[type="checkbox"]:checked').each(function() {
                if (!$.contains(document, this)) {
                    if (this.checked) {
                        $(form).append($('<input>').attr('type', 'hidden').attr('name', this.name).val(this.value));
                    }
                }
            });
            form.submit();
        });
    });
</script>