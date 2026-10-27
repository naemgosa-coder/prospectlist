<?php
$id = $_GET['id'] ?? 0;

// 1. ดึงข้อมูลหลักพนักงานและลูกค้า
$stmt = $pdo->prepare("
    SELECT ct.*, 
           e.sr_no, e.fullname AS emp_name,
           c.hospital_name, c.department, c.customer_name, c.phone AS customer_phone
    FROM contacts ct
    LEFT JOIN employees e ON ct.employee_id = e.id
    LEFT JOIN customers c ON ct.customer_id = c.id
    WHERE ct.id = ?
");
$stmt->execute([$id]);
$ct = $stmt->fetch();
if (!$ct) {
    header("Location: ?page=contacts");
    exit;
}

//  2. ดึงรายการสินค้าทั้งหมดในดีลนี้ออกมาแสดงผล
$stmt_items = $pdo->prepare("
	SELECT ci.*, pc.category_name, p.model, p.details
	FROM contact_items ci
	LEFT JOIN products p ON ci.product_id = p.id
	LEFT JOIN product_categories pc ON p.category_id = pc.id
	WHERE ci.contact_id = ?
	ORDER BY ci.id ASC
");
$stmt_items->execute([$id]);
$saved_items = $stmt_items->fetchAll();

// เตรียมตัวแปรเช็คค่าปัญหา/สนับสนุน
$prob_options = ["ลูกค้ายังไม่ชัดเจนงบ", "TOR อาจล็อกสเปค", "มีผู้มีอำนาจตัดสินใจหลายคน", "สินค้ามีราคาสูงกว่าคู่แข่ง"];
$curr_prob = htmlspecialchars($ct['issues'] ?? '');
$is_other_prob = $curr_prob && !in_array($curr_prob, $prob_options);

$supp_options = ["ต้องการผู้เชี่ยวชาญเข้า Demo", "ต้องการปรับราคา/ส่วนลดพิเศษ", "ต้องการเอกสารเทคนิคเพิ่มเติม"];
$curr_supp = htmlspecialchars($ct['support_needed'] ?? '');
$is_other_supp = $curr_supp && !in_array($curr_supp, $supp_options);
?>

<style>
    .card-pastel-update {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 18px rgba(150,90,110,.08);
        overflow: hidden;
        margin-bottom: 20px;
        border: none;
    }
    .header-gradient-info {
        background: linear-gradient(135deg, #3f74c4, #4a86d6);
        color: #fff;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: space-between;
    }

    /* สไตล์ช่อง Input แบบใหม่ให้เข้ากับดีไซน์ส่วนที่ 2 */
    .input-pastel {
        display: block; width: 100%; box-sizing: border-box; border: 1px solid #eddbe1; 
        border-radius: 10px; padding: 9px 12px; font-size: 12.5px; background: #fdfbfc; 
        color: #4a3b40; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-pastel:focus {
        border-color: #c1466a; box-shadow: 0 0 0 3px rgba(193, 70, 106, 0.1);
    }
    .input-pastel-fw { font-weight: 600; }
    .input-pastel::placeholder { color: #c3a9b3; }
    
    /* ซ่อน scrollbar สำหรับตัว Funnel */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-end;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#2fa572;"><use href="#i-refresh"></use></svg> 
            อัพเดทความคืบหน้าการติดต่อ
        </div>
    </div>
    <a href="?page=contacts" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการติดต่อ</a>
</div>

<!-- ส่วนที่ 1 -->
<div class="card-pastel-update">
    <div class="header-gradient-info">
        <div style="display:flex;align-items:center;gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-file"></use></svg>
            <div style="font-size:16px;font-weight:600">ส่วนที่ 1 · ข้อมูลการเสนอราคาและรายการสินค้า (อ้างอิง)</div>
        </div>
        <span style="font-size:11px;background:rgba(255,255,255,0.2);padding:4px 10px;border-radius:20px;">อ่านอย่างเดียว</span>
    </div>
    <div class="card-body p-4 bg-light">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">พนักงานผู้รับผิดชอบ</label>
                <div style="padding:9px 12px; border:1px solid #eddbe1; border-radius:10px; background:#fff; font-size:13px; color:#6b5760;">
                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars(($ct['sr_no'] ?? '') . ' - ' . ($ct['emp_name'] ?? '-')) ?>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">วันที่เสนอราคา</label>
                <div style="padding:9px 12px; border:1px solid #eddbe1; border-radius:10px; background:#fff; font-size:13px; color:#6b5760;">
                    <i class="fa-regular fa-calendar me-1 opacity-50"></i> <?= !empty($ct['quotation_date']) ? date('d/m/Y', strtotime($ct['quotation_date'])) : '-' ?>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">เลขที่ใบเสนอราคา</label>
                <div style="padding:9px 12px; border:1px solid #c1466a; border-radius:10px; background:#fbe4ea; font-size:13px; color:#c1466a; font-weight:700;">
                    <i class="fa-solid fa-hashtag me-1"></i> <?= htmlspecialchars($ct['quotation_no'] ?? '-') ?>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label" style="font-size:12.5px;color:#8a7078;font-weight:600;">ข้อมูลลูกค้า (โรงพยาบาล / แผนก / ชื่อลูกค้า / เบอร์โทร)</label>
                <div style="padding:9px 12px; border:1px solid #eddbe1; border-radius:10px; background:#fff; font-size:13px; color:#6b5760;">
                    <i class="fa-regular fa-hospital me-1 opacity-50"></i>
                    <strong class="text-dark"><?= htmlspecialchars($ct['hospital_name'] ?? 'ไม่ระบุ') ?></strong> |
                    แผนก: <?= htmlspecialchars($ct['department'] ?? '-') ?> |
                     <?= htmlspecialchars($ct['customer_name'] ?? '-') ?>
                    (<?= htmlspecialchars($ct['customer_phone'] ?? '-') ?>)
                </div>
            </div>
        </div>

        <div class="table-responsive mb-3" style="border:1px solid #f4e6ec;border-radius:12px">
            <table class="table table-hover align-middle m-0" style="font-size:12.5px;">
                <thead style="background:#faf0f4;color:#7a5d67;text-align:left">
                    <tr>
                        <th width="5%" style="padding:11px 13px;font-weight:600;">ลำดับ</th>
                        <th style="padding:11px 13px;font-weight:600;">แบรนด์สินค้า / รุ่น</th>
                        <th width="15%" style="padding:11px 13px;font-weight:600;text-align:right">ราคา/หน่วย</th>
                        <th width="12%" style="padding:11px 13px;font-weight:600;text-align:center">จำนวน</th>
                        <th width="20%" style="padding:11px 13px;font-weight:600;text-align:right">ราคารวม</th>
                    </tr>
                </thead>
                <tbody style="background:#fff;">
                    <?php if (count($saved_items) > 0): ?>
                        <?php foreach ($saved_items as $index => $item): ?>
                            <tr>
                                <td class="text-center align-middle fw-bold text-muted" style="border-top:1px solid #f4ece2;"><?= $index + 1 ?></td>
                                <td class="fw-bold text-dark" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars(($item['category_name'] ?? 'ไม่ระบุ') . '  ' . ($item['model'] ?: '-') . ' '.  ($item['details'] ?: '-')) ?></td>
                                <td class="text-end" style="border-top:1px solid #f4ece2;"><?= number_format($item['unit_price'], 2) ?> </td>
                                <td class="text-center" style="border-top:1px solid #f4ece2;"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td class="text-end fw-bold text-secondary" style="border-top:1px solid #f4ece2;"><?= number_format($item['total_price'], 2) ?> </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <div style="font-size:13px; color:#6b5760; background:#fff; padding:12px; border-radius:12px; border:1px solid #eddbe1;">
                    <div style="display:flex;justify-content:space-between;padding:4px 0">
                        <span>ราคาสินค้า (Subtotal):</span>
                        <span><?= number_format($ct['subtotal'] ?? 0, 2) ?> </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0">
                        <span class="text-danger fw-bold">ส่วนลดพิเศษ (Discount):</span>
                        <span class="text-danger">-<?= number_format($ct['discount'] ?? 0, 2) ?>฿</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0">
                        <span>ภาษีมูลค่าเพิ่ม (VAT 7%):</span>
                        <span><?= number_format($ct['vat'] ?? 0, 2) ?> </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 0 4px;border-top:1px solid #f0dce3;margin-top:4px;font-weight:700;font-size:15px;color:#2fa572">
                        <span>ราคาสุทธิ (Grand Total):</span>
                        <span><?= number_format($ct['grand_total'] ?? 0, 2) ?> </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="index.php?route=contact_update_action" method="POST" id="updateForm">
    <input type="hidden" name="contact_id" value="<?= $ct['id'] ?>">

    <!-- ส่วนที่ 2 แบบใหม่ (Sales Funnel UI) -->
    <div class="card-pastel-update">
        <div style="background:linear-gradient(90deg,#2f9fb8,#38b6cf);color:#fff;padding:14px 20px;display:flex;align-items:center;gap:10px;">
            <span><svg width="20" height="20" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;flex:none;display:inline-block"><use href="#i-refresh"></use></svg></span>
            <div>
                <div style="font-size:15px;font-weight:600;color:#fff;">ส่วนที่ 2 · การติดตามและสถานะงาน (อัพเดท)</div>
                <div style="font-size:11.5px;color:rgba(255,255,255,0.9);">อัปเดตความคืบหน้าและ Sales Funnel</div>
            </div>
        </div>
        <div style="padding:22px;">
            
            <!-- ระบบ Sales Funnel กราฟิก -->
            <div style="font-size:12px;color:#8a7078;margin-bottom:6px">สถานะงาน (Sales Funnel) <span class="text-danger">*</span></div>
            <div style="background:#fdfbfc;border:1px solid #f0dce3;border-radius:14px;padding:16px 14px 12px;margin-bottom:20px; overflow-x: auto;" class="no-scrollbar">
                <input type="hidden" name="job_status" id="job_status_input" value="<?= htmlspecialchars($ct['status'] ?? '0 ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว') ?>" required>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin:6px 0 4px; min-width: 600px;" id="funnel_container">
                    <!-- Javascript จะสร้างวงกลมสถานะตรงนี้อัตโนมัติ -->
                </div>
            </div>

            <!-- แถวที่ 1 -->
            <div class="row mb-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">วันที่รายงาน <span class="text-danger">*</span></div>
                    <input type="date" class="input-pastel input-pastel-fw" name="report_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">คู่แข่ง</div>
                    <input type="text" class="input-pastel input-pastel-fw" name="competitor" value="<?= htmlspecialchars($ct['competitor'] ?? '') ?>" placeholder="ชื่อบริษัทคู่แข่ง หรือพิมพ์ 'ไม่มี'">
                </div>
                <div class="col-md-4">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">วันที่คาดว่าจะปิดได้</div>
                    <input type="date" class="input-pastel input-pastel-fw" style="color:#c1466a; border-color:#f4c9d5;" name="expected_close_date" value="<?= !empty($ct['expected_close_date']) && $ct['expected_close_date'] != '0000-00-00' ? htmlspecialchars($ct['expected_close_date']) : '' ?>">
                </div>
            </div>

            <!-- แถวที่ 2 -->
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">กิจกรรมล่าสุด <span class="text-danger">*</span></div>
                    <textarea class="input-pastel" name="recent_activity" rows="2" required placeholder="เช่น ได้เข้าพบเพื่ออธิบายการใช้งานเพิ่มเติม..."><?= htmlspecialchars($ct['recent_activity'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">แผนถัดไป</div>
                    <textarea class="input-pastel" name="next_plan" rows="2" placeholder="เช่น นัดสาธิตเครื่องจริงสัปดาห์หน้า..."><?= htmlspecialchars($ct['next_plan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- แถวที่ 3 -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">ปัญหา / อุปสรรค</div>
                    <select class="input-pastel" id="problems_select" style="color:#a89aa0;">
                        <option value="">-- ไม่มี / เลือกปัญหา --</option>
                        <?php foreach ($prob_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $curr_prob === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                        <option value="อื่นๆ" <?= $is_other_prob ? 'selected' : '' ?>>อื่นๆ (โปรดระบุ)</option>
                    </select>
                    <input type="text" class="input-pastel mt-2 <?= $is_other_prob ? '' : 'd-none' ?>" id="problems_other" value="<?= $is_other_prob ? $curr_prob : '' ?>" placeholder="โปรดระบุปัญหา / อุปสรรค...">
                    <input type="hidden" name="problems" id="final_problems" value="<?= $curr_prob ?>">
                </div>
                <div class="col-md-6">
                    <div style="font-size:12px;color:#8a7078;margin-bottom:5px">สิ่งที่ต้องการสนับสนุน</div>
                    <select class="input-pastel" id="support_select" style="color:#a89aa0;">
                        <option value="">-- ไม่มี / เลือกสิ่งที่ต้องการ --</option>
                        <?php foreach ($supp_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $curr_supp === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                        <option value="อื่นๆ" <?= $is_other_supp ? 'selected' : '' ?>>อื่นๆ (โปรดระบุ)</option>
                    </select>
                    <input type="text" class="input-pastel mt-2 <?= $is_other_supp ? '' : 'd-none' ?>" id="support_other" value="<?= $is_other_supp ? $curr_supp : '' ?>" placeholder="โปรดระบุสิ่งที่ต้องการสนับสนุน...">
                    <input type="hidden" name="support_needed" id="final_support" value="<?= $curr_supp ?>">
                </div>
            </div>

            <!-- จัดปุ่มชิดขวา และเอาเส้นขอบบนออก -->
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px;">
                <a href="?page=contacts" style="font-size:12.5px;padding:10px 20px;border-radius:11px;border:1px solid #eddbe1;color:#8a7078;text-decoration:none;cursor:pointer;">ยกเลิก</a>
                <button type="submit" style="font-size:12.5px;padding:10px 22px;border-radius:11px;background:#2fa572;color:#fff;font-weight:600;border:none;cursor:pointer;">
                    <i class="fa-solid fa-save"></i> บันทึกการอัพเดท
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // ==========================================
        // ระบบ Sales Funnel กราฟิก (ส่วนที่ 2)
        // ==========================================
        const statuses = [
            "0 ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว",
            "1 เริ่มทำเสนอราคา ต้องการเครื่อง ของบไปไม่ทราบผล",
            "2 มีงบแล้วหรือเลือกเราแล้ว",
            "3 มีงบแล้วและเลือกเราแล้ว (รอ)ขั้นตอนการสั่งซื้อ",
            "4 เรียกเซ็นสัญญาแล้วหรือรอส่งของ",
            "5 รอเก็บเงิน",
            "6 สิ้นสุดการขาย จบการขาย ปิดจ๊อบ"
        ];
             const statusLabels = ["ยังไม่ได้เข้าไปหรือ จัดซื้อที่อื่นแล้ว", "เริ่มทำเสนอราคา ต้องการเครื่อง ของบไปไม่ทราบผล", "มีงบแล้วหรือเลือกเราแล้ว", "มีงบแล้วและเลือกเราแล้ว (รอ)ขั้นตอนการสั่งซื้อ",
							  			"เรียกเซ็นสัญญาแล้วหรือรอส่งของ", "รอเก็บเงิน", "สิ้นสุดการขาย จบการขาย ปิดจ๊อบ"];

        function renderFunnel(selectedIndex) {
            let html = '';
            for(let i=0; i<7; i++) {
                let isPassed = i <= selectedIndex;
                let isCurrent = i === selectedIndex;
                let isLast = i === 6;

                let lineHtml = '';
                if (!isLast) {
                    let lineColor = i < selectedIndex ? '#c1466a' : '#f0dce3';
                    lineHtml = `<div style="position:absolute;top:15px;left:50%;width:100%;height:3px;background:${lineColor}"></div>`;
                }

                let circleStyle = isPassed ? 'background:#c1466a;border:2px solid #c1466a;color:#fff;' : 'background:#fff;border:2px solid #e6cdd6;color:#c3a9b3;';
                let shadowStyle = isCurrent ? 'box-shadow:0 0 0 5px #fbe4ea;' : '';
                let textStyle = isCurrent ? 'color:#c1466a;font-weight:600;' : (isPassed ? 'color:#9a828a;font-weight:400;' : 'color:#9a828a;font-weight:400;');

                html += `
                <div class="funnel-step" data-index="${i}" style="flex:1;display:flex;flex-direction:column;align-items:center;position:relative;cursor:pointer;min-width:0;">
                    ${lineHtml}
                    <div style="width:31px;height:31px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;position:relative;z-index:1;transition:all 0.2s;${circleStyle}${shadowStyle}">${i}</div>
                    <div style="font-size:10.5px;margin-top:7px;text-align:center;transition:all 0.2s;${textStyle}max-width:78px">${statusLabels[i]}</div>
                </div>`;
            }
            document.getElementById('funnel_container').innerHTML = html;

            document.querySelectorAll('.funnel-step').forEach(step => {
                step.addEventListener('click', function() {
                    let idx = parseInt(this.getAttribute('data-index'));
                    document.getElementById('job_status_input').value = statuses[idx];
                    renderFunnel(idx);
                });
            });
        }

        let jobStatusInput = document.getElementById('job_status_input');
        if (jobStatusInput) {
            let initStatus = jobStatusInput.value;
            let initIndex = statuses.indexOf(initStatus);
            if(initIndex === -1) initIndex = 1;
            jobStatusInput.value = statuses[initIndex];
            renderFunnel(initIndex);
        }

        // ==========================================
        // การจัดการฟิลด์ "อื่นๆ (โปรดระบุ)"
        // ==========================================
        function toggleOtherInput(selectId, inputId) {
            let selectEl = document.getElementById(selectId);
            let inputEl = document.getElementById(inputId);
            if (selectEl.value === 'อื่นๆ') {
                inputEl.classList.remove('d-none');
                inputEl.required = true;
                inputEl.focus();
            } else {
                inputEl.classList.add('d-none');
                inputEl.required = false;
            }
        }
        document.getElementById('problems_select').addEventListener('change', function() { toggleOtherInput('problems_select', 'problems_other'); });
        document.getElementById('support_select').addEventListener('change', function() { toggleOtherInput('support_select', 'support_other'); });

        document.getElementById('updateForm').addEventListener('submit', function(e) {
            let probSel = document.getElementById('problems_select').value;
            let probOther = document.getElementById('problems_other').value;
            document.getElementById('final_problems').value = (probSel === 'อื่นๆ') ? probOther : probSel;
            
            let suppSel = document.getElementById('support_select').value;
            let suppOther = document.getElementById('support_other').value;
            document.getElementById('final_support').value = (suppSel === 'อื่นๆ') ? suppOther : suppSel;

            let submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'กำลังบันทึกข้อมูล...';
            }
        });
    });
</script>