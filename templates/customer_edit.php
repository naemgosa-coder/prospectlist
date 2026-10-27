<?php
$id = $_GET['id'] ?? 0;
$customer = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$customer->execute([$id]);
$cus = $customer->fetch();

if (!$cus) {
    header("Location: ?page=customers");
    exit;
}

$provinces = $pdo->query("SELECT Id, name_in_thai FROM provinces ORDER BY name_in_thai ASC")->fetchAll();
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
    .header-gradient-warn {
        background: linear-gradient(135deg, #f0b429, #e09a1f);
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
        border-color: #f0b429;
        box-shadow: 0 0 0 0.2rem rgba(240, 180, 41, 0.15);
    }
    .form-label {
        font-size: 12.5px;
        color: #8a7078;
        font-weight: 600;
        margin-bottom: 6px;
    }
</style>

<!-- 🛑 เพิ่ม div คลุมจัดให้อยู่ตรงกลางแบบพอดี -->
<div style="max-width: 800px; margin: 0 auto;">

    <div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
            <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
                <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#e09a1f;"><use href="#i-edit"></use></svg> 
                แก้ไขข้อมูลลูกค้า
            </div>
        </div>
        <a href="?page=customers" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการลูกค้า</a>
    </div>

    <div class="card-pastel-form">
        <div class="header-gradient-warn">
            <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-building"></use></svg>
            <div style="font-size:16px;font-weight:600">อัปเดตข้อมูลลูกค้าและโรงพยาบาล</div>
        </div>
        <div class="card-body p-4">
            <form action="index.php?route=customer_edit_action" method="POST">
                <input type="hidden" name="id" value="<?= $cus['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                    <select name="province_id" id="province_select" required>
                        <option value=""></option>
                        <?php foreach ($provinces as $prov): ?>
                            <option value="<?= $prov['Id'] ?>" <?= $cus['province_id'] == $prov['Id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['name_in_thai']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">โรงพยาบาล / บริษัท <span class="text-danger">*</span></label>
                    <input type="text" name="hospital_name" class="form-control fw-bold text-dark" value="<?= htmlspecialchars($cus['hospital_name']) ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">แผนก <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($cus['department'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รายชื่อลูกค้า / ผู้ติดต่อ <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($cus['customer_name']) ?>" required>
                    </div>
                </div>

                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">เบอร์ติดต่อ <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control text-primary fw-bold" value="<?= htmlspecialchars($cus['phone']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cus['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="text-end">
                    <a href="?page=customers" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                    <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#e09a1f;color:#fff;font-weight:600;border:none;">
                        <i class="fa-solid fa-save"></i> บันทึกการแก้ไข
                    </button>
                </div>
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
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>