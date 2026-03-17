<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. ตั้งค่าฐานข้อมูล (แก้ไขจุดที่ผิด) ---
define('DB_HOST', 'localhost'); // แก้จาก localhost เป็นชื่อโฮสต์จริง
define('DB_USER', 'if0_38904313');
define('DB_PASS', 'RyvwsdTAZ3LMj');
define('DB_NAME', 'https://if0_38904313_crud_system');

// แก้ไข BASE_URL ให้เป็นที่อยู่เว็บของคุณบน Render
define('BASE_URL', 'sql307.infinityfree.com'); 

// --- 2. การเชื่อมต่อฐานข้อมูล (PDO) ---
try {
    // กำหนดรายละเอียดการเชื่อมต่อ
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // ตั้งค่า Options เพื่อความเสถียร
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch(PDOException $e) {
    // ถ้ายังขึ้น could not find driver แสดงว่า Dockerfile ยังติดตั้งไม่สำเร็จ
    die("Connection failed: " . $e->getMessage());
}

// --- 3. ฟังก์ชันต่างๆ (ปรับปรุงให้ใช้ BASE_URL ได้ถูกต้อง) ---

// ฟังก์ชันตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันรีไดเร็กต์
function redirect($url) {
    // ปรับให้รองรับทั้ง URL เต็ม และ Path สั้นๆ
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: " . $url);
    } else {
        header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    }
    exit();
}

// ฟังก์ชันป้องกัน XSS
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันตั้งค่าข้อความแจ้งเตือน
function setMessage($type, $message) {
    $_SESSION['message_type'] = $type;
    $_SESSION['message'] = $message;
}

// ฟังก์ชันแสดงข้อความแจ้งเตือน
function showMessage() {
    if (isset($_SESSION['message'])) {
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
        $message = $_SESSION['message'];
        $bg_color = $type == 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
        $icon = $type == 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle';
        
        echo "<div class='$bg_color border px-4 py-3 rounded mb-4 flex items-center'>
                <i class='fas $icon mr-3'></i>
                <span>$message</span>
              </div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// ฟังก์ชันตรวจสอบสิทธิ์
function checkRole($allowed_roles = ['admin']) {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
    
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
    
    if (!in_array($user_role, $allowed_roles)) {
        setMessage('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect('dashboard.php');
    }
}
?>
