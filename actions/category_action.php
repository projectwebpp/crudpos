<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $color = $_POST['color'];
        $icon = trim($_POST['icon']);
        
        // Validation
        if (empty($name) || empty($color) || empty($icon)) {
            setMessage('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            redirect('../categories.php');
        }
        
        if (strlen($name) < 2) {
            setMessage('error', 'ชื่อหมวดหมู่ต้องมีความยาวอย่างน้อย 2 ตัวอักษร');
            redirect('../categories.php');
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, color, icon, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $color, $icon, $_SESSION['user_id']])) {
            setMessage('success', 'เพิ่มหมวดหมู่สำเร็จ!');
        } else {
            setMessage('error', 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่');
        }
    }
    elseif ($action == 'update') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $color = $_POST['color'];
        $icon = trim($_POST['icon']);
        
        // Validation
        if (empty($name) || empty($color) || empty($icon)) {
            setMessage('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            redirect('../categories.php');
        }
        
        if (strlen($name) < 2) {
            setMessage('error', 'ชื่อหมวดหมู่ต้องมีความยาวอย่างน้อย 2 ตัวอักษร');
            redirect('../categories.php');
        }
        
        // ตรวจสอบว่าผู้ใช้เป็นเจ้าของหมวดหมู่หรือเป็น admin
        $stmt = $pdo->prepare("SELECT created_by FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        if ($category && ($_SESSION['role'] == 'admin' || $category['created_by'] == $_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ?, icon = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $description, $color, $icon, $id])) {
                setMessage('success', 'อัพเดทหมวดหมู่สำเร็จ!');
            } else {
                setMessage('error', 'เกิดข้อผิดพลาดในการอัพเดทหมวดหมู่');
            }
        } else {
            setMessage('error', 'คุณไม่มีสิทธิ์แก้ไขหมวดหมู่นี้');
        }
    }
}
elseif ($action == 'delete') {
    $id = intval($_GET['id']);
    
    // ตรวจสอบว่าผู้ใช้เป็นเจ้าของหมวดหมู่หรือเป็น admin
    $stmt = $pdo->prepare("SELECT created_by FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if ($category && ($_SESSION['role'] == 'admin' || $category['created_by'] == $_SESSION['user_id'])) {
        // ตรวจสอบว่ามีสินค้าในหมวดหมู่นี้หรือไม่
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $product_count = $stmt->fetchColumn();
        
        if ($product_count > 0) {
            // อัพเดทสินค้าให้ไม่มีหมวดหมู่
            $stmt = $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
            $stmt->execute([$id]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            setMessage('success', 'ลบหมวดหมู่สำเร็จ!' . ($product_count > 0 ? ' สินค้า ' . $product_count . ' รายการถูกเปลี่ยนเป็นไม่มีหมวดหมู่' : ''));
        } else {
            setMessage('error', 'เกิดข้อผิดพลาดในการลบหมวดหมู่');
        }
    } else {
        setMessage('error', 'คุณไม่มีสิทธิ์ลบหมวดหมู่นี้');
    }
}

redirect('../categories.php');
?>