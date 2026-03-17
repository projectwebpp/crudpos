<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// อนุญาตเฉพาะ admin
if ($_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = intval($_GET['id']);

try {
    // ดึงข้อมูลผู้ใช้งาน
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM products WHERE created_by = u.id) as product_count,
               (SELECT COUNT(*) FROM categories WHERE created_by = u.id) as category_count
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        // ไม่ส่งรหัสผ่านกลับ
        unset($user['password']);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้งาน']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>