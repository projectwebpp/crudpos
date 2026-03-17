<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$page_title = 'จัดการสินค้า';

// ตัวแปรสำหรับการกรองและค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// สร้างเงื่อนไข WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// สร้างเงื่อนไข ORDER BY
$order_sql = '';
switch ($sort) {
    case 'name_asc':
        $order_sql = 'ORDER BY p.name ASC';
        break;
    case 'name_desc':
        $order_sql = 'ORDER BY p.name DESC';
        break;
    case 'price_low':
        $order_sql = 'ORDER BY p.price ASC';
        break;
    case 'price_high':
        $order_sql = 'ORDER BY p.price DESC';
        break;
    case 'quantity_low':
        $order_sql = 'ORDER BY p.quantity ASC';
        break;
    case 'quantity_high':
        $order_sql = 'ORDER BY p.quantity DESC';
        break;
    case 'barcode_asc':
        $order_sql = 'ORDER BY p.barcode ASC';
        break;
    default:
        $order_sql = 'ORDER BY p.created_at DESC';
}

// ดึงข้อมูลสินค้า
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.color as category_color, u.full_name as creator 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.created_by = u.id 
    $where_sql 
    $order_sql
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// ดึงข้อมูลหมวดหมู่สำหรับ dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// นับจำนวนสินค้าทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where_sql");
$stmt->execute($params);
$total_products = $stmt->fetchColumn();

// นับสินค้าตามสถานะสต็อก
$low_stock = 0;
$out_of_stock = 0;
$in_stock = 0;

