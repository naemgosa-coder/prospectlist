<?php
// เช็คสิทธิ์แอดมิน
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้าจัดการข้อมูลสินค้าครับ";
    header("Location: index.php?page=dashboard");
    exit;
}

// ดึงหมวดหมู่สินค้าทั้งหมดมาทำ Dropdown
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_name ASC")->fetchAll();
?>

<!-- 🛑 เพิ่ม CSS ของ TomSelect สำหรับทำ Dropdown ค้นหา -->
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
    .header-gradient-success {
        background: linear-gradient(135deg, #2fa572, #38c287);
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
        border-color: #2fa572;
        box-shadow: 0 0 0 0.2rem rgba(47, 165, 114, 0.15);
    }
    .form-label {
        font-size: 12.5px;
        color: #8a7078;
        font-weight: 600;
        margin-bottom: 6px;
    }
</style>

<!-- 🛑 เพิ่ม div คลุมจัดให้อยู่ตรงกลางหน้าจอ (ขยายขนาดเป็น 1000px เพราะมี 2 คอลัมน์) -->
<div style="max-width: 1000px; margin: 0 auto;">

    <div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
            <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
                <i class="fa-solid fa-box-open" style="color:#2fa572;"></i> 
                เพิ่มสินค้าใหม่
            </div>
        </div>
        <a href="?page=products" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการสินค้า</a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card-pastel-form">
                <div class="header-gradient-success">
                    <i class="fa-solid fa-box" style="font-size:18px;"></i>
                    <div style="font-size:16px;font-weight:600">รายละเอียดสินค้า</div>
                </div>
                <div class="card-body p-4">
                    <form action="index.php?route=product_add_action" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-primary">ชื่อสินค้า / แบรนด์ <span class="text-danger">*</span></label>
                                <!-- 🛑 เพิ่ม id="category_select" เพื่อให้ JS ใช้งาน -->
                                <select class="form-select" name="category_id" id="category_select" required placeholder="พิมพ์ค้นหาแบรนด์...">
                                    <option value="">-- เลือกแบรนด์ --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">รุ่น (Model) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fw-bold text-dark" name="model" required placeholder="ระบุรุ่น...">
                                <input type="hidden" name="product_name" value="-">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รายละเอียดสินค้าเพิ่มเติม</label>
                            <textarea class="form-control" name="details" rows="4" placeholder="ระบุรายละเอียด, สเปค หรือข้อมูลเพิ่มเติม..."></textarea>
                        </div>

                        <div class="text-end mt-4 pt-3 border-top" style="border-color:#f4e6ec !important;">
                            <a href="?page=products" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                            <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#2fa572;color:#fff;font-weight:600;border:none;">
                                <i class="fa-solid fa-save"></i> บันทึกข้อมูลสินค้า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ส่วนนำเข้าไฟล์ CSV (Import) -->
        <div class="col-md-5">
            <div style="background:#fff;border-radius:18px;padding:24px;box-shadow:0 6px 18px rgba(150,90,110,.08); border: 2px dashed #e3f4ea;">
                <div style="text-align:center; margin-bottom:16px;">
                    <i class="fa-solid fa-file-import" style="font-size:36px; color:#2fa572; margin-bottom:10px;"></i>
                    <h5 style="font-weight:600; color:#4a3b40;">Import ข้อมูลสินค้า</h5>
                    <p style="font-size:12px; color:#8a7078; margin:0;">อัปโหลดไฟล์ CSV เพื่อนำเข้าข้อมูลสินค้าทีละหลายรายการ</p>
                </div>

                <div style="background:#fdfbfc; border:1px solid #eddbe1; border-radius:12px; padding:12px; margin-bottom:20px; text-align:center;">
                    <span style="font-size:12px; color:#6b5760; display:block; margin-bottom:8px;">ยังไม่มีฟอร์แมตโครงสร้างไฟล์ใช่ไหม?</span>
                    <a href="index.php?route=product_template_download" style="font-size:12px;padding:6px 12px;border-radius:8px;border:1px solid #3f74c4;color:#3f74c4;background:#e6eefc;text-decoration:none;display:inline-block;font-weight:600;">
                        <i class="fa-solid fa-download me-1"></i> ดาวน์โหลดไฟล์ตัวอย่าง (CSV)
                    </a>
                </div>

                <form action="index.php?route=product_import_action" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px;color:#8a7078;font-weight:600;">เลือกไฟล์ CSV ที่ต้องการอัปโหลด:</label>
                        <input class="form-control" type="file" id="importFile" name="importFile" accept=".csv" required style="font-size:12px;">
                        <div class="form-text mt-2" style="font-size: 11px; color:#c1466a;">
                            * ข้อแนะนำ: หัวตารางในไฟล์ตัวอย่างต้องเป็น: ชื่อสินค้า, รุ่น, รายละเอียด
                        </div>
                    </div>
                    <button type="submit" style="width:100%;font-size:13px;padding:10px;border-radius:10px;background:#2fa572;color:#fff;font-weight:600;border:none;">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> เริ่มต้น Import ข้อมูลสินค้า
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 🛑 สคริปต์จัดการ Dropdown ค้นหาและป้องกันการพิมพ์ชื่อใหม่ -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect("#category_select", {
            create: false, // 🛑 บังคับห้ามสร้างชื่อแบรนด์ใหม่เด็ดขาด
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>