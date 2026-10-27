<?php

// ป้องกันคนไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนดูเอกสาร");
}

if (!isset($_GET['id'])) {
    die("ไม่พบข้อมูลอ้างอิง");
}

$id = $_GET['id'];

// 1. ดึงข้อมูลการติดต่อหลัก
$sql = "
    SELECT ct.*, 
           e.sr_no, e.fullname AS emp_name, 
           c.hospital_name, c.customer_name, c.phone AS customer_phone, c.department
    FROM contacts ct
    LEFT JOIN employees e ON ct.employee_id = e.id
    LEFT JOIN customers c ON ct.customer_id = c.id
    WHERE ct.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("ไม่พบข้อมูลการติดต่อในระบบ");
}

// 2.  ดึงรายการสินค้าที่อยู่ในบิลนี้ (เพิ่ม p.details เข้ามาด้วย)
$sql_items = "
    SELECT ci.*, pc.category_name, p.model, p.details
    FROM contact_items ci
    LEFT JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE ci.contact_id = ?
    ORDER BY ci.id ASC
";
$stmt_items = $pdo->prepare($sql_items);
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// 3. ดึงประวัติการอัปเดตทั้งหมดของลูกค้ารายนี้
$sql_history = "SELECT * FROM contact_updates WHERE contact_id = ? ORDER BY id DESC";
$stmt_history = $pdo->prepare($sql_history);
$stmt_history->execute([$id]);
$updates = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

$total_updates = count($updates);

