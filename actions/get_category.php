<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

$category_id = intval($_GET['id']);

try {
    // ดึงข้อมูลหมวดหมู่
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as creator, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN users u ON c.created_by = u.id 
        LEFT JOIN products p ON c.id = p.category_id 
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if ($category) {
        // ตรวจสอบสิทธิ์ (เฉพาะเจ้าของหรือ admin)
        if ($_SESSION['role'] != 'admin' && $category['created_by'] != $_SESSION['user_id']) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขหมวดหมู่นี้']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'category' => [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['description'],
                'color' => $category['color'],
                'icon' => $category['icon'],
                'created_by' => $category['created_by'],
                'creator' => $category['creator'],
                'product_count' => $category['product_count'],
                'created_at' => $category['created_at']
            ]
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบหมวดหมู่']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>