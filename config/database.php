<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ค่ากำหนดฐานข้อมูล ---
define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12820323');
define('DB_PASS', '3byAVLpJSr');
define('DB_NAME', 'sql12820323');
define('BASE_URL', 'https://crudpos.onrender.com'); 

// --- เริ่มการเชื่อมต่อ ---
try {
    // ระบุ Host และ DB Name ให้ชัดเจน (หลีกเลี่ยง localhost)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // ถ้าเชื่อมต่อไม่ได้ จะแสดง Error ที่ชัดเจนขึ้น
    die("Database Connection Error: " . $e->getMessage());
}

// --- ฟังก์ชันพื้นฐาน ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    exit();
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