foreach ($products as $product) {
    if ($product['quantity'] == 0) {
        $out_of_stock++;
    } elseif ($product['quantity'] <= 10) {
        $low_stock++;
    } else {
        $in_stock++;
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Noto Sans Thai', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-boxes mr-2 text-blue-500"></i>
                        ระบบจัดการสินค้า
                    </h1>
                    <nav class="hidden md:flex space-x-4">
                        <a href="sale.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-cash-register mr-1"></i>ขายสินค้า
                        </a>
                        <a href="report.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-chart-bar mr-1"></i>รายงานการขาย
                        </a>
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-tachometer-alt mr-1"></i>แดชบอร์ด
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
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-boxes mr-3 text-blue-500"></i>
                จัดการสินค้า
            </h2>
            <button onclick="openProductModal()" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i>เพิ่มสินค้าใหม่
            </button>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="search">
                        <i class="fas fa-search mr-2 text-blue-500"></i>ค้นหาสินค้า
                    </label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="ค้นหาชื่อ, คำอธิบาย, หรือบาร์โค้ด...">
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                        <i class="fas fa-filter mr-2 text-green-500"></i>หมวดหมู่
                    </label>
                    <select id="category" name="category" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sort">
                        <i class="fas fa-sort mr-2 text-purple-500"></i>เรียงตาม
                    </label>
                    <select id="sort" name="sort" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>ชื่อ A-Z</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>ชื่อ Z-A</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>ราคาต่ำ-สูง</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>ราคาสูง-ต่ำ</option>
                        <option value="quantity_low" <?php echo $sort == 'quantity_low' ? 'selected' : ''; ?>>จำนวนน้อย-มาก</option>
                        <option value="quantity_high" <?php echo $sort == 'quantity_high' ? 'selected' : ''; ?>>จำนวนมาก-น้อย</option>
                        <option value="barcode_asc" <?php echo $sort == 'barcode_asc' ? 'selected' : ''; ?>>บาร์โค้ด A-Z</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center flex-1 justify-center">
                        <i class="fas fa-filter mr-2"></i>กรอง
                    </button>
                    <a href="products.php" 
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-refresh mr-2"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">สินค้าทั้งหมด</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_products; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-box text-blue-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">สินค้าพร้อมขาย</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $in_stock; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check text-green-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">สต็อกต่ำ</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $low_stock; ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-orange-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">สินค้าหมด</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $out_of_stock; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times text-red-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สินค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บาร์โค้ด</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมวดหมู่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ราคา</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้สร้าง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สร้าง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg mb-2">ไม่พบสินค้า</p>
                                    <p class="text-sm text-gray-400">ลองเปลี่ยนเงื่อนไขการค้นหาหรือเพิ่มสินค้าใหม่</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-blue-500"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($product['description']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($product['barcode'])): ?>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded border">
                                                <?php echo $product['barcode']; ?>
                                            </span>
                                            <button onclick="copyBarcode('<?php echo $product['barcode']; ?>')" 
                                                    class="text-blue-600 hover:text-blue-800 transition duration-200"
                                                    title="คัดลอกบาร์โค้ด">
                                                <i class="fas fa-copy text-xs"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">ไม่มีบาร์โค้ด</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($product['category_name']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                              style="background-color: <?php echo $product['category_color']; ?>20; color: <?php echo $product['category_color']; ?>">
                                            <i class="fas fa-tag mr-1"></i>
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">ไม่มีหมวดหมู่</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">฿<?php echo number_format($product['price'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $quantity_class = 'bg-green-100 text-green-800';
                                    $quantity_icon = 'fa-check';
                                    
                                    if ($product['quantity'] == 0) {
                                        $quantity_class = 'bg-red-100 text-red-800';
                                        $quantity_icon = 'fa-times';
                                    } elseif ($product['quantity'] <= 10) {
                                        $quantity_class = 'bg-orange-100 text-orange-800';
                                        $quantity_icon = 'fa-exclamation-triangle';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $quantity_class; ?>">
                                        <i class="fas <?php echo $quantity_icon; ?> mr-1"></i>
                                        <?php echo number_format($product['quantity']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($product['creator']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($product['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editProduct(<?php echo $product['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3 transition duration-200">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900 transition duration-200">
                                        <i class="fas fa-trash mr-1"></i>ลบ
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Product Modal -->
        <div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
                <div class="mt-3">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">เพิ่มสินค้าใหม่</h3>
                    
                    <form id="productForm" method="POST" action="actions/product_action.php">
                        <input type="hidden" id="productId" name="id">
                        <input type="hidden" name="action" id="formAction" value="create">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                    <i class="fas fa-tag mr-2 text-blue-500"></i>ชื่อสินค้า *
                                </label>
                                <input type="text" id="name" name="name" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                       placeholder="กรอกชื่อสินค้า">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="barcode">
                                    <i class="fas fa-barcode mr-2 text-purple-500"></i>บาร์โค้ด
                                </label>
                                <input type="text" id="barcode" name="barcode"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                       placeholder="กรอกบาร์โค้ดสินค้า">
                                <p class="text-xs text-gray-500 mt-1">เว้นว่างไว้เพื่อสร้างบาร์โค้ดอัตโนมัติ</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="category_id">
                                    <i class="fas fa-folder mr-2 text-green-500"></i>หมวดหมู่
                                </label>
                                <select id="category_id" name="category_id" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="">เลือกหมวดหมู่</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                                    <i class="fas fa-money-bill-wave mr-2 text-yellow-500"></i>ราคา (บาท) *
                                </label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                <i class="fas fa-align-left mr-2 text-purple-500"></i>คำอธิบาย
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                      placeholder="รายละเอียดเกี่ยวกับสินค้า..."></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                                    <i class="fas fa-boxes mr-2 text-orange-500"></i>จำนวนในสต็อก *
                                </label>
                                <input type="number" id="quantity" name="quantity" min="0" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                       placeholder="0">
                            </div>
                            
                            <div class="flex items-end">
                                <button type="button" onclick="generateBarcode()" 
                                        class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center w-full">
                                    <i class="fas fa-barcode mr-2"></i>สร้างบาร์โค้ดอัตโนมัติ
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="closeProductModal()"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    // ฟังก์ชันเปิด Modal เพิ่มสินค้า
    function openProductModal() {
        document.getElementById('productModal').classList.remove('hidden');
        document.getElementById('modalTitle').textContent = 'เพิ่มสินค้าใหม่';
        document.getElementById('formAction').value = 'create';
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        document.getElementById('category_id').value = '';
    }

    // ฟังก์ชันปิด Modal
    function closeProductModal() {
        document.getElementById('productModal').classList.add('hidden');
    }

    // ฟังก์ชันแก้ไขสินค้า
    function editProduct(id) {
        loadProductData(id);
    }

    // ฟังก์ชันโหลดข้อมูลสินค้า
    function loadProductData(productId) {
        // แสดง loading
        const modalTitle = document.getElementById('modalTitle');
        modalTitle.innerHTML = '<div class="flex items-center justify-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลด...</div>';
        
        // ส่ง request ไปยัง API
        fetch(`actions/get_product.php?id=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const product = data.product;
                    
                    // เติมข้อมูลลงใน form
                    document.getElementById('productId').value = product.id;
                    document.getElementById('name').value = product.name;
                    document.getElementById('description').value = product.description || '';
                    document.getElementById('price').value = parseFloat(product.price).toFixed(2);
                    document.getElementById('quantity').value = product.quantity;
                    document.getElementById('category_id').value = product.category_id || '';
                    document.getElementById('barcode').value = product.barcode || '';
                    
                    // เปลี่ยน title
                    modalTitle.textContent = 'แก้ไขสินค้า: ' + product.name;
                    document.getElementById('formAction').value = 'update';
                    
                    // แสดง modal
                    document.getElementById('productModal').classList.remove('hidden');
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: data.message || 'ไม่สามารถโหลดข้อมูลสินค้าได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถโหลดข้อมูลสินค้าได้ กรุณาลองอีกครั้ง',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    // ฟังก์ชันลบสินค้า
    function deleteProduct(id) {
        // หาชื่อสินค้าจากแถวในตาราง
        const productRow = event.target.closest('tr');
        const productName = productRow.querySelector('.text-sm.font-medium').textContent;
        
        Swal.fire({
            title: 'ยืนยันการลบสินค้า',
            html: `
                <div class="text-center py-2">
                    <div class="w-16 h-16 mx-auto mb-3 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-xl text-red-600"></i>
                    </div>
                    <p class="text-lg font-semibold text-gray-800 mb-1">คุณต้องการลบสินค้า</p>
                    <p class="text-red-600 font-bold text-lg">${productName}</p>
                    <p class="text-gray-500 text-sm mt-2">การลบสินค้านี้ไม่สามารถย้อนกลับได้</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ลบสินค้า',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            reverseButtons: true,
            customClass: {
                confirmButton: 'px-4 py-2 rounded-lg font-semibold',
                cancelButton: 'px-4 py-2 rounded-lg font-semibold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // แสดง loading
                Swal.fire({
                    title: 'กำลังลบ...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // ส่งคำขอลบ
                setTimeout(() => {
                    window.location.href = 'actions/product_action.php?action=delete&id=' + id;
                }, 1000);
            }
        });
    }

    // ฟังก์ชันสร้างบาร์โค้ดอัตโนมัติ
    function generateBarcode() {
        // สุ่มบาร์โค้ด 13 หลัก (รูปแบบ EAN-13)
        const prefix = '885'; // รหัสประเทศไทย
        const randomDigits = Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
        const barcode = prefix + randomDigits;
        
        // คำนวณ checksum (ตามมาตรฐาน EAN-13)
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            const digit = parseInt(barcode[i]);
            sum += (i % 2 === 0) ? digit : digit * 3;
        }
        const checksum = (10 - (sum % 10)) % 10;
        const finalBarcode = barcode + checksum;
        
        document.getElementById('barcode').value = finalBarcode;
        
        // แสดงข้อความสำเร็จ
        Swal.fire({
            title: 'สร้างบาร์โค้ดสำเร็จ!',
            text: `บาร์โค้ด: ${finalBarcode}`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // ฟังก์ชันคัดลอกบาร์โค้ด
    function copyBarcode(barcode) {
        navigator.clipboard.writeText(barcode).then(function() {
            Swal.fire({
                title: 'คัดลอกสำเร็จ!',
                text: `คัดลอกบาร์โค้ด: ${barcode}`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            Swal.fire({
                title: 'คัดลอกไม่สำเร็จ!',
                text: 'กรุณาลองอีกครั้ง',
                icon: 'error',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    // ตรวจสอบบาร์โค้ดซ้ำ
    function checkBarcodeDuplicate(barcode, productId = null) {
        if (!barcode) return Promise.resolve(false);
        
        return fetch(`actions/check_barcode.php?barcode=${barcode}&product_id=${productId || ''}`)
            .then(response => response.json())
            .then(data => data.exists)
            .catch(error => {
                console.error('Error checking barcode:', error);
                return false;
            });
    }

    // เพิ่มการตรวจสอบบาร์โค้ดในฟอร์ม
    document.getElementById('productForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const barcode = document.getElementById('barcode').value;
        const productId = document.getElementById('productId').value;
        const action = document.getElementById('formAction').value;
        
        // ตรวจสอบข้อมูลพื้นฐาน
        const price = document.getElementById('price').value;
        const quantity = document.getElementById('quantity').value;
        
        if (parseFloat(price) < 0) {
            Swal.fire({
                title: 'ข้อผิดพลาด!',
                text: 'ราคาต้องเป็นจำนวนบวก',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }
        
        if (parseInt(quantity) < 0) {
            Swal.fire({
                title: 'ข้อผิดพลาด!',
                text: 'จำนวนต้องเป็นจำนวนบวก',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }
        
        // ตรวจสอบบาร์โค้ดซ้ำ (ถ้ามีการกรอกบาร์โค้ด)
        if (barcode) {
            try {
                const isDuplicate = await checkBarcodeDuplicate(barcode, productId);
                
                if (isDuplicate) {
                    Swal.fire({
                        title: 'บาร์โค้ดซ้ำ!',
                        text: 'บาร์โค้ดนี้มีอยู่ในระบบแล้ว กรุณาใช้บาร์โค้ดอื่น',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }
            } catch (error) {
                console.error('Error checking barcode:', error);
                // ยังคงส่งฟอร์มต่อแม้ตรวจสอบบาร์โค้ดไม่สำเร็จ
            }
        }
        
        // ส่งฟอร์ม
        this.submit();
    });

    // Auto-format price input
    document.getElementById('price').addEventListener('blur', function(e) {
        const value = parseFloat(e.target.value);
        if (!isNaN(value)) {
            e.target.value = value.toFixed(2);
        }
    });

    // ปุ่มกด Enter ในช่องบาร์โค้ดให้สร้างบาร์โค้ดอัตโนมัติ
    document.getElementById('barcode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            generateBarcode();
        }
    });

    // ปิด modal เมื่อคลิกนอกพื้นที่
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            closeProductModal();
        }
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape to close modal
        if (e.key === 'Escape' && !document.getElementById('productModal').classList.contains('hidden')) {
            closeProductModal();
        }
        
        // Ctrl + N to add new product
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            openProductModal();
        }
    });
    </script>
</body>
</html>