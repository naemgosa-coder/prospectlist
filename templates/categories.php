<?php
// เช็คสิทธิ์ (ให้เฉพาะ Admin/ผู้บริหารเข้าจัดการได้)
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้าจัดการแบรนด์ / ชื่อสินค้าครับ";
    header("Location: index.php?page=dashboard");
    exit;
}

$categories = $pdo->query("SELECT * FROM product_categories ORDER BY id DESC")->fetchAll();
?>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <i class="fa-solid fa-tags" style="color:#3f74c4;"></i> 
            จัดการแบรนด์ / ชื่อสินค้า
        </div>
        <div style="font-size:13.5px;color:#9a828a;margin-top:3px">เพิ่ม ลบ และแก้ไขหมวดหมู่แบรนด์สินค้าในระบบ</div>
    </div>
    <div style="display:flex;gap:9px">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="font-size:12.5px;padding:9px 16px;border-radius:11px;background:#3f74c4;color:#fff;font-weight:600;border:none;display:inline-flex;gap:7px;align-items:center">
            <i class="fa-solid fa-plus"></i> เพิ่มแบรนด์ใหม่
        </button>
    </div>
</div>

<form action="index.php?route=category_action" method="POST" id="bulkCategoryForm">
    <input type="hidden" name="action" value="bulk_delete">

    <div style="background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 6px 18px rgba(150,90,110,.08);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:12.5px;color:#8a7078;font-weight:600;display:flex;align-items:center;gap:6px">
            <i class="fa-solid fa-list-check"></i> จัดการรายการที่เลือก:
        </span>
        <button type="submit" onclick="return confirm('ยืนยันการลบแบรนด์ / ชื่อสินค้าที่เลือกทั้งหมด? (สินค้าในแบรนด์เหล่านี้จะกลายเป็นไม่มีแบรนด์ / ชื่อสินค้า)')" style="font-size:12.5px;padding:6px 12px;border-radius:9px;border:1px solid #f4c9d5;color:#c1466a;background:#fff;font-weight:600;cursor:pointer;">
            ลบแบรนด์ที่เลือก
        </button>
    </div>

    <div style="background:#fff;border-radius:18px;padding:20px 22px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
        <div class="table-responsive" style="border:1px solid #f4e6ec;border-radius:12px">
            <table id="categoryTable" class="table table-hover mb-0 w-100 text-nowrap" style="font-size:13px;">
                <thead>
                    <tr style="background:#faf0f4;color:#7a5d67;text-align:left">
                        <th width="3%" class="no-sort text-center"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th width="10%" class="text-center">ลำดับ</th>
                        <th width="67%">ชื่อแบรนด์ / ชื่อสินค้า</th>
                        <th width="20%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody style="vertical-align:middle;">
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>" class="form-check-input check-item">
                            </td>
                            <!-- ปล่อยช่องลำดับให้ว่างไว้ เดี๋ยว JavaScript จะมารันเลขให้เอง -->
                            <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;"></td>
                            <td class="align-middle fw-bold text-dark" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td class="text-center align-middle" style="border-top:1px solid #f4ece2;">
                                <div style="display:flex;gap:5px;justify-content:center;">
                                    <button type="button" class="btn-icon-soft btn-icon-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal" 
                                            data-id="<?= $cat['id'] ?>" 
                                            data-name="<?= htmlspecialchars($cat['category_name']) ?>" title="แก้ไขข้อมูล">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="index.php?route=category_action&action=delete&id=<?= $cat['id'] ?>" class="btn-icon-soft btn-icon-danger" onclick="return confirm('ยืนยันการลบแบรนด์ / ชื่อสินค้ารายนี้? (หากลบ สินค้าที่อยู่ในแบรนด์ / ชื่อสินค้ารายนี้จะกลายเป็นไม่มีแบรนด์ / ชื่อสินค้า)')" title="ลบข้อมูล">
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

<!-- Modal เพิ่มแบรนด์ -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden; border:none; box-shadow:0 10px 40px rgba(120,60,80,.15);">
            <form action="index.php?route=category_action" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header" style="background:linear-gradient(135deg,#3f74c4,#4a86d6); color:#fff; border-bottom:none;">
                    <h5 class="modal-title" style="font-weight:600; font-size:16px;">
                        <i class="fa-solid fa-plus me-2"></i> เพิ่มแบรนด์ / ชื่อสินค้า
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">ชื่อแบรนด์ / ชื่อสินค้า</label>
                        <input type="text" class="form-control" name="category_name" required placeholder="เช่น เครื่องมือแพทย์, อุปกรณ์ไอที..." style="border-radius:10px; border:1px solid #eddbe1; padding:10px 12px; font-size:13px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f4e6ec;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-size:13px;border-radius:10px;border:1px solid #eddbe1;color:#8a7078;">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="font-size:13px;border-radius:10px;background:#3f74c4;border:none;font-weight:600;">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขแบรนด์ -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden; border:none; box-shadow:0 10px 40px rgba(120,60,80,.15);">
            <form action="index.php?route=category_action" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_cat_id">
                <div class="modal-header" style="background:linear-gradient(135deg,#f0b429,#e09a1f); color:#fff; border-bottom:none;">
                    <h5 class="modal-title" style="font-weight:600; font-size:16px;">
                        <i class="fa-solid fa-pen-to-square me-2"></i> แก้ไขแบรนด์ / ชื่อสินค้า
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">ชื่อแบรนด์ / ชื่อสินค้า</label>
                        <input type="text" class="form-control" name="category_name" id="edit_cat_name" required style="border-radius:10px; border:1px solid #eddbe1; padding:10px 12px; font-size:13px; font-weight:bold; color:#4a3b40;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f4e6ec;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-size:13px;border-radius:10px;border:1px solid #eddbe1;color:#8a7078;">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning text-white" style="font-size:13px;border-radius:10px;background:#e09a1f;border:none;font-weight:600;">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        // 🛑 เติมวงเล็บ ({ ให้ตรงนี้ครับ
        var table = $('#categoryTable').DataTable({
            "stateSave": true,
            "stateDuration": -1, // จำสถานะไว้จนกว่าจะปิดเบราว์เซอร์
            "language": {
                "sLengthMenu": "แสดง _MENU_ รายการ", "sZeroRecords": "ไม่พบข้อมูล",
                "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", "sSearch": "", "searchPlaceholder": "ค้นหาแบรนด์...",
                "oPaginate": { "sPrevious": "ก่อนหน้า", "sNext": "ถัดไป" }
            },
            "columnDefs": [{ 
                "orderable": false, 
                "targets": [0, 1, 3] // ล็อกคอลัมน์
            }],
            "order": [[2, 'asc']] // ให้มันเรียงค่าเริ่มต้นตามชื่อแบรนด์แทน
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

        // ดึงข้อมูลใส่ Modal แก้ไข
        $('#editCategoryModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var modal = $(this);
            modal.find('#edit_cat_id').val(id);
            modal.find('#edit_cat_name').val(name);
        });

        $('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        $('#categoryTable tbody').on('change', 'input[type="checkbox"]', function() {
            if (!this.checked) {
                var el = $('#selectAll').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
        });

        $('#bulkCategoryForm').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            var checkedCount = table.$('input[type="checkbox"]:checked').length;

            if (checkedCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาเลือกหมวดหมู่ที่ต้องการลบอย่างน้อย 1 รายการครับ', confirmButtonColor: '#0d6efd' });
                } else {
                    alert('กรุณาเลือกหมวดหมู่ที่ต้องการลบอย่างน้อย 1 รายการครับ');
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