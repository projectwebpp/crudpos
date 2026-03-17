<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$page_title = 'รายงานการขาย';

// ดึงข้อมูลรายงานการขาย
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as cashier
    FROM sales s 
    LEFT JOIN users u ON s.created_by = u.id 
    ORDER BY s.created_at DESC
    LIMIT 50
");
$stmt->execute();
$sales = $stmt->fetchAll();

// สถิติการขาย
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        AVG(total_amount) as avg_sale
    FROM sales
");
$stmt->execute();
$stats = $stmt->fetch();
?>

<?php include '../includes/header.php'; ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-chart-bar mr-3 text-green-500"></i>
        รายงานการขาย
    </h2>
    <div class="flex space-x-2">
        <a href="sale.php" 
           class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
            <i class="fas fa-cash-register mr-2"></i>ขายสินค้า
        </a>
    </div>
</div>

<!-- Sales Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">รายการขายทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_sales']; ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-receipt text-blue-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">รายได้ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800">฿<?php echo number_format($stats['total_revenue'], 2); ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-money-bill-wave text-green-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ยอดขายเฉลี่ย</p>
                <p class="text-2xl font-bold text-gray-800">฿<?php echo number_format($stats['avg_sale'], 2); ?></p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-chart-line text-purple-500"></i>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขที่รายการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">พนักงานขาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่ขาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg mb-2">ไม่พบรายการขาย</p>
                            <p class="text-sm text-gray-400">เริ่มต้นการขายที่หน้า POS</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">#<?php echo $sale['id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo escape($sale['cashier']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-green-600">฿<?php echo number_format($sale['total_amount'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="sale_detail.php?id=<?php echo $sale['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 mr-3 transition duration-200">
                                <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>