// กำหนดสีสถานะงาน ตามเลขลำดับสถานะ (0-6) ที่ระบบใช้ ให้แต่ละขั้นมีสีพาสเทลของตัวเอง
function getStatusColors($status)
{
    $status = trim((string) $status);

    // สถานะของระบบขึ้นต้นด้วยเลขลำดับเสมอ เช่น "2 มีงบแล้วหรือเลือกเราแล้ว"
    if (preg_match('/^(\d+)/', $status, $m)) {
        $palette = [
            '0' => ['bg' => '#fbe1e6', 'text' => '#c23d5b'], // ยังไม่ได้เข้าไป / จัดซื้อที่อื่นแล้ว
            '1' => ['bg' => '#fde3d4', 'text' => '#b5651d'], // ติดต่อเบื้องต้น
            '2' => ['bg' => '#fbead0', 'text' => '#a9761b'], // มีงบแล้วหรือเลือกเราแล้ว
            '3' => ['bg' => '#f7e6b8', 'text' => '#8a6d0f'], // มีงบแล้วและเลือกเราแล้ว (รอขั้นตอนสั่งซื้อ)
            '4' => ['bg' => '#dce8fb', 'text' => '#3b5fa0'], // เรียกเซ็นสัญญาแล้ว / รอส่งของ
            '5' => ['bg' => '#ece1fb', 'text' => '#6b4ea0'], // รอเก็บเงิน
            '6' => ['bg' => '#e3f3df', 'text' => '#3f8a4c'], // สิ้นสุดการขาย / ปิดจ็อบ
        ];
        if (isset($palette[$m[1]])) {
            return $palette[$m[1]];
        }
    }

    // สำรอง: เผื่อสถานะไม่ได้ขึ้นต้นด้วยเลข ให้เดาจากคำในข้อความแทน
    if (mb_strpos($status, 'มีงาน') !== false || mb_strpos($status, 'เลือกเรา') !== false) {
        return ['bg' => '#fbead0', 'text' => '#a9761b'];
    }
    if (mb_strpos($status, 'ยังไม่ได้เข้าไป') !== false || mb_strpos($status, 'จัดซื้อที่อื่น') !== false) {
        return ['bg' => '#fbe1e6', 'text' => '#c23d5b'];
    }
    if (
        mb_strpos($status, 'ปิดจ็อบ') !== false ||
        mb_strpos($status, 'ปิดจ๊อบ') !== false ||
        mb_strpos($status, 'จบการขาย') !== false ||
        mb_strpos($status, 'สิ้นสุดการขาย') !== false ||
        mb_strpos($status, 'สำเร็จ') !== false ||
        mb_strpos($status, 'ปิดการขาย') !== false
    ) {
        return ['bg' => '#e3f3df', 'text' => '#3f8a4c'];
    }
    if (mb_strpos($status, 'ยกเลิก') !== false || mb_strpos($status, 'แพ้') !== false) {
        return ['bg' => '#ece9f0', 'text' => '#645f77'];
    }
    // สถานะอื่นๆ ที่ไม่ตรงเงื่อนไขด้านบน -> ใช้โทนชมพู-ม่วงพาสเทลของธีมหลัก
    return ['bg' => '#f4e2ee', 'text' => '#8a4c85'];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เอกสารการติดต่อ - <?= htmlspecialchars($data['hospital_name'] ?? 'ไม่ระบุ') ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-pink: #e88bab;
            --brand-pink-dark: #c9688f;
            --brand-pink-soft: #fce4ec;
            --brand-purple: #c3a3e6;
            --ink: #33303a;
            --muted: #8a8794;
            --line: #f0d9e4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(160deg, #fdf3f7 0%, #f6eefb 45%, #fdf3f7 100%);
            background-attachment: fixed;
            font-family: 'Prompt', sans-serif;
            color: var(--ink);
            position: relative;
        }

        /* จุดตกแต่งพื้นหลังแบบเดียวกับหน้า Dashboard */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image:
                radial-gradient(circle, rgba(214,51,108,0.10) 3px, transparent 3px),
                radial-gradient(circle, rgba(156,106,222,0.10) 3px, transparent 3px);
            background-size: 140px 140px, 220px 220px;
            background-position: 0 0, 70px 90px;
        }

        .a4-page {
            position: relative;
            z-index: 1;
            background: #ffffff;
            width: 210mm;
            max-width: 100%;
            min-height: 297mm;
            margin: 30px auto;
            padding: 20mm;
            box-shadow: 0 10px 35px rgba(156, 79, 130, 0.15);
            border-radius: 18px;
            color: var(--ink);
            border: 1px solid var(--line);
        }

        .doc-header {
            text-align: center;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--brand-pink-soft);
        }

        .doc-header .brand-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--brand-pink), var(--brand-purple));
            color: #fff;
            font-size: 1.4rem;
            margin-bottom: 10px;
            box-shadow: 0 6px 16px rgba(214,51,108,0.35);
        }

        .doc-header h3 {
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .doc-header p {
            color: var(--muted);
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--brand-pink-dark);
            padding: 10px 16px;
            margin-top: 30px;
            margin-bottom: 18px;
            font-weight: 600;
            font-size: 1.05rem;
            background: var(--brand-pink-soft);
            border-radius: 12px;
            page-break-after: avoid;
        }

        .section-title i {
            color: var(--brand-pink);
        }

        .data-label {
            font-weight: 600;
            color: var(--muted);
            font-size: 0.82rem;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-value {
            font-size: 1rem;
            color: var(--ink);
            font-weight: 500;
            margin-bottom: 15px;
            word-wrap: break-word;
        }

        .data-value.accent {
            color: var(--brand-pink-dark);
            font-weight: 600;
        }

        .content-box {
            background-color: #faf7fb;
            padding: 15px 18px;
            border-radius: 14px;
            border: 1px solid var(--line);
            margin-bottom: 15px;
            min-height: 70px;
            white-space: pre-line;
            color: var(--ink);
        }

        .history-box {
            background-color: #ffffff;
            border: 1px solid var(--line);
            border-left: 4px solid var(--brand-pink);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 14px;
            page-break-inside: avoid;
            color: var(--ink);
            box-shadow: 0 3px 10px rgba(156, 79, 130, 0.06);
        }

        .history-box .badge-round {
            background: var(--brand-pink-soft);
            color: var(--brand-pink-dark);
            border-radius: 999px;
            padding: 3px 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .pdf-table-wrapper {
            width: 100%;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--line);
        }

        .pdf-table {
            margin: 0;
        }

        .pdf-table thead th {
            background: linear-gradient(135deg, var(--brand-pink), var(--brand-purple));
            color: #fff !important;
            border: none !important;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .pdf-table td {
            border: none !important;
            border-bottom: 1px solid var(--line) !important;
            color: var(--ink);
            vertical-align: middle;
            padding: 16px 14px;
            line-height: 1.55;
        }

        .pdf-table tbody tr:last-child td {
            border-bottom: none !important;
        }

        .pdf-table .item-name {
            font-size: 0.98rem;
            display: block;
            margin-bottom: 4px;
        }

        .pdf-table .item-details {
            margin-top: 6px;
            line-height: 1.55;
            font-size: 0.83rem;
        }

        .summary-box {
            padding: 18px 20px;
            border-radius: 16px;
            background: linear-gradient(160deg, #fff, var(--brand-pink-soft));
            border: 1px solid var(--line);
            font-size: 0.92rem;
            box-shadow: 0 4px 14px rgba(156, 79, 130, 0.08);
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 0;
            white-space: nowrap;
            vertical-align: middle;
        }

        .summary-table td:first-child {
            font-weight: 600;
            color: var(--ink);
            padding-right: 16px;
            width: 1%;
        }

        .summary-table td:last-child {
            text-align: right;
            width: 99%;
        }

        .summary-box .grand-total {
            color: var(--brand-pink-dark);
            font-size: 1.15rem;
        }

        .status-pill {
            display: inline-block;
            border-radius: 14px;
            padding: 6px 18px;
            font-weight: 600;
            line-height: 1.5;
        }

        .toolbar {
            background: rgba(255,255,255,0.85);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(8px);
        }

        .toolbar .btn {
            font-weight: 500;
            border: none;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--brand-pink), var(--brand-purple));
            color: #fff;
        }

        .btn-print:hover {
            color: #fff;
            filter: brightness(1.05);
        }

        .btn-close-win {
            background: #f1eef4;
            color: var(--ink);
        }

        .btn-close-win:hover {
            background: #e6e1eb;
            color: var(--ink);
        }

        .tip-text {
            color: var(--muted) !important;
        }

        /* ===== Tablet ===== */
        @media (max-width: 900px) {
            .a4-page {
                width: 100%;
                min-height: auto;
                margin: 15px auto;
                padding: 15mm 10mm;
                border-radius: 12px;
            }
        }

        /* ===== Mobile ===== */
        @media (max-width: 576px) {
            .a4-page {
                padding: 15px;
                margin: 8px auto;
                box-shadow: none;
                border-radius: 0;
            }

            .doc-header h3 {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 1rem;
                margin-top: 20px;
                padding: 8px 12px;
            }

            .data-label {
                font-size: 0.76rem;
            }

            .data-value {
                font-size: 0.92rem;
                margin-bottom: 10px;
            }

            .row .col-6,
            .row .col-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .pdf-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .pdf-table {
                min-width: 480px;
                font-size: 0.78rem;
            }

            .history-box {
                padding: 10px 12px;
            }

            .history-box .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 4px;
            }

            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                background: white;
                margin: 0;
                -webkit-print-color-adjust: exact;
            }

            body::before {
                display: none;
            }

            .a4-page {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 15mm;
                box-shadow: none;
                border-radius: 0;
                border: none;
            }

            .row .col-6,
            .row .col-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .row.justify-content-end .col-lg-7,
            .row.justify-content-end .col-md-8,
            .row.justify-content-end .col-12 {
                flex: 0 0 58.33333%;
                max-width: 58.33333%;
            }

            .pdf-table-wrapper {
                overflow: visible;
            }

            .pdf-table {
                min-width: 0;
                font-size: 1rem;
            }

            .no-print {
                display: none !important;
            }

            .page-break-before {
                break-before: page;
                page-break-before: always;
                margin-top: 12mm !important;
                border-top: none;
            }
        }
    </style>
