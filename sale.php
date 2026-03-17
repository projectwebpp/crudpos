<?php
// sale.php - ระบบขายสินค้าพร้อมสแกนบาร์โค้ดและพร้อมเพย์
ob_start();

require_once 'config/database.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$page_title = 'ระบบขายสินค้า';

// ตั้งค่าหมายเลขพร้อมเพย์
$promptpay_number = "0912345678";

// ดึงข้อมูลสินค้าสำหรับขาย
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.color as category_color
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.quantity > 0
    ORDER BY p.name ASC
");
$stmt->execute();
$products = $stmt->fetchAll();

// ดึงข้อมูลหมวดหมู่สำหรับฟิลเตอร์
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// ตัวแปรสำหรับตะกร้าสินค้า
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// เพิ่มสินค้าในตะกร้า
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // ตรวจสอบว่ามีสินค้าในสต็อกพอหรือไม่
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND quantity >= ?");
    $stmt->execute([$product_id, $quantity]);
    $product = $stmt->fetch();
    
    if ($product) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'max_quantity' => $product['quantity']
            ];
        }
        $_SESSION['message'] = ['type' => 'success', 'text' => 'เพิ่มสินค้าในตะกร้าสำเร็จ!'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'สินค้าไม่เพียงพอในสต็อก'];
    }
    header('Location: sale.php');
    exit();
}

// เพิ่มสินค้าด้วยบาร์โค้ด
if (isset($_POST['add_by_barcode'])) {
    $barcode = trim($_POST['barcode']);
    
    if (!empty($barcode)) {
        // ค้นหาสินค้าด้วยบาร์โค้ด
        $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = ? AND quantity > 0");
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
        
        if ($product) {
            $product_id = $product['id'];
            $quantity = 1;
            
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'max_quantity' => $product['quantity']
                ];
            }
            $_SESSION['message'] = ['type' => 'success', 'text' => 'เพิ่มสินค้าในตะกร้าสำเร็จ!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'ไม่พบสินค้าที่มีบาร์โค้ดนี้ หรือสินค้าหมดสต็อก'];
        }
    }
    header('Location: sale.php');
    exit();
}

// ลบสินค้าจากตะกร้า
if (isset($_GET['remove_from_cart'])) {
    $product_id = intval($_GET['remove_from_cart']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'ลบสินค้าจากตะกร้าสำเร็จ!'];
    }
    header('Location: sale.php');
    exit();
}

// อัพเดทจำนวนสินค้าในตะกร้า
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            // ตรวจสอบสต็อก
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product && $quantity <= $product['quantity']) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'จำนวนสินค้าไม่เพียงพอในสต็อก'];
            }
        }
    }
    header('Location: sale.php');
    exit();
}

// ล้างตะกร้า
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['message'] = ['type' => 'success', 'text' => 'ล้างตะกร้าสำเร็จ!'];
    header('Location: sale.php');
    exit();
}

// บันทึกรายการขาย (ชำระเงินสด)
if (isset($_POST['checkout_cash'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'ไม่มีสินค้าในตะกร้า'];
        header('Location: sale.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // คำนวณยอดรวม
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // บันทึกรายการขาย
        $stmt = $pdo->prepare("INSERT INTO sales (total_amount, payment_method, created_by) VALUES (?, 'cash', ?)");
        $stmt->execute([$total_amount, $_SESSION['user_id']]);
        $sale_id = $pdo->lastInsertId();
        
        // บันทึกรายการสินค้าในรายการขาย
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $total_price = $item['price'] * $item['quantity'];
            $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $total_price]);
            
            // อัพเดทสต็อกสินค้า
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['id']]);
        }
        
        $pdo->commit();
        
        // บันทึกรายการขายสำเร็จ
        $_SESSION['last_sale_id'] = $sale_id;
        $_SESSION['last_sale_amount'] = $total_amount;
        $_SESSION['last_sale_items'] = $_SESSION['cart']; // เก็บข้อมูลสินค้าสำหรับใบเสร็จ
        $_SESSION['cart'] = [];
        
        // ลบข้อมูล QR ถ้ามี
        unset($_SESSION['show_promptpay']);
        unset($_SESSION['payment_amount']);
        unset($_SESSION['qr_expire_time']);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'บันทึกรายการขายสำเร็จ!'];
        header('Location: sale.php?success=1');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาดในการบันทึกรายการขาย: ' . $e->getMessage()];
        header('Location: sale.php');
        exit();
    }
}

