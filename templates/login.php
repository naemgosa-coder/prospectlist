<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="scene">
    <div class="left-panel">
        <div class="blob blob1"></div>
        <div class="blob blob2"></div>
        <div class="blob blob3"></div>

        <div class="brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#c1466a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
        </div>

        <div class="brand-name" style="color: #4a3b40;">Prospectlisti</div>
        <div class="brand-tagline" style="color: #8a7078;">ระบบจัดการข้อมูลการเข้าพบลูกค้า</div>

        <div class="dots-deco">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <div class="right-panel">
        <div class="form-title text-center" style="color: #4a3b40;">เข้าสู่ระบบ</div>
        <div class="form-sub text-center" style="color: #9a828a;">ยินดีต้อนรับ 👋</div>

		<!--
        <div class="alert border-0 rounded-3 mb-3 p-2 text-center shadow-sm" style="background: #fdf5f7; color: #c1466a; font-size: 0.85rem; border: 1px solid #f4c9d5 !important;">
            <strong><i class="fa-solid fa-circle-info me-1"></i> Demo:</strong> admin / password123
        </div>  -->

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error shadow-sm" style="background: #fff0f3; border: 1px solid #f8b8c8; color: #c0405a; border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 1rem;">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="index.php?route=login_action" id="loginForm" novalidate>
            <label class="field-label" for="username" style="color: #8a7078;">USERNAME</label>
            <div class="field-wrap">
                <svg class="field-icon" style="color: #c9b3bb;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                </svg>
                <input class="field-input" type="text" id="username" name="username" placeholder="กรอก Username ของคุณ" required />
            </div>

            <label class="field-label" for="password" style="color: #8a7078;">PASSWORD</label>
            <div class="field-wrap">
                <svg class="field-icon" style="color: #c9b3bb;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0110 0v4" />
                </svg>
                <input class="field-input" type="password" id="password" name="password" placeholder="••••••••" required />
            </div>

            <button type="submit" class="btn-login mt-3">เข้าสู่ระบบ &rarr;</button>
        </form>

        <div class="text-center mt-4 pt-3 border-top" style="color: #b09aa2; font-size: 0.8rem; border-color: #f2e2e7 !important;">
            <p class="mb-0"><i class="fa-solid fa-shield-alt" style="color: #2fa572;"></i> Secure Login • All data encrypted</p>
        </div>
    </div>
</div>