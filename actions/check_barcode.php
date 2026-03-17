<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['barcode'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Barcode is required']);
    exit();
}

$barcode = trim($_GET['barcode']);
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

try {
    if ($product_id) {
        // กรณีแก้ไขสินค้า - ตรวจสอบบาร์โค้ดซ้ำยกเว้นสินค้าปัจจุบัน
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE barcode = ? AND id != ?");
        $stmt->execute([$barcode, $product_id]);
    } else {
        // กรณีเพิ่มสินค้าใหม่ - ตรวจสอบบาร์โค้ดซ้ำทั้งหมด
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE barcode = ?");
        $stmt->execute([$barcode]);
    }
    
    $result = $stmt->fetch();
    $exists = $result['count'] > 0;
    
    echo json_encode(['exists' => $exists]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>