// บันทึกรายการขาย (ชำระเงินด้วยพร้อมเพย์)
if (isset($_POST['checkout_promptpay'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'ไม่มีสินค้าในตะกร้า'];
        header('Location: sale.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // คำนวณยอดรวม
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // บันทึกรายการขาย
        $stmt = $pdo->prepare("INSERT INTO sales (total_amount, payment_method, created_by) VALUES (?, 'promptpay', ?)");
        $stmt->execute([$total_amount, $_SESSION['user_id']]);
        $sale_id = $pdo->lastInsertId();
        
        // บันทึกรายการสินค้าในรายการขาย
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $total_price = $item['price'] * $item['quantity'];
            $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $total_price]);
            
            // อัพเดทสต็อกสินค้า
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['id']]);
        }
        
        $pdo->commit();
        
        // บันทึกรายการขายสำเร็จ
        $_SESSION['last_sale_id'] = $sale_id;
        $_SESSION['last_sale_amount'] = $total_amount;
        $_SESSION['last_sale_items'] = $_SESSION['cart']; // เก็บข้อมูลสินค้าสำหรับใบเสร็จ
        $_SESSION['cart'] = [];
        unset($_SESSION['show_promptpay']);
        unset($_SESSION['payment_amount']);
        unset($_SESSION['qr_expire_time']);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'บันทึกรายการขายพร้อมเพย์สำเร็จ!'];
        header('Location: sale.php?success=1');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาดในการบันทึกรายการขาย: ' . $e->getMessage()];
        header('Location: sale.php');
        exit();
    }
}

// แสดงฟอร์มชำระเงินพร้อมเพย์
if (isset($_POST['show_promptpay'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'ไม่มีสินค้าในตะกร้า'];
        header('Location: sale.php');
        exit();
    }
    
    // คำนวณยอดรวม
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    $_SESSION['show_promptpay'] = true;
    $_SESSION['payment_amount'] = $total_amount;
    $_SESSION['qr_expire_time'] = time() + 60; // 1 นาที
    
    header('Location: sale.php');
    exit();
}

// ยกเลิกการชำระเงินพร้อมเพย์
if (isset($_POST['cancel_promptpay'])) {
    unset($_SESSION['show_promptpay']);
    unset($_SESSION['payment_amount']);
    unset($_SESSION['qr_expire_time']);
    header('Location: sale.php');
    exit();
}

