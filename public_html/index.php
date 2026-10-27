<?php
// เปิดการแสดงผล Error ชั่วคราว
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// กำหนด Path พื้นฐาน
define('BASE_PATH', dirname(__DIR__));

session_start();
require_once BASE_PATH . '/config/db.php';

// --- Front controller: ทุก request เข้าทางไฟล์นี้ไฟล์เดียว ---
// ?route=xxx  -> สคริปต์ประมวลผลใน src/Actions/xxx.php
// ?page=xxx   -> หน้าเทมเพลตใน templates/xxx.php
$route = $_GET['route'] ?? null;
$page = $_GET['page'] ?? 'dashboard';

// อนุญาตเฉพาะ a-z และ _ เพื่อกันการ include ไฟล์นอกโฟลเดอร์ (path traversal)
$isValid = static fn(?string $name): bool => $name !== null && preg_match('/^[a-z_]+$/', $name) === 1;

$isLoggedIn = isset($_SESSION['user_id']);

if ($route !== null) {
    $action = BASE_PATH . '/src/Actions/' . $route . '.php';
    if (!$isValid($route) || !is_file($action)) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }
    // --- ระบบล็อคประตู: ทุก action ต้องล็อกอินก่อน ยกเว้นการล็อกอินเอง ---
    if (!$isLoggedIn && $route !== 'login_action') {
        header("Location: index.php?page=login");
        exit;
    }
    require $action;
    exit;
}

// --- ระบบล็อคประตู (Authentication Check) ---
// ถ้ายากเข้าหน้าอื่นที่ไม่ใช่ 'login' แต่ยังไม่ได้ล็อกอิน ให้เด้งไปหน้า login
if (!$isLoggedIn && $page !== 'login') {
    header("Location: index.php?page=login");
    exit;
}
// ถ้าล็อกอินแล้ว แต่พยายามจะเข้าหน้า login อีก ให้เด้งไปหน้า dashboard
if ($isLoggedIn && $page === 'login') {
    header("Location: index.php?page=dashboard");
    exit;
}

// ชื่อเดิมที่ layout.php ใช้อยู่
$request = $page;

// เลือก Template ที่จะแสดง
$template = BASE_PATH . '/templates/' . $page . '.php';

// ถ้าไม่มีไฟล์ Template ให้แสดงหน้า 404
if (!$isValid($page) || !is_file($template)) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

// โหลดตัวโครงร่างหลัก (Layout)
require BASE_PATH . '/templates/layout.php';
