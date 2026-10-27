<style>
    .card-pastel-form {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 18px rgba(150,90,110,.08);
        overflow: hidden;
        margin-bottom: 20px;
        border: none;
        max-width: 800px;
    }
    .header-gradient-primary {
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
        border-color: #3f74c4;
        box-shadow: 0 0 0 0.2rem rgba(63, 116, 196, 0.15);
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
            เพิ่มพนักงานใหม่
        </div>
    </div>
    <a href="?page=employees" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการพนักงาน</a>
</div>

<div class="card-pastel-form">
    <div class="header-gradient-primary">
        <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-user"></use></svg>
        <div style="font-size:16px;font-weight:600">ข้อมูลรายละเอียดพนักงาน</div>
    </div>
    <div class="card-body p-4">
        <form action="index.php?route=employee_add_action" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="sr_no" class="form-label">รหัสพนักงาน <span class="text-danger">*</span></label> 
                    <input type="text" class="form-control" id="sr_no" name="sr_no" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fullname" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label> 
                    <input type="text" class="form-control fw-bold text-dark" id="fullname" name="fullname" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label> 
                    <input type="email" class="form-control" id="email" name="email" required placeholder="example@domain.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="position" class="form-label">ตำแหน่ง <span class="text-danger">*</span></label> 
                    <select class="form-select" id="position" name="position" required>
                        <option value="">-- เลือกตำแหน่ง --</option>
                        <option value="ผู้บริหาร">ผู้บริหาร</option>
                        <option value="พนักงานขาย">พนักงานขาย</option>
                        <option value="แอดมิน">แอดมิน</option>
                    </select>
                </div>
            </div>

            <div class="row border-bottom pb-3 mb-3">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label> 
                    <input type="text" class="form-control text-primary fw-bold" id="username" name="username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">รหัสผ่าน (Password) <span class="text-danger">*</span></label> 
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <div class="text-end">
                <a href="?page=employees" style="font-size:13px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;margin-right:8px;">ยกเลิก</a>
                <button type="submit" style="font-size:13px;padding:10px 24px;border-radius:11px;background:#3f74c4;color:#fff;font-weight:600;border:none;">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>