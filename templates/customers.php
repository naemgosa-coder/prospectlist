<?php
$regions = $pdo->query("SELECT id, name_in_thai FROM regions ORDER BY id ASC")->fetchAll();
$sql = "
    SELECT c.*, 
           p.name_in_thai AS province_name,
           r.name_in_thai AS region_name
    FROM customers c
    LEFT JOIN provinces p ON c.province_id = p.id
    LEFT JOIN regions r ON p.region_id = r.id
    ORDER BY c.id DESC
";
$stmt = $pdo->query($sql);
$customers = $stmt->fetchAll();

$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin_user = (isset($_SESSION['position']) && in_array($_SESSION['position'], $allowed_roles));
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .pill-filter {
        display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px;
        padding: 8px 14px; border-radius: 20px; cursor: pointer; transition: all 0.2s;
    }
    .pill-filter.active { background: #c1466a; color: #fff; border: 1px solid #c1466a; font-weight: 600; }
    .pill-filter.inactive { background: #fdfbfc; color: #6b5760; border: 1px solid #eddbe1; }
    .pill-filter.inactive:hover { background: #fdf5f7; border-color: #f4c9d5; color: #c1466a; }
    
    /* โหลดดิ้งตรงกลางตอนที่ตารางกำลังจัดหน้า */
    #tableLoader {
        text-align: center;
        padding: 40px 0;
        color: #c1466a;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* สไตล์สำหรับเบอร์โทรและอีเมลเวลากด */
    .link-contact { text-decoration: none; transition: 0.2s; }
    .link-contact:hover { text-decoration: underline; opacity: 0.8; }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-users"></use></svg> 
            ข้อมูลลูกค้า
        </div>
        <div style="font-size:13.5px;color:#9a828a;margin-top:3px">ฐานข้อมูลโรงพยาบาลและผู้ติดต่อ</div>
    </div>
    <div style="display:flex;gap:9px">
        <a href="?page=customer_add" style="font-size:12.5px;padding:9px 16px;border-radius:11px;background:#c1466a;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-plus"></use></svg> เพิ่มลูกค้าใหม่
        </a>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px" id="regionPillContainer">
    <span class="pill-filter active" data-region="">ทุกภาค</span>
    <?php foreach ($regions as $reg): ?>
        <span class="pill-filter inactive" data-region="<?= htmlspecialchars($reg['name_in_thai']) ?>">
            <?= htmlspecialchars($reg['name_in_thai']) ?>
        </span>
    <?php endforeach; ?>
    <!-- ซ่อน Select เดิมไว้ให้ DataTables ดึงไปใช้ -->
    <select id="regionFilter" style="display:none;">
        <option value="">-- แสดงทุกภาค --</option>
        <?php foreach ($regions as $reg): ?>
            <option value="<?= htmlspecialchars($reg['name_in_thai']) ?>"><?= htmlspecialchars($reg['name_in_thai']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<form action="index.php?route=customer_bulk_delete" method="POST" id="bulkDeleteForm">
    <?php if ($is_admin_user): ?>
        <div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
            <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px">
                <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-list"></use></svg> จัดการรายการที่เลือก:
            </span>
            <button type="submit" onclick="return confirm('⚠️ เตือนขั้นรุนแรง! คุณแน่ใจไหมว่าจะลบข้อมูลลูกค้าที่เลือกทั้งหมดทําลายทิ้งถาวร? การลบนี้ไม่สามารถกู้คืนได้')" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:1px solid #f4c9d5;color:#c1466a;background:#fff;font-weight:600;cursor:pointer;">
                ลบรายการลูกค้าที่เลือก
            </button>
        </div>
    <?php endif; ?>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
        
        <!-- โชว์ตัวโหลดระหว่างรอจัดหน้า -->
        <div id="tableLoader"><i class="fa-solid fa-spinner fa-spin me-2"></i> กำลังโหลดและจัดเรียงข้อมูล...</div>

        <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px; display:none;" id="tableContainer">
            <table id="customerTable" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12.5px; width: 100%;">
                <thead>
                    <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <th width="3%" class="no-sort text-center">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th class="text-center">ลำดับ</th>
                        <th>ภาค</th>
                        <th>จังหวัด</th>
                        <th>โรงพยาบาล</th>
                        <th>แผนก</th>
                        <th>รายชื่อลูกค้า</th>
                        <th class="text-center">เบอร์ติดต่อ</th>
                        <th>อีเมล</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody style="vertical-align:top;">
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $cus): ?>
                            <tr>
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <input type="checkbox" name="customer_ids[]" value="<?= $cus['id'] ?>" class="form-check-input check-item" <?= !$is_admin_user ? 'disabled' : '' ?>>
                                </td>
                                <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;"></td>
                                <td class="align-middle text-center" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cus['region_name'] ?? '-') ?></td>
                                <td class="align-middle" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cus['province_name'] ?? '-') ?></td>
                                <td class="align-middle fw-bold text-dark" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cus['hospital_name']) ?></td>
                                <td class="align-middle text-secondary" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cus['department'] ?? '-') ?></td>
                                <td class="align-middle" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cus['customer_name']) ?></td>
                                
                                <!-- 🛑 กดแล้วโทรออกได้ทันที -->
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <?php if(!empty($cus['phone']) && $cus['phone'] !== '-'): ?>
                                        <!-- ใช้ preg_replace กรองให้เหลือแต่ตัวเลขสำหรับคำสั่ง tel: -->
                                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $cus['phone']) ?>" class="link-contact" style="color:#3f74c4; font-weight:600;" title="กดเพื่อโทรออก">
                                            <?= htmlspecialchars($cus['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                
                                <!-- 🛑 กดแล้วเปิดแอปอีเมลได้ทันที -->
                                <td class="align-middle text-muted" style="border-top:1px solid #f4ece2;">
                                    <?php if(!empty($cus['email']) && $cus['email'] !== '-'): ?>
                                        <a href="mailto:<?= htmlspecialchars($cus['email']) ?>" class="link-contact" style="color:#8a7078;" title="กดเพื่อส่งอีเมล">
                                            <?= htmlspecialchars($cus['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <div style="display:flex;gap:5px;justify-content:center;">
                                        <a href="?page=customer_edit&id=<?= $cus['id'] ?>" class="btn-icon-soft btn-icon-warning" title="แก้ไขข้อมูล">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-edit"></use></svg>
                                        </a>
                                        <?php if ($is_admin_user): ?>
                                            <a href="index.php?route=customer_delete&id=<?= $cus['id'] ?>" class="btn-icon-soft btn-icon-danger" onclick="return confirm('ยืนยันการลบข้อมูลลูกค้ารายนี้?')" title="ลบข้อมูล">
                                                <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-trash"></use></svg>
                                            </a>
                                        <?php endif; ?>
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
        var table = $('#customerTable').DataTable({
            "stateSave": true,
            "stateDuration": -1,
            "language": {
                "sProcessing": "กำลังดำเนินการ...", "sLengthMenu": "แสดง _MENU_ รายการ", "sZeroRecords": "ไม่พบข้อมูล",
                "sEmptyTable": "ไม่มีข้อมูลในตาราง", "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sInfoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ", "sSearch": "", "searchPlaceholder": "ค้นหาโรงพยาบาล...",
                "oPaginate": { "sFirst": "หน้าแรก", "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป", "sLast": "หน้าสุดท้าย" }
            },
            "columnDefs": [{
                "orderable": false,
                "targets": [0, 1, 9] 
            }],
            "order": [[4, 'asc']],
            "initComplete": function() {
                $('#tableLoader').hide();
                $('#tableContainer').fadeIn('fast');
            }
        });

        function updateIndexColumn() {
            table.column(1, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }
        table.on('draw.dt', updateIndexColumn);
        updateIndexColumn();

        $('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).not(':disabled').prop('checked', this.checked);
        });

        $('#customerTable tbody').on('change', 'input[type="checkbox"]', function() {
            if (!this.checked) {
                var el = $('#selectAll').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
        });

        $('#bulkDeleteForm').on('submit', function(e) {
            var form = this;
            var checkedCount = table.$('input[type="checkbox"]:checked').length;
            if (checkedCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกรายการที่ต้องการลบอย่างน้อย 1 รายการ', confirmButtonColor: '#c1466a' });
                } else {
                    alert('กรุณาเลือกรายการที่ต้องการลบอย่างน้อย 1 รายการ');
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
        });

        $('.pill-filter').on('click', function() {
            $('.pill-filter').removeClass('active').addClass('inactive');
            $(this).removeClass('inactive').addClass('active');
            
            var selectedRegion = $(this).attr('data-region');
            $('#regionFilter').val(selectedRegion).trigger('change');
        });

        $('#regionFilter').on('change', function() {
            var selectedRegion = $(this).val();
            if (selectedRegion) {
                var escapedRegion = $.fn.dataTable.util.escapeRegex(selectedRegion);
                table.column(2).search('^' + escapedRegion + '$', true, false).draw();
            } else {
                table.column(2).search('').draw();
            }
        });
    });
</script>