</head>

<body>

    <div class="text-center no-print py-3 sticky-top toolbar">
        <button onclick="window.print()" class="btn btn-print btn-lg shadow-sm px-4 rounded-pill">
            <i class="fa-solid fa-print me-2"></i> พิมพ์ / บันทึกเป็น PDF
        </button>
        <button onclick="window.close()" class="btn btn-close-win btn-lg shadow-sm ms-2 rounded-pill">
            <i class="fa-solid fa-times me-2"></i> ปิดหน้าต่าง
        </button>
        <p class="tip-text small mt-2 mb-0">เคล็ดลับ: ตอนเซฟ PDF แนะนำให้เลือกกระดาษขนาด <b>A4</b> และติ๊กเปิด <b>Background Graphics</b></p>
    </div>

    <div class="a4-page">

        <div class="doc-header">
            <div class="brand-icon"><i class="fa-solid fa-file-invoice"></i></div>
            <h3 class="text-uppercase">รายงานการติดต่อลูกค้า</h3>
            <p>รหัสเอกสารอ้างอิง: #<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></p>
        </div>

        <div class="section-title"><i class="fa-solid fa-file-invoice-dollar"></i>ส่วนที่ 1: ข้อมูลการเสนอราคาและรายการสินค้า</div>
        <div class="row">
            <div class="col-6">
                <div class="data-label">พนักงานผู้รับผิดชอบ</div>
                <div class="data-value"><?= htmlspecialchars($data['sr_no'] ?? '') ?> - <?= htmlspecialchars($data['emp_name'] ?? '-') ?></div>
            </div>
            <div class="col-6">
                <div class="data-label">วันที่เสนอราคา</div>
                <div class="data-value"><?= !empty($data['quotation_date']) ? date('d/m/Y', strtotime($data['quotation_date'])) : '-' ?></div>
            </div>

            <div class="col-6">
                <div class="data-label">ลูกค้า / โรงพยาบาล</div>
                <div class="data-value accent"><?= htmlspecialchars($data['hospital_name'] ?? '-') ?></div>
            </div>
            <div class="col-6">
                <div class="data-label">เลขที่ใบเสนอราคา</div>
                <div class="data-value accent"><?= htmlspecialchars($data['quotation_no'] ?? '-') ?></div>
            </div>

            <div class="col-6">
                <div class="data-label">ชื่อผู้ติดต่อ / แผนก</div>
                <div class="data-value"><?= htmlspecialchars($data['customer_name'] ?? '-') ?> : <?= htmlspecialchars($data['department'] ?? '-') ?></div>
            </div>
            <div class="col-6">
                <div class="data-label">เบอร์ติดต่อ</div>
                <div class="data-value"><?= htmlspecialchars($data['customer_phone'] ?? '-') ?></div>
            </div>

            <div class="col-12 mt-2 mb-3">
                <div class="data-label mb-2">รายการสินค้าที่เสนอราคา</div>
                <div class="pdf-table-wrapper">
                    <table class="table align-middle pdf-table m-0">
                        <thead class="text-center">
                            <tr>
                                <th width="5%">ที่</th>
                                <th>แบรนด์สินค้า / รุ่น / รายละเอียด</th>
                                <th width="18%">ราคา/หน่วย (บาท)</th>
                                <th width="12%">จำนวน</th>
                                <th width="22%">ราคารวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td class="text-center"><?= $index + 1 ?></td>
                                        <td>
                                            <!--  รวม แบรนด์ + รุ่น + รายละเอียด ไว้ในช่องเดียวกัน -->
                                            <span class="item-name"><?= htmlspecialchars(($item['category_name'] ?? 'ไม่มีแบรนด์') . '  ' . ($item['model'] ?: '-') . '') ?></span>
                                            <?php if (!empty($item['details'])): ?>
                                                <div class="item-details tip-text fw-normal"><?= nl2br(htmlspecialchars($item['details'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= number_format($item['unit_price'], 2) ?> </td>
                                        <td class="text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td class="text-end accent-text"><?= number_format($item['total_price'], 2) ?> </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">ไม่มีข้อมูลสินค้า</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <div class="row justify-content-end">
                    <div class="col-lg-7 col-md-8 col-12">
                        <div class="summary-box">
                            <table class="summary-table">
                                <tr>
                                    <td>ราคาสินค้า (Subtotal):</td>
                                    <td><?= number_format($data['subtotal'] ?? 0, 2) ?> </td>
                                </tr>
                                <tr>
                                    <td>ส่วนลดพิเศษ (Discount):</td>
                                    <td>-<?= number_format($data['discount'] ?? 0, 2) ?> </td>
                                </tr>
                                <tr>
                                    <td>ภาษีมูลค่าเพิ่ม (VAT 7%):</td>
                                    <td><?= number_format($data['vat'] ?? 0, 2) ?> </td>
                                </tr>
                            </table>
                            <hr class="my-2" style="border-color: var(--line);">
                            <div class="d-flex justify-content-between align-items-center fw-bold grand-total">
                                <span>ราคาสุทธิ (Grand Total):</span>
                                <span><?= number_format($data['grand_total'] ?? 0, 2) ?> </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title mt-4 page-break-before"><i class="fa-solid fa-list-check"></i>ส่วนที่ 2: การติดตามและสถานะงาน (ล่าสุด)</div>
        <div class="row">
            <div class="col-4">
                <div class="data-label">วันที่รายงาน</div>
                <div class="data-value"><?= !empty($data['report_date']) ? date('d/m/Y', strtotime($data['report_date'])) : '-' ?></div>
            </div>
            <div class="col-4">
                <div class="data-label">คู่แข่ง</div>
                <div class="data-value"><?= htmlspecialchars($data['competitor'] ?? '-') ?></div>
            </div>
            <div class="col-4">
                <div class="data-label">วันที่คาดว่าจะปิดได้</div>
                <div class="data-value fw-bold accent"><?= !empty($data['expected_close_date']) && $data['expected_close_date'] != '0000-00-00' ? date('d/m/Y', strtotime($data['expected_close_date'])) : '-' ?></div>
            </div>
            <div class="col-12 mt-2">
                <div class="data-label">สถานะงานปัจจุบัน</div>
                <div class="mt-1">
                    <?php $sc = getStatusColors($data['status'] ?? ''); ?>
                    <span class="status-pill" style="background: <?= $sc['bg'] ?>; color: <?= $sc['text'] ?>;"><?= htmlspecialchars($data['status'] ?? '-') ?></span>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="data-label">กิจกรรมล่าสุด (Recent Activity)</div>
                <div class="content-box"><?= htmlspecialchars($data['recent_activity'] ?? '-') ?></div>
            </div>
            <div class="col-12">
                <div class="data-label">แผนถัดไป (Next Plan)</div>
                <div class="content-box"><?= htmlspecialchars($data['next_plan'] ?? '-') ?></div>
            </div>
            <div class="col-12">
                <div class="data-label text-danger">ปัญหา / อุปสรรค (Problems)</div>
                <div class="content-box" style="border-color:#f3c8d4;"><?= htmlspecialchars($data['issues'] ?? '-') ?></div>
            </div>
            <div class="col-12">
                <div class="data-label text-success">สิ่งที่ต้องการสนับสนุน (Support Needed)</div>
                <div class="content-box" style="border-color:#c8e6d0;"><?= htmlspecialchars($data['support_needed'] ?? '-') ?></div>
            </div>
        </div>

        <?php if (!empty($updates)): ?>
            <div class="section-title mt-4 page-break-before"><i class="fa-solid fa-clock-rotate-left"></i>ส่วนที่ 3: ประวัติการอัปเดตความคืบหน้า</div>
            <div>
                <?php
                foreach ($updates as $index => $upd):
                    $upd_date = $upd['update_date'] ?? $upd['created_at'] ?? null;
                    $upd_issues = $upd['issues'] ?? $upd['problems'] ?? '-';
                    $current_version = $total_updates - $index;
                ?>
                    <div class="history-box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-round">ครั้งที่ <?= $current_version ?></span>
                            <span class="small fw-bold tip-text">
                                <i class="fa-regular fa-calendar-check me-1"></i>
                                <?= $upd_date ? date('d/m/Y H:i', strtotime($upd_date)) : '-' ?>
                            </span>
                        </div>
                        <div class="mb-2 pb-2" style="border-bottom: 1px solid var(--line);">
                            <?php $usc = getStatusColors($upd['status'] ?? ''); ?>
                            <span class="status-pill" style="background: <?= $usc['bg'] ?>; color: <?= $usc['text'] ?>; padding: 4px 14px; font-size: 0.85rem; line-height: 1.5;"><?= htmlspecialchars($upd['status']) ?></span>
                        </div>

                        <div class="row small">
                            <?php if (!empty($upd['recent_activity'])): ?>
                                <div class="col-12 mb-1"><strong>กิจกรรม:</strong> <?= nl2br(htmlspecialchars($upd['recent_activity'])) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($upd['next_plan'])): ?>
                                <div class="col-12 mb-1"><strong>แผนถัดไป:</strong> <?= nl2br(htmlspecialchars($upd['next_plan'])) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($upd_issues) && $upd_issues !== '-'): ?>
                                <div class="col-12 mb-1 text-danger"><strong>ปัญหา:</strong> <?= nl2br(htmlspecialchars($upd_issues)) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($upd['support_needed']) && $upd['support_needed'] !== '-'): ?>
                                <div class="col-12 text-success"><strong>ต้องการสนับสนุน:</strong> <?= nl2br(htmlspecialchars($upd['support_needed'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <style>
        .accent-text { color: var(--brand-pink-dark); }
    </style>

</body>
</html>