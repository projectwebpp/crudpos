<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$page_title = 'แดชบอร์ด';

// ดึงข้อมูลสถิติ
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM categories) as total_categories,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT SUM(quantity) FROM products) as total_quantity
");
$stmt->execute();
$stats = $stmt->fetch();

// ดึงข้อมูลหมวดหมู่และจำนวนสินค้า
$stmt = $pdo->prepare("
    SELECT c.name, c.color, c.icon, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY product_count DESC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// ดึงสินค้าล่าสุด
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, u.full_name as creator 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.created_by = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_products = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<!-- Welcome Section -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-lg p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">สวัสดี, <?php echo $_SESSION['full_name']; ?>! 👋</h1>
            <p class="text-blue-100 text-lg">ยินดีต้อนรับสู่ระบบจัดการสินค้า ครบเครื่องเรื่องไอที</p>
        </div>
        <div class="bg-white bg-opacity-20 p-4 rounded-xl">
            <i class="fas fa-calendar-day text-3xl"></i>
            <p class="mt-2 font-semibold"><?php echo date('d/m/Y'); ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stat Card 1 -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">สินค้าทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_products']; ?></p>
                <p class="text-green-600 text-sm mt-1">
                    <i class="fas fa-arrow-up mr-1"></i>ทั้งหมด
                </p>
            </div>
            <div class="bg-blue-100 p-4 rounded-full">
                <i class="fas fa-boxes text-blue-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card 2 -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">หมวดหมู่</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_categories']; ?></p>
                <p class="text-green-600 text-sm mt-1">
                    <i class="fas fa-layer-group mr-1"></i>กลุ่มสินค้า
                </p>
            </div>
            <div class="bg-green-100 p-4 rounded-full">
                <i class="fas fa-folder text-green-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card 3 -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ผู้ใช้งาน</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_users']; ?></p>
                <p class="text-blue-600 text-sm mt-1">
                    <i class="fas fa-users mr-1"></i>ในระบบ
                </p>
            </div>
            <div class="bg-purple-100 p-4 rounded-full">
                <i class="fas fa-user-friends text-purple-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card 4 -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500 hover:shadow-xl transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">จำนวนในสต็อก</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_quantity'] ? number_format($stats['total_quantity']) : '0'; ?></p>
                <p class="text-orange-600 text-sm mt-1">
                    <i class="fas fa-warehouse mr-1"></i>ชิ้น
                </p>
            </div>
            <div class="bg-orange-100 p-4 rounded-full">
                <i class="fas fa-pallet text-orange-500 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Categories Summary -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-folder-tree mr-2 text-blue-500"></i>
                หมวดหมู่สินค้า
            </h3>
            <a href="categories.php" class="text-blue-500 hover:text-blue-700 text-sm font-semibold transition duration-200">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="space-y-4">
            <?php if (empty($categories)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                    <p>ยังไม่มีหมวดหมู่สินค้า</p>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border border-gray-100 hover:border-blue-200 transition duration-200">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" 
                             style="background-color: <?php echo $category['color']; ?>20;">
                            <i class="<?php echo $category['icon']; ?> text-lg" 
                               style="color: <?php echo $category['color']; ?>"></i>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-800"><?php echo $category['name']; ?></span>
                            <p class="text-gray-500 text-sm"><?php echo $category['product_count']; ?> สินค้า</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo $category['product_count']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Products -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-clock mr-2 text-green-500"></i>
                สินค้าล่าสุด
            </h3>
            <a href="products.php" class="text-blue-500 hover:text-blue-700 text-sm font-semibold transition duration-200">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="space-y-4">
            <?php if (empty($recent_products)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p>ยังไม่มีสินค้า</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_products as $product): ?>
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border border-gray-100 hover:border-green-200 transition duration-200 group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-blue-100 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition duration-200">
                            <i class="fas fa-box text-green-500"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 group-hover:text-green-600 transition duration-200">
                                <?php echo $product['name']; ?>
                            </p>
                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                <span class="bg-gray-100 px-2 py-1 rounded"><?php echo $product['category_name']; ?></span>
                                <span>โดย <?php echo $product['creator']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800 text-lg">฿<?php echo number_format($product['price'], 2); ?></p>
                        <p class="text-sm text-gray-600">คงเหลือ: <span class="font-semibold"><?php echo $product['quantity']; ?></span> ชิ้น</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-6">
        <i class="fas fa-bolt mr-2 text-yellow-500"></i>
        การดำเนินการด่วน
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="products.php?action=create" 
           class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white p-4 rounded-xl text-center transition duration-200 transform hover:scale-105">
            <i class="fas fa-plus-circle text-2xl mb-2"></i>
            <p class="font-semibold">เพิ่มสินค้าใหม่</p>
        </a>
        
        <a href="categories.php" 
           class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white p-4 rounded-xl text-center transition duration-200 transform hover:scale-105">
            <i class="fas fa-folder-plus text-2xl mb-2"></i>
            <p class="font-semibold">จัดการหมวดหมู่</p>
        </a>
        
        <a href="profile.php" 
           class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white p-4 rounded-xl text-center transition duration-200 transform hover:scale-105">
            <i class="fas fa-user-cog text-2xl mb-2"></i>
            <p class="font-semibold">ตั้งค่าโปรไฟล์</p>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>