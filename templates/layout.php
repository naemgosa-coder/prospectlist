<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prospectlisti</title>
    <!-- Fonts ตามที่ดีไซเนอร์ระบุ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Prompt:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* บังคับใช้ฟอนต์และพื้นหลังตามดีไซน์ใหม่ */
        body {
            margin: 0;
            background: #f9f1ea;
            font-family: 'Prompt', 'DM Sans', sans-serif;
            color: #4a3b40;
        }

        /* ตกแต่งลิงก์เมนู Dropdown ย่อย */
        .dd-item {
            display: block;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 9px;
            font-size: 13px;
            color: #5a4a50;
            transition: all 0.2s ease;
        }
        .dd-item:hover, .dd-item.active {
            background-color: #fdf5f7;
            color: #c1466a;
            font-weight: 600;
        }
        
        /* ปุ่มเมนูหลัก */
        .nav-pill {
            text-decoration: none;
            padding: 7px 13px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .nav-pill.active {
            background: #fbe4ea;
            color: #c1466a;
            font-weight: 600;
        }
        .nav-pill:not(.active) {
            color: #8a7078;
        }
        .nav-pill:hover:not(.active) {
            background: rgba(251, 228, 234, 0.5);
            color: #c1466a;
        }

        /* จัดการเส้นคั่นเมนูผู้ใช้งานเฉพาะในจอมือถือ (แก้ปัญหาเส้นโผล่ในจอคอม) */
        @media (max-width: 991.98px) {
            .mobile-user-panel {
                margin-top: 15px !important;
                padding-top: 15px !important;
                border-top: 1px solid #f2e2e7;
            }
        }
    </style>
</head>

<body class="bg-pastel-theme">
    
    <!-- ฝังชุดไอคอน SVG ของฝ่ายออกแบบ -->
    <svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
        <symbol id="i-building" viewBox="0 0 24 24"><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h3a2 2 0 0 1 2 2v10"/><path d="M3 21h18"/><path d="M9.5 7h3M11 5.5v3"/></symbol>
        <symbol id="i-box" viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></symbol>
        <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3.2 20a5.8 5.8 0 0 1 11.6 0"/><path d="M16 5.2a3 3 0 0 1 0 5.6M21 20a5.8 5.8 0 0 0-3.5-5.3"/></symbol>
        <symbol id="i-award" viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="M8.4 13.3L7 22l5-3 5 3-1.4-8.7"/></symbol>
        <symbol id="i-filter" viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></symbol>
        <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v3.5M16 3v3.5"/></symbol>
        <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.4"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/></symbol>
        <symbol id="i-list" viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></symbol>
        <symbol id="i-hash" viewBox="0 0 24 24"><path d="M9 4L7 20M17 4l-2 16M5 9.5h15M4 14.5h15"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-3.9-3.9"/></symbol>
        <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 3v12M7.5 10.5l4.5 4.5 4.5-4.5M5 20h14"/></symbol>
        <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.2v5l3.3 2"/></symbol>
        <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M20.5 12a8.5 8.5 0 1 1-2.4-5.9"/><path d="M20.5 3.5V8h-4.5"/></symbol>
        <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.2"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.2 5.2l2.1 2.1M16.7 16.7l2.1 2.1M18.8 5.2l-2.1 2.1M7.3 16.7l-2.1 2.1"/></symbol>
        <symbol id="i-table" viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><path d="M3.5 9.7h17M9.2 9.7V19.5M14.8 9.7V19.5"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V11M9.3 20V5M14.7 20v-8"/><path d="M4 20h16"/></symbol>
        <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></symbol>
        <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></symbol>
        <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></symbol>
        <symbol id="i-file" viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/></symbol>
        <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
        <symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4M7.5 8.5L12 4l4.5 4.5M5 20h14"/></symbol>
        <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12l9-9h8v8l-9 9z"/><circle cx="16.5" cy="7.5" r="1.4"/></symbol>
        <symbol id="i-briefcase" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></symbol>
    </defs></svg>

    <canvas id="bg-canvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none;"></canvas>
    
    <?php
    $allowed_roles = ['Admin', 'Administrator', 'แอดมิน', 'ผู้บริหาร', 'Executive'];
    $user_position = isset($_SESSION['position']) ? $_SESSION['position'] : '';
    $current_page = $_GET['page'] ?? 'dashboard';
    ?>

    <?php if ($request !== 'login'): ?>
        <!-- Navbar Responsive -->
        <nav class="navbar navbar-expand-lg navbar-pastel-new sticky-top">
            <div class="container-fluid px-3 px-md-4">
                
                <!-- โลโก้ -->
                <a class="navbar-brand d-flex align-items-center gap-2" href="?page=dashboard">
                    <div style="width:26px;height:26px;border-radius:9px;background:linear-gradient(135deg,#e88aa5,#c1466a)"></div>
                    <span style="font-weight:600;font-size:16px;color:#4a3b40;">Prospectlisti</span>
                </a>
                
                <!-- ปุ่มแฮมเบอร์เกอร์ สำหรับมือถือ -->
                <button class="navbar-toggler border-0 shadow-none px-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <!-- เมนูหลัก (ปรับให้ดันไปชิดขวาด้วย ms-lg-auto) -->
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0 ms-lg-auto">
                        <a href="?page=dashboard" class="nav-pill <?= $current_page == 'dashboard' ? 'active' : '' ?>">แดชบอร์ด</a>
                        <a href="?page=contacts" class="nav-pill <?= strpos($current_page, 'contact') !== false ? 'active' : '' ?>">เข้าพบลูกค้า</a>
                        
                        <!-- เมนูจัดการข้อมูล (Dropdown) -->
                        <div data-dd style="position:relative">
                            <span data-dd-btn class="nav-pill <?= in_array($current_page, ['customers', 'customer_add', 'customer_edit', 'employees', 'employee_add', 'employee_edit', 'categories', 'products', 'product_add', 'product_edit']) ? 'active' : '' ?>">
                                จัดการข้อมูล ▾
                            </span>
                            <div data-dd-menu class="dd-menu">
                                <a href="?page=customers" class="dd-item <?= strpos($current_page, 'customer') !== false ? 'active' : '' ?>">ข้อมูลลูกค้า</a>
                                <?php if (in_array($user_position, $allowed_roles)): ?>
                                    <a href="?page=employees" class="dd-item <?= strpos($current_page, 'employee') !== false ? 'active' : '' ?>">ข้อมูลพนักงาน</a>
                                    <hr style="margin: 5px 0; border-color: #f2e2e7;">
                                    <a href="?page=categories" class="dd-item <?= $current_page == 'categories' ? 'active' : '' ?>">ข้อมูลแบรนด์ / ชื่อสินค้า</a>
                                    <a href="?page=products" class="dd-item <?= strpos($current_page, 'product') !== false && $current_page != 'report_products' ? 'active' : '' ?>">ข้อมูลสินค้า (รุ่น)</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- เมนูรายงานสถิติ (Dropdown) -->
                        <div data-dd style="position:relative">
                            <span data-dd-btn class="nav-pill <?= in_array($current_page, ['reports', 'report_products']) ? 'active' : '' ?>">
                                รายงานสถิติ ▾
                            </span>
                            <div data-dd-menu class="dd-menu">
                                <a href="?page=reports" class="dd-item <?= $current_page == 'reports' ? 'active' : '' ?>">รายงานการเข้าพบ</a>
                                <a href="?page=report_products" class="dd-item <?= $current_page == 'report_products' ? 'active' : '' ?>">สรุปยอดขายเชิงลึก</a>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลผู้ใช้งานฝั่งขวา -->
                    <div class="d-flex align-items-center ms-lg-3 mobile-user-panel ps-lg-3 border-start-lg" style="border-color:#f2e2e7 !important;">
                        <span class="me-3" style="color: #4a3b40; font-size: 14px; font-weight: 500;">
                            <i class="fa-solid fa-circle-user fa-lg me-1" style="color: #c1466a;"></i>
                            <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'ผู้ใช้งาน'; ?>
                            <small class="badge ms-1" style="background-color: #fbe4ea; color: #c1466a; border: 1px solid #f4c9d5; padding: 4px 7px;"><?= htmlspecialchars($user_position) ?></small>
                        </span>
                        <a href="index.php?route=logout" onclick="return confirm('ยืนยันการออกจากระบบ?')" class="btn btn-sm rounded-pill px-3 shadow-sm" style="background-color: #c1466a; color: #fff; border: none; font-size: 13px;">
                            <i class="fa-solid fa-power-off"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- พื้นที่แสดงเนื้อหาหลัก -->
    <div style="<?= $request === 'login' ? '' : 'max-width:1300px; margin:0 auto; padding: 25px 15px;' ?>" class="<?php echo $request === 'login' ? 'login-layout' : ''; ?>">
        <?php require $template; ?>
    </div>

    <!-- นำเข้า SweetAlert2 & Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // ==============================================================
            // 0. ระบบ Dropdown ของ Navbar ใหม่
            // ==============================================================
            document.addEventListener('click', function(e) {
                var ddb = e.target.closest && e.target.closest('[data-dd-btn]');
                if(ddb) {
                    e.stopPropagation();
                    var menu = ddb.parentNode.querySelector('[data-dd-menu]');
                    var open = menu.style.display === 'block';
                    // ปิดเมนูอื่นทั้งหมดก่อน
                    document.querySelectorAll('[data-dd-menu]').forEach(function(m){ m.style.display = 'none'; });
                    // สลับสถานะเมนูที่กด
                    menu.style.display = open ? 'none' : 'block';
                    return;
                }
                // ถ้าคลิกที่อื่น ให้ปิดเมนูให้หมด
                document.querySelectorAll('[data-dd-menu]').forEach(function(m){ m.style.display = 'none'; });
            });

            // ==============================================================
            // 1. ระบบแจ้งเตือนมุมล่างขวา (Toast Notifications) สำหรับบันทึก/แก้ไข/ลบ
            // ==============================================================
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 4000, 
                timerProgressBar: true,
                background: '#ffffff',
                color: '#333333',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            <?php if (isset($_SESSION['success'])): ?>
                Toast.fire({
                    icon: 'success',
                    title: '<?= addslashes($_SESSION['success']) ?>'
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                Toast.fire({
                    icon: 'error',
                    title: '<?= addslashes($_SESSION['error']) ?>'
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // ==============================================================
            // 2. สคริปต์แปลง confirm() แบบเก่า ให้เป็น Popup สวยๆ ตรงกลาง
            // ==============================================================
            document.querySelectorAll('a[onclick*="return confirm"]').forEach(function(el) {
                let match = el.getAttribute('onclick').match(/confirm\(['"]([^'"]+)['"]\)/);
                let msg = match ? match[1] : 'คุณแน่ใจหรือไม่ที่จะทำรายการนี้?';
                el.removeAttribute('onclick');

                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    let href = this.getAttribute('href');

                    Swal.fire({
                        title: 'ยืนยันการทำรายการ?',
                        text: msg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#c1466a',
                        cancelButtonColor: '#b09aa2',
                        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> ใช่, ดำเนินการเลย',
                        cancelButtonText: 'ยกเลิก',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href; 
                        }
                    });
                });
            });

            document.querySelectorAll('button[onclick*="return confirm"]').forEach(function(el) {
                let match = el.getAttribute('onclick').match(/confirm\(['"]([^'"]+)['"]\)/);
                let msg = match ? match[1] : 'คุณแน่ใจหรือไม่ที่จะทำรายการนี้?';
                el.removeAttribute('onclick');

                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    let form = this.closest('form');
                    let formaction = this.getAttribute('formaction'); 

                    let btnColor = '#3f74c4'; 
                    if (msg.includes('ลบ') || msg.includes('ทำลาย') || msg.includes('อันตราย') || msg.includes('เตือน')) {
                        btnColor = '#c1466a'; 
                    } else if (msg.includes('อัปเดต') || msg.includes('เคลียร์')) {
                        btnColor = '#2fa572'; 
                    }

                    Swal.fire({
                        title: 'ยืนยันการทำรายการ?',
                        text: msg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#b09aa2',
                        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> ใช่, ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (formaction) {
                                form.action = formaction;
                            }
                            form.submit(); 
                        }
                    });
                });
            });
        });

        // ==============================================================
        // 3. เอฟเฟกต์พื้นหลัง (Canvas Particles)
        // ==============================================================
        (function() {
            const canvas = document.getElementById('bg-canvas');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            let width, height;
            let particles = [];

            const PASTEL_COLORS = [
                'rgba(248, 187, 208, OPACITY)', // pink
                'rgba(181, 200, 248, OPACITY)', // blue
                'rgba(200, 247, 228, OPACITY)', // mint
                'rgba(224, 195, 252, OPACITY)', // lavender
                'rgba(255, 224, 178, OPACITY)' // peach
            ];

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }

            function randomColor(opacity) {
                const c = PASTEL_COLORS[Math.floor(Math.random() * PASTEL_COLORS.length)];
                return c.replace('OPACITY', opacity);
            }

            class Particle {
                constructor() {
                    this.reset();
                }
                reset() {
                    this.x = Math.random() * width;
                    this.y = Math.random() * height;
                    this.radius = Math.random() * 5 + 2;
                    this.speedX = (Math.random() - 0.5) * 0.4;
                    this.speedY = (Math.random() - 0.5) * 0.4 - 0.15;
                    this.opacity = Math.random() * 0.35 + 0.15;
                    this.color = randomColor(this.opacity);
                    this.pulse = Math.random() * Math.PI * 2;
                    this.pulseSpeed = Math.random() * 0.02 + 0.01;
                }
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    this.pulse += this.pulseSpeed;
                    if (this.x < -20) this.x = width + 20;
                    if (this.x > width + 20) this.x = -20;
                    if (this.y < -20) this.y = height + 20;
                    if (this.y > height + 20) this.y = -20;
                }
                draw() {
                    const pulseRadius = this.radius + Math.sin(this.pulse) * 1.2;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, Math.max(pulseRadius, 0.5), 0, Math.PI * 2);
                    ctx.fillStyle = this.color;
                    ctx.fill();
                }
            }

            function createParticles() {
                const count = Math.min(60, Math.floor((width * height) / 22000));
                particles = Array.from({ length: count }, () => new Particle());
            }

            function animate() {
                ctx.clearRect(0, 0, width, height);
                particles.forEach((p) => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animate);
            }

            window.addEventListener('resize', () => {
                resize();
                createParticles();
            });
            resize();
            createParticles();
            animate();
        })();
    </script>
</body>
</html>