<?php
$id = $_GET['id'] ?? 0;

// 1. ดึงรายละเอียดเคสหลัก
$stmt_main = $pdo->prepare("
    SELECT c.hospital_name, c.customer_name 
    FROM contacts ct
    LEFT JOIN customers c ON ct.customer_id = c.id
    WHERE ct.id = ?
");
$stmt_main->execute([$id]);
$main = $stmt_main->fetch();

//  2. ดึงรายการสินค้าทั้งหมดในดีลนี้มาแสดงผลที่หัวข้อ (ดึง แบรนด์ จากตารางหมวดหมู่)
$stmt_items = $pdo->prepare("
    SELECT ci.*, pc.category_name, p.model 
    FROM contact_items ci
    LEFT JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE ci.contact_id = ?
    ORDER BY ci.id ASC
");
$stmt_items->execute([$id]);
$saved_items = $stmt_items->fetchAll();

// รวมชื่อรายการสินค้าทั้งหมดเป็นเส้นข้อความยาว
$prod_names = [];
foreach ($saved_items as $item) {
    $brand = $item['category_name'] ?? 'ไม่มีแบรนด์';
    $prod_names[] = htmlspecialchars($brand . ($item['model'] ? ' (รุ่น: ' . $item['model'] . ')' : ''));
}
$display_products = count($prod_names) > 0 ? implode(', ', $prod_names) : 'ไม่ระบุสินค้า';

// 3. ดึงประวัติการอัพเดททั้งหมด เรียงจากใหม่ล่าสุดไปเก่าสุด
$stmt_hist = $pdo->prepare("SELECT * FROM contact_updates WHERE contact_id = ? ORDER BY created_at DESC");
$stmt_hist->execute([$id]);
$history = $stmt_hist->fetchAll();
?>

<div style="margin-bottom:22px; display:flex; justify-content:space-between; align-items:flex-start;">
    <div>
        <div style="font-size:24px;font-weight:600;display:flex;align-items:center;gap:8px;color:#4a3b40">
            <svg width="24" height="24" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><use href="#i-clock"></use></svg> 
            ประวัติการอัพเดทความคืบหน้า
        </div>
        <div style="font-size:13.5px;color:#6b5760;margin-top:3px; max-width:800px; line-height:1.6;">
            <strong>โรงพยาบาล:</strong> <?= htmlspecialchars($main['hospital_name'] ?? '') ?><br>
            <strong>สินค้า:</strong> <span style="color:#9a828a;"><?= $display_products ?></span>
        </div>
    </div>
    <a href="?page=contacts" style="font-size:12.5px;color:#8a7078;text-decoration:none;">← กลับรายการติดต่อ</a>
</div>

<div style="background:#fff;border-radius:18px;padding:24px 28px;box-shadow:0 6px 18px rgba(150,90,110,.08)">
    <?php if (count($history) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:0;font-size:13px">
            <?php 
                $colors = ['#c1466a', '#e88aa5', '#f0b8c8', '#a89aa0', '#d9c3cb'];
                foreach ($history as $index => $log): 
                    $is_last = ($index === count($history) - 1);
                    $dot_color = ($index === 0) ? '#2fa572' : $colors[$index % count($colors)]; // จุดแรกสีเขียว
            ?>
                <div style="display:flex;gap:16px;padding-bottom:<?= $is_last ? '0' : '20px' ?>">
                    <!-- เส้น Timeline -->
                    <div style="display:flex;flex-direction:column;align-items:center">
                        <div style="width:12px;height:12px;border-radius:50%;background:<?= $dot_color ?>;margin-top:6px; box-shadow: 0 0 0 4px <?= $index === 0 ? '#e3f4ea' : '#fbe4ea' ?>;"></div>
                        <?php if (!$is_last): ?><div style="flex:1;width:2px;background:#f2e2e7;margin-top:4px;"></div><?php endif; ?>
                    </div>
                    
                    <!-- ข้อมูลการอัพเดท -->
                    <div style="flex:1; padding-bottom: <?= $is_last ? '0' : '8px' ?>; background:#fdfbfc; border:1px solid #f4e6ec; border-radius:12px; padding: 16px;">
                        <div style="display:flex;justify-content:space-between; align-items:center; border-bottom:1px solid #f4e6ec; padding-bottom:10px; margin-bottom:12px;">
                            <span style="font-weight:600;color:#c1466a;">อัพเดทเมื่อ: <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?> น.</span>
                            <span style="background:#f0ece7;color:#6b5760;padding:4px 10px;border-radius:8px;font-size:11.5px;font-weight:600;"><?= htmlspecialchars($log['status']) ?></span>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div style="font-weight:600; color:#4a3b40; margin-bottom:4px;">กิจกรรมล่าสุด:</div>
                                <div style="color:#7a626a; line-height:1.6;"><?= nl2br(htmlspecialchars($log['recent_activity'])) ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div style="font-weight:600; color:#4a3b40; margin-bottom:4px;">แผนถัดไป:</div>
                                <div style="color:#7a626a; line-height:1.6;"><?= nl2br(htmlspecialchars($log['next_plan'] ?? '-')) ?></div>
                            </div>
                        </div>
                        
                        <div class="row pt-2">
                            <div class="col-md-6 mb-2">
                                <div style="font-weight:600; color:#c1466a; margin-bottom:4px;">ปัญหา / อุปสรรค:</div>
                                <div style="color:#9a828a;"><?= nl2br(htmlspecialchars($log['issues'] ?? '-')) ?></div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div style="font-weight:600; color:#2fa572; margin-bottom:4px;">สิ่งที่ต้องการสนับสนุน:</div>
                                <div style="color:#9a828a;"><?= nl2br(htmlspecialchars($log['support_needed'] ?? '-')) ?></div>
                            </div>
                        </div>

                        <?php if($log['expected_close_date'] && $log['expected_close_date'] != '0000-00-00'): ?>
                            <div style="border-top:1px dashed #f4e6ec; padding-top:10px; margin-top:6px; text-align:right; color:#c1466a; font-size:12px; font-weight:600;">
                                วันที่คาดว่าจะปิด ณ ตอนนั้น: <?= date('d/m/Y', strtotime($log['expected_close_date'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding: 40px 20px; color:#a89aa0; font-size:13.5px;">
            <svg width="26" height="26" viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;margin-bottom:10px;"><use href="#i-file"></use></svg><br>
            เคสนี้ยังไม่เคยมีประวัติการบันทึกอัพเดทความคืบหน้า
        </div>
    <?php endif; ?>
</div>