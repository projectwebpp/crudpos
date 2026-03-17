<?php
require_once 'config/database.php';

// ฟังก์ชันสร้างบาร์โค้ดอัตโนมัติ
function generateBarcode($productId) {
    $prefix = '885'; // รหัสประเทศไทย
    $productCode = str_pad($productId, 9, '0', STR_PAD_LEFT);
    return $prefix . $productCode;
}

try {
    // ดึงสินค้าที่ไม่มีบาร์โค้ด
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE barcode IS NULL OR barcode = ''");
    $stmt->execute();
    $products = $stmt->fetchAll();

    if (empty($products)) {
        echo "ไม่มีสินค้าที่ต้องการเพิ่มบาร์โค้ด\n";
        exit;
    }

    $pdo->beginTransaction();

    foreach ($products as $product) {
        $barcode = generateBarcode($product['id']);
        
        $updateStmt = $pdo->prepare("UPDATE products SET barcode = ? WHERE id = ?");
        $updateStmt->execute([$barcode, $product['id']]);
        
        echo "เพิ่มบาร์โค้ด {$barcode} ให้กับสินค้า: {$product['name']}\n";
    }

    $pdo->commit();
    echo "เพิ่มบาร์โค้ดสำเร็จทั้งหมด " . count($products) . " รายการ\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}
?>