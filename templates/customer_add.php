<?php
$stmt_prov = $pdo->query("SELECT Id, name_in_thai FROM provinces ORDER BY name_in_thai ASC");
$provinces = $stmt_prov->fetchAll();
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .card-pastel-form {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 18px rgba(150,90,110,.08);
        overflow: hidden;
        margin-bottom: 20px;
        border: none;
    }
    .header-gradient-1 {
        background: linear-gradient(135deg, #3f74c4, #4a86d6);
        color: #fff;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #eddbe1;
        background: #fdfbfc;
        font-size: 13px;
        padding: 10px 12px;
        color: #4a3b40;
    }
    .form-control:focus, .form-select:focus {
        border-color: #c1466a;
        box-shadow: 0 0 0 0.2rem rgba(193, 70, 106, 0.15);
    }
    .form-label {
        font-size: 12.5px;
        color: #8a7078;
        font-weight: 600;
        margin-bottom: 6px;
    }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#3f74c4;"><use href="#i-plus"></use></svg> 
            เพิ่มลูกค้าใหม่
        </div>
    </div>
    <a href="?page=customers" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการลูกค้า</a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card-pastel-form">
            <div class="header-gradient-1">
                <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-building"></use></svg>
                <div style="font-size:16px;font-weight:600">ข้อมูลโรงพยาบาล/ลูกค้า</div>
            </div>
            <div class="card-body p-4">
                <form action="index.php?route=customer_add_action" method="POST">

                    <div class="mb-3">
                        <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                        <select name="province_id" id="province_select" required placeholder="-- เลือกจังหวัด --">
                            <option value=""></option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= $prov['Id'] ?>"><?= htmlspecialchars($prov['name_in_thai']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">โรงพยาบาล / บริษัท <span class="text-danger">*</span></label>
                        <input type="text" name="hospital_name" class="form-control fw-bold text-dark" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">แผนก <span class="text-danger">*</span></label>
                            <input type="text" name="department" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รายชื่อลูกค้า / ผู้ติดต่อ <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row border-bottom pb-3 mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">เบอร์ติดต่อ <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control text-primary fw-bold" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">อีเมล</label>
                            <input type="email" name="email" class="form-control" placeholder="example@email.com">
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="?page=customers" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                        <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#3f74c4;color:#fff;font-weight:600;border:none;">
                            <i class="fa-solid fa-save"></i> บันทึกข้อมูลลูกค้า
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนนำเข้าไฟล์ CSV (Import) -->
    <div class="col-md-5">
        <div style="background:#fff;border-radius:18px;padding:24px;box-shadow:0 6px 18px rgba(150,90,110,.08); border: 2px dashed #e6eefc;">
            <div style="text-align:center; margin-bottom:16px;">
                <svg width="40" height="40" viewBox="0 0 24 24" style="fill:none;stroke:#3f74c4;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;margin-bottom:10px;"><use href="#i-upload"></use></svg>
                <h5 style="font-weight:600; color:#4a3b40;">Import ข้อมูลลูกค้า</h5>
                <p style="font-size:12px; color:#8a7078; margin:0;">อัปโหลดไฟล์ CSV เพื่อนำเข้าข้อมูลลูกค้าทีละหลายรายการ</p>
            </div>
            
            <div style="background:#fdfbfc; border:1px solid #eddbe1; border-radius:12px; padding:12px; margin-bottom:20px; text-align:center;">
                <span style="font-size:12px; color:#6b5760; display:block; margin-bottom:8px;">ยังไม่มีฟอร์แมตไฟล์ใช่ไหม?</span>
                <a href="index.php?route=customer_template_download" style="font-size:12px;padding:6px 12px;border-radius:8px;border:1px solid #2fa572;color:#2fa572;background:#e3f4ea;text-decoration:none;display:inline-block;font-weight:600;">
                    <i class="fa-solid fa-download me-1"></i> ดาวน์โหลดไฟล์ตัวอย่าง (CSV)
                </a>
            </div>

            <form action="index.php?route=customer_import_action" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;color:#8a7078;font-weight:600;">เลือกไฟล์ที่ต้องการอัปโหลด:</label>
                    <input class="form-control" type="file" id="importCustomer" name="importCustomer" accept=".csv" required style="font-size:12px;">
                </div>
                <button type="submit" style="width:100%;font-size:13px;padding:10px;border-radius:10px;background:#3f74c4;color:#fff;font-weight:600;border:none;">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> เริ่มต้น Import ข้อมูล
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect("#province_select", {
            create: false,
            maxOptions: null,
            sortField: { field: "text", direction: "asc" }
        });
    });
</script>