// ตรวจสอบอายุ QR Code
if (isset($_SESSION['qr_expire_time']) && time() > $_SESSION['qr_expire_time']) {
    unset($_SESSION['show_promptpay']);
    unset($_SESSION['payment_amount']);
    unset($_SESSION['qr_expire_time']);
    $_SESSION['message'] = ['type' => 'error', 'text' => 'QR Code หมดอายุแล้ว กรุณาสร้างใหม่'];
    header('Location: sale.php');
    exit();
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ระบบขายสินค้า</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- เพิ่ม pdfmake และฟอนต์ภาษาไทย -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Noto Sans Thai', sans-serif; }
        .product-card { transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-2px); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .message-slide { animation: slideIn 0.3s ease-out; }
        @keyframes scan {
            0% { background-position: 0 -100%; }
            100% { background-position: 0 200%; }
        }
        .scanning {
            background: linear-gradient(to bottom, transparent 0%, rgba(59, 130, 246, 0.3) 50%, transparent 100%);
            background-size: 100% 200%;
            animation: scan 2s linear infinite;
        }
        .countdown-timer {
            font-size: 1.2em;
            font-weight: bold;
            color: #ef4444;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-cash-register mr-2 text-green-500"></i>
                        ระบบขายสินค้า
                    </h1>
                    <nav class="hidden md:flex space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-chart-bar mr-1"></i>แดชบอร์ด
                        </a>
                        <a href="products.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-boxes mr-1"></i>จัดการสินค้า
                        </a>
                        <a href="report.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-receipt mr-1"></i>รายงานการขาย
                        </a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-user mr-1"></i><?php echo $_SESSION['username'] ?? 'ผู้ใช้'; ?>
                    </span>
                    <a href="auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
                        <i class="fas fa-sign-out-alt mr-1"></i>ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Side - Products Grid -->
            <div class="lg:w-2/3 bg-white rounded-xl shadow-lg p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-store mr-3 text-green-500"></i>
                        สินค้าทั้งหมด
                    </h2>
                    
                    <!-- Search and Filter -->
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <input type="text" id="productSearch" 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="ค้นหาสินค้า...">
                        <select id="categoryFilter" 
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">หมวดหมู่ทั้งหมด</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Barcode Scanner Section -->
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                        <i class="fas fa-barcode mr-2"></i>สแกนบาร์โค้ดสินค้า
                    </h3>
                    <form method="POST" id="barcodeForm" class="flex items-center space-x-2">
                        <input type="text" name="barcode" id="barcodeInput" 
                               class="flex-1 px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="สแกนหรือป้อนบาร์โค้ดสินค้า..." autocomplete="off" autofocus>
                        <button type="submit" name="add_by_barcode"
                                class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-medium transition duration-200 flex items-center">
                            <i class="fas fa-plus mr-1"></i>เพิ่มสินค้า
                        </button>
                    </form>
                    <div class="mt-2 text-xs text-blue-600 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        สแกนบาร์โค้ดแล้วกด Enter เพื่อเพิ่มสินค้าอัตโนมัติ
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto max-h-[calc(100vh-400px)]">
                    <?php if (empty($products)): ?>
                        <div class="col-span-full text-center py-8 text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg mb-2">ไม่พบสินค้า</p>
                            <p class="text-sm text-gray-400">ไม่มีสินค้าในสต็อก</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="product-card bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition duration-200" 
                             data-category="<?php echo $product['category_id']; ?>">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" 
                                         style="background-color: <?php echo $product['category_color'] ? $product['category_color'] . '20' : '#EFF6FF'; ?>">
                                        <i class="fas fa-box text-sm" 
                                           style="color: <?php echo $product['category_color'] ?: '#3B82F6'; ?>"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="text-xs text-gray-500"><?php echo $product['category_name'] ?: 'ไม่มีหมวดหมู่'; ?></p>
                                        <?php if (!empty($product['barcode'])): ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <i class="fas fa-barcode mr-1"></i><?php echo $product['barcode']; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="text-lg font-bold text-green-600">฿<?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-boxes mr-1"></i>
                                    คงเหลือ: <?php echo number_format($product['quantity']); ?>
                                </span>
                                <?php if ($product['quantity'] <= 10): ?>
                                    <span class="text-xs px-2 py-1 bg-orange-100 text-orange-800 rounded-full">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>สต็อกต่ำ
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" class="product-form flex items-center space-x-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>"
                                       class="quantity-input w-20 px-2 py-1 border border-gray-300 rounded text-center focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <button type="submit" name="add_to_cart"
                                        class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-lg text-sm font-medium transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-cart-plus mr-1"></i>เพิ่ม
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side - Cart -->
            <div class="lg:w-1/3 bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-shopping-cart mr-3 text-blue-500"></i>
                        ตะกร้าสินค้า
                    </h2>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="?clear_cart=1" id="clearCartBtn"
                           class="text-red-500 hover:text-red-700 text-sm flex items-center">
                            <i class="fas fa-trash mr-1"></i>ล้างตะกร้า
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Cart Items -->
                <div class="space-y-3 mb-4 overflow-y-auto max-h-96">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-shopping-cart text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg mb-2">ตะกร้าว่าง</p>
                            <p class="text-sm text-gray-400">เลือกสินค้าจากรายการด้านซ้าย</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="cartForm">
                            <?php 
                            $cart_total = 0;
                            foreach ($_SESSION['cart'] as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $cart_total += $item_total;
                            ?>
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p class="text-green-600 font-bold">฿<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <a href="?remove_from_cart=<?php echo $item['id']; ?>" 
                                       class="text-red-400 hover:text-red-600 ml-2 remove-item-btn">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                                <div class="flex items-center justify-between">
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['max_quantity']; ?>"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <span class="text-gray-700 font-semibold">
                                        ฿<?php echo number_format($item_total, 2); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <button type="submit" name="update_cart"
                                    class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition duration-200 mt-4">
                                <i class="fas fa-sync-alt mr-1"></i>อัพเดทตะกร้า
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Cart Summary -->
                <?php if (!empty($_SESSION['cart'])): ?>
                <div class="border-t pt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">จำนวนสินค้า:</span>
                        <span class="font-semibold">
                            <?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?> ชิ้น
                        </span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span class="text-gray-800">ยอดรวม:</span>
                        <span class="text-green-600">฿<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <!-- Payment Options -->
                    <div class="grid grid-cols-1 gap-2">
                        <form method="POST">
                            <button type="submit" name="checkout_cash"
                                    class="w-full bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg font-bold text-sm transition duration-200 flex items-center justify-center">
                                <i class="fas fa-money-bill-wave mr-2"></i>ชำระเงินสด
                            </button>
                        </form>
                        
                        <form method="POST">
                            <button type="submit" name="show_promptpay"
                                    class="w-full bg-purple-500 hover:bg-purple-600 text-white py-3 px-4 rounded-lg font-bold text-sm transition duration-200 flex items-center justify-center">
                                <i class="fas fa-mobile-alt mr-2"></i>ชำระด้วยพร้อมเพย์
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Last Sale Info -->
                <?php if (isset($_SESSION['last_sale_id'])): ?>
                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center text-green-800 mb-1">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-semibold">ขายสำเร็จ!</span>
                    </div>
                    <p class="text-sm text-green-700">รายการขาย #<?php echo $_SESSION['last_sale_id']; ?></p>
                    <p class="text-sm text-green-700">ยอดขาย: ฿<?php echo number_format($_SESSION['last_sale_amount'], 2); ?></p>
                    <p class="text-xs text-green-600"><?php echo date('d/m/Y H:i:s'); ?></p>
                    <div class="mt-2 flex space-x-2">
                        <button onclick="printReceipt()" 
                                class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded text-sm transition duration-200 flex items-center">
                            <i class="fas fa-print mr-1"></i>พิมพ์ใบเสร็จ
                        </button>
                        <button onclick="downloadReceipt()" 
                                class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded text-sm transition duration-200 flex items-center">
                            <i class="fas fa-download mr-1"></i>ดาวน์โหลด
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PromptPay QR Code -->
                <?php if (isset($_SESSION['show_promptpay']) && $_SESSION['show_promptpay']): ?>
                <div class="mt-4 p-4 bg-white border border-purple-200 rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-purple-800 mb-3 flex items-center justify-center">
                        <i class="fas fa-qrcode mr-2"></i>สแกนชำระด้วยพร้อมเพย์
                    </h3>
                    
                    <div class="text-center mb-3">
                        <div class="bg-gray-100 p-4 rounded-lg inline-block">
                            <img src="https://promptpay.io/<?php echo $promptpay_number; ?>/<?php echo $_SESSION['payment_amount']; ?>.png" 
                                 alt="QR Code PromptPay" 
                                 class="w-64 h-64 mx-auto border-4 border-white rounded">
                        </div>
                    </div>
                    
                    <div class="text-center text-sm text-gray-600 mb-3">
                        <p class="text-lg font-bold text-purple-600">฿<?php echo number_format($_SESSION['payment_amount'], 2); ?></p>
                        <p class="text-xs mt-1">หมายเลขพร้อมเพย์: <?php echo $promptpay_number; ?></p>
                        <p class="text-xs text-gray-500 mt-2">สแกน QR Code เพื่อชำระเงิน</p>
                        <div class="mt-2 countdown-timer" id="countdownTimer">
                            หมดอายุใน: <span id="countdown">60</span> วินาที
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <form method="POST">
                            <button type="submit" name="checkout_promptpay"
                                    class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center">
                                <i class="fas fa-check mr-2"></i>ยืนยันการชำระ
                            </button>
                        </form>
                        
                        <form method="POST">
                            <button type="submit" name="cancel_promptpay"
                                    class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="products.php" 
               class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500 hover:shadow-xl transition duration-200">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-boxes text-blue-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">จัดการสินค้า</h3>
                        <p class="text-sm text-gray-600">ดูและจัดการสินค้าทั้งหมด</p>
                    </div>
                </div>
            </a>
            
            <a href="dashboard.php" 
               class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500 hover:shadow-xl transition duration-200">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-chart-bar text-green-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">แดชบอร์ด</h3>
                        <p class="text-sm text-gray-600">ดูรายงานและสถิติการขาย</p>
                    </div>
                </div>
            </a>
            
            <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-shopping-bag text-purple-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">ขายวันนี้</h3>
                        <p class="text-sm text-gray-600">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as sales_total 
                                                 FROM sales 
                                                 WHERE DATE(created_at) = CURDATE()");
                            $stmt->execute();
                            $today_sales = $stmt->fetch();
                            ?>
                            <?php echo $today_sales['sales_count']; ?> รายการ (฿<?php echo number_format($today_sales['sales_total'], 2); ?>)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Product Search and Filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const productCards = document.querySelectorAll('.product-card');
        const barcodeInput = document.getElementById('barcodeInput');
        const barcodeForm = document.getElementById('barcodeForm');
        const clearCartBtn = document.getElementById('clearCartBtn');
        
        function filterProducts() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            
            productCards.forEach(card => {
                const productName = card.querySelector('h3').textContent.toLowerCase();
                const productCategory = card.dataset.category;
                
                const matchesSearch = productName.includes(searchTerm);
                const matchesCategory = !selectedCategory || productCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        searchInput.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
        
        // Auto-focus barcode input
        barcodeInput.focus();
        
        // Barcode scanner - Auto submit when Enter is pressed - แก้ไขใหม่
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    // ส่งฟอร์มโดยตรง
                    barcodeForm.submit();
                }
            }
        });
        
        // Auto submit product forms when Enter is pressed in quantity input
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const form = this.closest('.product-form');
                    if (form && this.value > 0) {
                        form.submit();
                    }
                }
            });
        });
        
        // Clear cart with SweetAlert
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                Swal.fire({
                    title: 'ยืนยันการล้างตะกร้า?',
                    text: "คุณจะไม่สามารถกู้คืนข้อมูลได้!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ล้างตะกร้า!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        }
        
        // Remove item from cart with SweetAlert
        document.querySelectorAll('.remove-item-btn').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                Swal.fire({
                    title: 'ยืนยันการลบสินค้า?',
                    text: "คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ลบออก!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });
        
        // Quick quantity buttons
        document.querySelectorAll('.product-form').forEach(form => {
            const input = form.querySelector('.quantity-input');
            const quickButtons = document.createElement('div');
            quickButtons.className = 'flex space-x-1 mt-2';
            
            [1, 5, 10].forEach(qty => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-2 rounded text-xs transition duration-200';
                button.textContent = qty;
                button.onclick = () => {
                    input.value = qty;
                    setTimeout(() => form.submit(), 100);
                };
                quickButtons.appendChild(button);
            });
            
            form.appendChild(quickButtons);
        });

        // Add scanning animation to barcode input when focused
        barcodeInput.addEventListener('focus', function() {
            this.classList.add('scanning');
        });
        
        barcodeInput.addEventListener('blur', function() {
            this.classList.remove('scanning');
        });
        
        // Auto-select all text when focused
        barcodeInput.addEventListener('click', function() {
            this.select();
        });

        // ป้องกันการส่งฟอร์มซ้ำเมื่อกด Enter ซ้ำๆ
        let isSubmitting = false;
        barcodeForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            setTimeout(() => { isSubmitting = false; }, 1000);
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + F to focus search
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.getElementById('productSearch').focus();
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            document.getElementById('productSearch').value = '';
            document.getElementById('categoryFilter').value = '';
            const event = new Event('input');
            document.getElementById('productSearch').dispatchEvent(event);
        }
        
        // F2 to focus barcode input
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('barcodeInput').focus();
        }
        
        // F3 to clear cart
        if (e.key === 'F3' && document.getElementById('clearCartBtn')) {
            e.preventDefault();
            document.getElementById('clearCartBtn').click();
        }
    });

    // Countdown timer for QR Code
    <?php if (isset($_SESSION['qr_expire_time'])): ?>
    function startCountdown() {
        const expireTime = <?php echo $_SESSION['qr_expire_time']; ?> * 1000; // Convert to milliseconds
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expireTime - now;
            
            if (distance < 0) {
                countdownElement.textContent = '0';
                // Auto refresh when expired
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                return;
            }
            
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            countdownElement.textContent = seconds;
        }
        
        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);
    }
    
    startCountdown();
    <?php endif; ?>

    // ตั้งค่า PDFMake สำหรับรองรับภาษาไทย - แก้ไขใหม่
    pdfMake.fonts = {
        Roboto: {
            normal: 'Roboto-Regular.ttf',
            bold: 'Roboto-Medium.ttf',
            italics: 'Roboto-Italic.ttf',
            bolditalics: 'Roboto-MediumItalic.ttf'
        },
        'NotoSansThai': {
            normal: 'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/unhinted/ttf/NotoSansThai/NotoSansThai-Regular.ttf',
            bold: 'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/unhinted/ttf/NotoSansThai/NotoSansThai-Bold.ttf',
            italics: 'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/unhinted/ttf/NotoSansThai/NotoSansThai-Regular.ttf',
            bolditalics: 'https://cdn.jsdelivr.net/gh/googlefonts/noto-fonts@main/unhinted/ttf/NotoSansThai/NotoSansThai-Bold.ttf'
        }
    };

    // Generate PDF Receipt - แก้ไขให้รองรับภาษาไทยและใช้งานง่าย
    function generateReceipt() {
        const saleId = <?php echo isset($_SESSION['last_sale_id']) ? $_SESSION['last_sale_id'] : 'null'; ?>;
        const saleAmount = <?php echo isset($_SESSION['last_sale_amount']) ? $_SESSION['last_sale_amount'] : '0'; ?>;
        const saleItems = <?php echo isset($_SESSION['last_sale_items']) ? json_encode($_SESSION['last_sale_items']) : '[]'; ?>;
        const paymentMethod = '<?php echo (isset($_SESSION['last_sale_items']) && isset($_POST['checkout_promptpay'])) ? 'พร้อมเพย์' : 'เงินสด'; ?>';
        
        if (!saleId) {
            Swal.fire('ผิดพลาด', 'ไม่พบข้อมูลการขาย', 'error');
            return null;
        }

        // ตรวจสอบและแปลง saleItems ให้เป็น array ที่ปลอดภัย
        let itemsArray = [];
        if (saleItems && Array.isArray(saleItems)) {
            itemsArray = saleItems;
        } else if (saleItems && typeof saleItems === 'object') {
            // ถ้าเป็น object ให้แปลงเป็น array
            itemsArray = Object.values(saleItems);
        }

        const docDefinition = {
            pageSize: 'A7',
            pageMargins: [10, 10, 10, 10],
            defaultStyle: {
                font: 'Roboto'
            },
            content: [
                {
                    text: 'ใบเสร็จรับเงิน',
                    style: 'header',
                    alignment: 'center',
                    margin: [0, 0, 0, 10]
                },
                {
                    text: 'ร้านค้า',
                    style: 'subheader',
                    margin: [0, 0, 0, 5]
                },
                {
                    text: `เลขที่: ${saleId}`,
                    style: 'subheader',
                    margin: [0, 0, 0, 5]
                },
                {
                    text: `วันที่: ${new Date().toLocaleDateString('th-TH')} ${new Date().toLocaleTimeString('th-TH')}`,
                    style: 'subheader',
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: ['*', 'auto', 'auto'],
                        body: [
                            [
                                { text: 'สินค้า', style: 'tableHeader' },
                                { text: 'จำนวน', style: 'tableHeader' },
                                { text: 'ราคา', style: 'tableHeader' }
                            ],
                            ...itemsArray.map(item => [
                                { text: item.name || 'สินค้า', style: 'tableBody', fontSize: 7 },
                                { text: item.quantity || 1, style: 'tableBody', alignment: 'center', fontSize: 7 },
                                { text: `฿${((item.price || 0) * (item.quantity || 1)).toFixed(2)}`, style: 'tableBody', alignment: 'right', fontSize: 7 }
                            ])
                        ]
                    },
                    layout: {
                        hLineWidth: function(i, node) { return 0.5; },
                        vLineWidth: function(i, node) { return 0.5; },
                        hLineColor: function(i, node) { return '#aaaaaa'; },
                        vLineColor: function(i, node) { return '#aaaaaa'; },
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ['*', 'auto'],
                        body: [
                            [
                                { text: 'รวม', style: 'tableFooter' },
                                { text: `฿${(saleAmount || 0).toFixed(2)}`, style: 'tableFooter', alignment: 'right' }
                            ]
                        ]
                    },
                    layout: 'noBorders',
                    margin: [0, 0, 0, 10]
                },
                {
                    text: `ชำระโดย: ${paymentMethod}`,
                    style: 'footer',
                    margin: [0, 0, 0, 5]
                },
                {
                    text: 'ขอบคุณที่ใช้บริการ',
                    style: 'footer',
                    alignment: 'center',
                    margin: [0, 10, 0, 0]
                }
            ],
            styles: {
                header: {
                    fontSize: 14,
                    bold: true
                },
                subheader: {
                    fontSize: 9
                },
                tableHeader: {
                    fontSize: 8,
                    bold: true
                },
                tableBody: {
                    fontSize: 7
                },
                tableFooter: {
                    fontSize: 9,
                    bold: true
                },
                footer: {
                    fontSize: 8
                }
            }
        };

        return docDefinition;
    }

    // Print Receipt - แก้ไขฟังก์ชันพิมพ์
    function printReceipt() {
        try {
            const docDefinition = generateReceipt();
            if (docDefinition) {
                pdfMake.createPdf(docDefinition).print();
            } else {
                Swal.fire('ผิดพลาด', 'ไม่สามารถสร้างใบเสร็จได้', 'error');
            }
        } catch (error) {
            console.error('Error printing receipt:', error);
            Swal.fire('ผิดพลาด', 'ไม่สามารถพิมพ์ใบเสร็จได้: ' + error.message, 'error');
        }
    }

    // Download Receipt - แก้ไขฟังก์ชันดาวน์โหลด
    function downloadReceipt() {
        try {
            const docDefinition = generateReceipt();
            if (docDefinition) {
                pdfMake.createPdf(docDefinition).download(`receipt_${<?php echo isset($_SESSION['last_sale_id']) ? $_SESSION['last_sale_id'] : '0'; ?>}.pdf`);
            } else {
                Swal.fire('ผิดพลาด', 'ไม่สามารถดาวน์โหลดใบเสร็จได้', 'error');
            }
        } catch (error) {
            console.error('Error downloading receipt:', error);
            Swal.fire('ผิดพลาด', 'ไม่สามารถดาวน์โหลดใบเสร็จได้: ' + error.message, 'error');
        }
    }

    // Show success message for sales - แก้ไขใหม่
    <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_SESSION['last_sale_id'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            Swal.fire({
                title: 'ขายสำเร็จ!',
                html: `
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-3 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-xl text-green-600"></i>
                        </div>
                        <p class="text-lg font-semibold text-gray-800 mb-1">รายการขาย #<?php echo $_SESSION['last_sale_id']; ?></p>
                        <p class="text-green-600 font-bold text-xl mb-3">฿<?php echo number_format($_SESSION['last_sale_amount'], 2); ?></p>
                        <p class="text-gray-500 text-sm">ต้องการพิมพ์ใบเสร็จหรือไม่?</p>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'พิมพ์ใบเสร็จ',
                cancelButtonText: 'ปิด',
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                reverseButtons: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    printReceipt();
                }
                // ลบ session หลังจากแสดงผลเสร็จ
                fetch('clear_sale_session.php')
                    .then(response => response.json())
                    .then(data => {
                        console.log('Session cleared');
                    })
                    .catch(error => {
                        console.error('Error clearing session:', error);
                    });
            });
        }, 500);
    });
    <?php endif; ?>

    // Show messages from PHP
    <?php if (isset($_SESSION['message'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($_SESSION['message']['type'] === 'error'): ?>
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: '<?php echo $_SESSION['message']['text']; ?>',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        <?php elseif ($_SESSION['message']['type'] === 'success'): ?>
        Swal.fire({
            title: 'สำเร็จ',
            text: '<?php echo $_SESSION['message']['text']; ?>',
            icon: 'success',
            confirmButtonText: 'ตกลง',
            timer: 2000,
            timerProgressBar: true
        });
        <?php endif; ?>
        <?php unset($_SESSION['message']); ?>
    });
    <?php endif; ?>
    </script>
</body>
</html>