<?php
// คำสั่งดึงข้อมูลพนักงานทั้งหมด (เรียงจากพนักงานที่เพิ่มล่าสุด)
$stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
$employees = $stmt->fetchAll();

// เช็คสิทธิ์พนักงานเพื่อเปิดกล่องและปุ่ม Bulk Delete
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
$is_admin_user = (isset($_SESSION['position']) && in_array($_SESSION['position'], $allowed_roles));
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-users"></use></svg> 
            ข้อมูลพนักงาน
        </div>
        <div style="font-size:13.5px;color:#9a828a;margin-top:3px">จัดการบัญชีผู้ใช้งานและสิทธิ์ในระบบ</div>
    </div>
    <div style="display:flex;gap:9px">
        <a href="?page=employee_add" style="font-size:12.5px;padding:9px 16px;border-radius:11px;background:#3f74c4;color:#fff;font-weight:600;text-decoration:none;display:inline-flex;gap:7px;align-items:center">
            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-plus"></use></svg> เพิ่มพนักงานใหม่
        </a>
    </div>
</div>

<form action="index.php?route=employee_bulk_delete" method="POST" id="bulkDeleteEmpForm">

    <?php if ($is_admin_user): ?>
        <div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
            <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px">
                <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-list"></use></svg> จัดการรายการที่เลือก:
            </span>
            <button type="submit" onclick="return confirm('⚠️ คำเตือนขั้นรุนแรง! คุณแน่ใจหรือไม่ที่จะลบพนักงานกลุ่มที่เลือกออกจากระบบอย่างถาวร? การดำเนินการนี้ไม่สามารถกู้คืนได้')" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:1px solid #f4c9d5;color:#c1466a;background:#fff;font-weight:600;cursor:pointer;">
                ลบรายชื่อพนักงานที่เลือก
            </button>
        </div>
    <?php endif; ?>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
        <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
            <table id="employeeTable" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:12.5px;">
                <thead>
                    <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <th width="3%" class="no-sort text-center">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th width="5%" class="text-center">ลำดับ</th>
                        <th width="15%" class="text-center">รหัสพนักงาน</th>
                        <th width="20%">ชื่อพนักงาน</th>
                        <th width="15%" class="text-center">ตำแหน่ง</th>
                        <th width="18%">อีเมล</th>
                        <th width="14%">ชื่อผู้ใช้งาน</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody style="vertical-align:top;">
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <input type="checkbox" name="employee_ids[]" value="<?= $emp['id'] ?>" class="form-check-input check-item" <?= !$is_admin_user ? 'disabled' : '' ?>>
                                </td>
                                <!-- ปล่อยช่องลำดับให้ว่างไว้ ให้ JS รันให้ -->
                                <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;"></td>
                                <td class="text-center align-middle fw-bold text-secondary" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($emp['sr_no']) ?></td>
                                <td class="align-middle fw-bold text-dark" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($emp['fullname']) ?></td>
                                <td class="align-middle text-center" style="border-top:1px solid #f4ece2;">
                                    <?php
                                        $posClass = 'bg-light text-secondary border';
                                        if (in_array($emp['position'], ['Admin', 'Administrator', 'แอดมิน'])) $posClass = 'bg-pastel-danger text-danger';
                                        elseif (in_array($emp['position'], ['ผู้บริหาร', 'Executive'])) $posClass = 'bg-pastel-primary text-primary';
                                        else $posClass = 'bg-pastel-success text-success';
                                    ?>
                                    <span class="badge <?= $posClass ?>" style="padding:5px 8px; font-weight:600;"><?= htmlspecialchars($emp['position']) ?></span>
                                </td>
                                <td class="align-middle text-muted" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($emp['email']) ?></td>
                                <td class="align-middle" style="border-top:1px solid #f4ece2;color:#3a5a8a;font-weight:600;"><?= htmlspecialchars($emp['username']) ?></td>
                                <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                    <div style="display:flex;gap:5px;justify-content:center;">
                                        <a href="?page=employee_edit&id=<?= $emp['id'] ?>" class="btn-icon-soft btn-icon-warning" title="แก้ไขข้อมูล">
                                            <svg width="15" height="15" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-edit"></use></svg>
                                        </a>
                                        <?php if ($is_admin_user): ?>
                                            <a href="index.php?route=employee_delete&id=<?= $emp['id'] ?>" class="btn-icon-soft btn-icon-danger" onclick="return confirm('ยืนยันการลบพนักงานรายนี้?')" title="ลบข้อมูล">
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
        var table = $('#employeeTable').DataTable({
			"stateSave": true,
            "stateDuration": -1, // จำสถานะไว้จนกว่าจะปิดเบราว์เซอร์
            "language": {
                "sProcessing": "กำลังดำเนินการ...",
                "sLengthMenu": "แสดง _MENU_ รายการ",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sEmptyTable": "ไม่มีข้อมูลในตาราง",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                "sSearch": "", "searchPlaceholder": "ค้นหาพนักงาน...",
                "oPaginate": { "sFirst": "หน้าแรก", "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป", "sLast": "หน้าสุดท้าย" }
            },
            "columnDefs": [{
                "orderable": false,
                "targets": [0, 1, 7] // ล็อกคอลัมน์แรก Checkbox(0), ลำดับ(1) และ จัดการ(7)
            }],
            "order": [[2, 'asc']] // ให้เรียงตามรหัสพนักงาน(2) เป็นค่าเริ่มต้น
        });

        function updateIndexColumn() {
            // หมายเหตุ: เลข 1 คือตำแหน่งคอลัมน์ของ "ลำดับ" (นับ 0, 1, 2...) ให้แก้ให้ตรงกับหน้าเว็บนั้นๆ
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

        $('#employeeTable tbody').on('change', 'input[type="checkbox"]', function() {
            if (!this.checked) {
                var el = $('#selectAll').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
        });

        $('#bulkDeleteEmpForm').on('submit', function(e) {
            e.preventDefault(); 
            var form = this;
            var checkedCount = table.$('input[type="checkbox"]:checked').length;
            
            if (checkedCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกรายชื่อพนักงานที่ต้องการลบอย่างน้อย 1 รายการ', confirmButtonColor: '#c1466a' });
                } else {
                    alert('กรุณาเลือกรายชื่อพนักงานที่ต้องการลบอย่างน้อย 1 รายการ');
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