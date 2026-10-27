<?php
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    header("Location: ?page=employees");
    exit;
}
?>

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
                แก้ไขข้อมูลพนักงาน
            </div>
        </div>
        <a href="?page=employees" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการพนักงาน</a>
    </div>

    <div class="card-pastel-form">
        <div class="header-gradient-warn">
            <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-user"></use></svg>
            <div style="font-size:16px;font-weight:600">อัปเดตข้อมูลบัญชีผู้ใช้ระบบ</div>
        </div>

        <div class="card-body p-4">
            <form action="index.php?route=employee_edit_action" method="POST">
                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รหัสพนักงาน (SR_NO) <span class="text-danger">*</span></label>
                        <input type="text" name="sr_no" class="form-control" value="<?= htmlspecialchars($emp['sr_no']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control fw-bold text-dark" value="<?= htmlspecialchars($emp['fullname']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ตำแหน่ง</label>
                        <select name="position" class="form-select" required>
                            <option value="ผู้บริหาร" <?= $emp['position'] == 'ผู้บริหาร' ? 'selected' : '' ?>>ผู้บริหาร</option>
                            <option value="พนักงานขาย" <?= $emp['position'] == 'พนักงานขาย' ? 'selected' : '' ?>>พนักงานขาย</option>
                            <option value="แอดมิน" <?= $emp['position'] == 'แอดมิน' ? 'selected' : '' ?>>แอดมิน</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($emp['email']) ?>">
                    </div>
                </div>

                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control text-primary fw-bold" value="<?= htmlspecialchars($emp['username']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password ใหม่ <br><span style="font-size:10.5px;color:#a89aa0;font-weight:400;">(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="พิมพ์รหัสผ่านใหม่...">
                    </div>
                </div>

                <div class="text-end">
                    <a href="?page=employees" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                    <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#e09a1f;color:#fff;font-weight:600;border:none;">
                        <i class="fa-solid fa-save"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>