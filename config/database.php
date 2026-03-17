<?php
// เริ่มต้น Session สำหรับระบบ Login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล (Database Config) ---
define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12820323');
define('DB_PASS', '3byAVLpJSr');
define('DB_NAME', 'sql12820323');
// เปลี่ยน BASE_URL เป็น URL ของ Render ของคุณ
define('BASE_URL', 'https://crudpos.onrender.com'); 

// --- 2. เริ่มต้นการเชื่อมต่อด้วย PDO ---
try {
    // สร้าง DSN สำหรับการเชื่อมต่อ MySQL
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // ตั้งค่า Options สำหรับ PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ให้แจ้งเตือนเมื่อเกิด Error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ให้ดึงข้อมูลแบบ Array Key
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ปิดการจำลองการ Prepare Statement
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // หากเชื่อมต่อไม่ได้ ให้หยุดการทำงานและแสดงข้อความ Error
    die("Connection failed: " . $e->getMessage());
}

// --- 3. ฟังก์ชันเสริมสำหรับระบบ (Helper Functions) ---

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันสำหรับย้ายหน้า (Redirect)
function redirect($url) {
    header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    exit();
}

// ฟังก์ชันป้องกันการโจมตี XSS (ใช้ตอนแสดงผลตัวหนังสือ)
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันตั้งค่าข้อความแจ้งเตือน (Alert Message)
function setMessage($type, $message) {
    $_SESSION['message_type'] = $type;
    $_SESSION['message'] = $message;
}

// ฟังก์ชันแสดงข้อความแจ้งเตือนในหน้า HTML
function showMessage() {
    if (isset($_SESSION['message'])) {
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
        $message = $_SESSION['message'];
        
        // กำหนดสีตามประเภท Error หรือ Success (ใช้ Tailwind CSS ตามโค้ดเดิมของคุณ)
        $bg_color = ($type == 'error') ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
        $icon = ($type == 'error') ? 'fa-exclamation-triangle' : 'fa-check-circle';
        
        echo "<div class='$bg_color border px-4 py-3 rounded mb-4 flex items-center'>
                <i class='fas $icon mr-3'></i>
                <span>$message</span>
              </div>";
        
        // ลบข้อความออกหลังจากแสดงผลแล้ว
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// ฟังก์ชันตรวจสอบสิทธิ์ (Role Checking)
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
