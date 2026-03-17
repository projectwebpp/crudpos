<?php
session_start();

// ตั้งค่าฐานข้อมูล
define('DB_HOST', 'sql307.infinityfree.com');
define('DB_USER', 'if0_38904313');
define('DB_PASS', 'RyvwsdTAZ3LMj');
define('DB_NAME', 'if0_38904313_crud_system');
define('BASE_URL', 'https://allaboutitscript.42web.io/crudphp/');

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ฟังก์ชันตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันรีไดเร็กต์
function redirect($url) {
    header("Location: " . $url);
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
        if (isset($_SESSION['message_type'])) {
            unset($_SESSION['message_type']);
        }
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
