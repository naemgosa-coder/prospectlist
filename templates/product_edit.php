<?php
// เช็คสิทธิ์แอดมิน
$user_position = $_SESSION['position'] ?? '';
$allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
if (!in_array($user_position, $allowed_roles)) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้าจัดการข้อมูลสินค้าครับ";
    header("Location: index.php?page=dashboard");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch();

if (!$prod) {
    header("Location: ?page=products");
    exit;
}

// ดึงหมวดหมู่สินค้าทั้งหมดมาทำ Dropdown
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_name ASC")->fetchAll();
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

<!-- 🛑 เพิ่ม div คลุมเพื่อจัดให้อยู่ตรงกลางจอแบบพอดี -->
<div style="max-width: 800px; margin: 0 auto;">

    <div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
            <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
                <i class="fa-solid fa-pen-to-square" style="color:#e09a1f;"></i> 
                แก้ไขข้อมูลสินค้า
            </div>
        </div>
        <a href="?page=products" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการสินค้า</a>
    </div>

    <div class="card-pastel-form">
        <div class="header-gradient-warn">
            <i class="fa-solid fa-box" style="font-size:18px;"></i>
            <div style="font-size:16px;font-weight:600">อัปเดตรายละเอียดสินค้าและรุ่น</div>
        </div>
        <div class="card-body p-4">
            <form action="index.php?route=product_edit_action" method="POST">
                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-primary">หมวดหมู่สินค้า <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($prod['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <input type="hidden" name="product_name" value="-">
                        <label class="form-label">รุ่น (Model)</label>
                        <input type="text" class="form-control fw-bold text-dark" name="model" value="<?= htmlspecialchars($prod['model'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">รายละเอียดสินค้าเพิ่มเติม</label>
                    <textarea class="form-control" name="details" rows="5"><?= htmlspecialchars($prod['details'] ?? '') ?></textarea>
                </div>

                <div class="text-end mt-4 pt-3 border-top" style="border-color:#f4e6ec !important;">
                    <a href="?page=products" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                    <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#e09a1f;color:#fff;font-weight:600;border:none;">
                        <i class="fa-solid fa-save"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>