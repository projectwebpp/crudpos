<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'กรุณาเข้าสู่ระบบก่อน'];
    redirect('../auth/login.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

try {
    if ($action === 'create') {
        // เพิ่มสินค้าใหม่
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $barcode = !empty($_POST['barcode']) ? trim($_POST['barcode']) : null;
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($name) || $price < 0 || $quantity < 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'ข้อมูลไม่ถูกต้อง'];
            redirect('../products.php');
        }
        
        // ถ้าไม่มีบาร์โค้ด ให้สร้างอัตโนมัติ
        if (empty($barcode)) {
            $barcode = generateAutoBarcode($pdo);
        } else {
            // ตรวจสอบบาร์โค้ดซ้ำ
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
            $stmt->execute([$barcode]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'บาร์โค้ดนี้มีอยู่ในระบบแล้ว'];
                redirect('../products.php');
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, category_id, barcode, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $quantity, $category_id, $barcode, $_SESSION['user_id']]);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'เพิ่มสินค้าสำเร็จ!'];
        
    } elseif ($action === 'update') {
        // แก้ไขสินค้า
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $barcode = !empty($_POST['barcode']) ? trim($_POST['barcode']) : null;
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($name) || $price < 0 || $quantity < 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'ข้อมูลไม่ถูกต้อง'];
            redirect('../products.php');
        }
        
        // ตรวจสอบบาร์โค้ดซ้ำ (ยกเว้นสินค้าปัจจุบัน)
        if (!empty($barcode)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ? AND id != ?");
            $stmt->execute([$barcode, $id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'บาร์โค้ดนี้มีอยู่ในระบบแล้ว'];
                redirect('../products.php');
            }
        }
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category_id = ?, barcode = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $quantity, $category_id, $barcode, $id]);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'อัพเดทสินค้าสำเร็จ!'];
        
    } elseif ($action === 'delete') {
        // ลบสินค้า
        $id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'ลบสินค้าสำเร็จ!'];
        
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'การดำเนินการไม่ถูกต้อง'];
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
}

redirect('../products.php');

// ฟังก์ชันสร้างบาร์โค้ดอัตโนมัติ
function generateAutoBarcode($pdo) {
    $prefix = '885'; // รหัสประเทศไทย
    $maxAttempts = 10;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $randomDigits = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $barcode = $prefix . $randomDigits;
        
        // คำนวณ checksum EAN-13
        $sum = 0;
        for ($j = 0; $j < 12; $j++) {
            $digit = intval($barcode[$j]);
            $sum += ($j % 2 === 0) ? $digit : $digit * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        $finalBarcode = $barcode . $checksum;
        
        // ตรวจสอบว่าบาร์โค้ดนี้มีอยู่แล้วหรือไม่
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
        $stmt->execute([$finalBarcode]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            return $finalBarcode;
        }
    }
    
    // ถ้าสุ่มซ้ำหลายครั้ง ให้ใช้ timestamp-based barcode
    return $prefix . str_pad(time() % 1000000000, 9, '0', STR_PAD_LEFT) . '1';
}
?>