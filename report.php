<?php
// report.php - รายงานการขาย
ob_start();

require_once 'config/database.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$page_title = 'รายงานการขาย';

// กำหนดช่วงวันที่
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ดึงข้อมูลการขายรายวัน
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as sales_count,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_sales
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY sale_date DESC
");
$stmt->execute([$start_date, $end_date]);
$daily_sales = $stmt->fetchAll();

// ดึงข้อมูลการขายตามวิธีการชำระเงิน
$stmt = $pdo->prepare("
    SELECT 
        payment_method,
        COUNT(*) as sales_count,
        SUM(total_amount) as total_sales
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
");
$stmt->execute([$start_date, $end_date]);
$payment_methods = $stmt->fetchAll();

// ดึงข้อมูลสินค้าขายดี
$stmt = $pdo->prepare("
    SELECT 
        p.name as product_name,
        SUM(si.quantity) as total_quantity,
        SUM(si.total_price) as total_revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_quantity DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();

// สร้างข้อมูลสำหรับกราฟ
$chart_labels = [];
$chart_sales = [];
$chart_orders = [];

foreach ($daily_sales as $sale) {
    $chart_labels[] = date('d/m', strtotime($sale['sale_date']));
    $chart_sales[] = floatval($sale['total_sales']);
    $chart_orders[] = intval($sale['sales_count']);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <i class="fas fa-chart-bar mr-2 text-green-500"></i>
                        รายงานการขาย
                    </h1>
                    <nav class="hidden md:flex space-x-4">
                        <a href="sale.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-cash-register mr-1"></i>ขายสินค้า
                        </a>
                        <a href="products.php" class="text-gray-600 hover:text-gray-900 transition duration-200">
                            <i class="fas fa-boxes mr-1"></i>จัดการสินค้า
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
        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">กรองข้อมูล</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition duration-200">
                        <i class="fas fa-filter mr-2"></i>กรองข้อมูล
                    </button>
                </div>
                <div class="flex items-end">
                    <a href="report.php" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition duration-200">
                        <i class="fas fa-refresh mr-2"></i>รีเซ็ต
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-shopping-cart text-blue-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">ยอดขายรวม</h3>
                        <p class="text-2xl font-bold text-gray-800">
                            ฿<?php 
                            $total_sales = array_sum(array_column($daily_sales, 'total_sales'));
                            echo number_format($total_sales, 2);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-receipt text-green-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">จำนวนรายการ</h3>
                        <p class="text-2xl font-bold text-gray-800">
                            <?php 
                            $total_orders = array_sum(array_column($daily_sales, 'sales_count'));
                            echo number_format($total_orders);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">ยอดขายเฉลี่ย</h3>
                        <p class="text-2xl font-bold text-gray-800">
                            ฿<?php 
                            $avg_sales = $total_orders > 0 ? $total_sales / $total_orders : 0;
                            echo number_format($avg_sales, 2);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="bg-orange-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-calendar text-orange-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">จำนวนวัน</h3>
                        <p class="text-2xl font-bold text-gray-800">
                            <?php echo count($daily_sales); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Sales Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">ยอดขายรายวัน</h2>
                <canvas id="salesChart" height="300"></canvas>
            </div>
            
            <!-- Payment Methods Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">วิธีการชำระเงิน</h2>
                <canvas id="paymentChart" height="300"></canvas>
            </div>
        </div>

        <!-- Daily Sales Table -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">การขายรายวัน</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">วันที่</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">จำนวนรายการ</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">ยอดขาย</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">ยอดขายเฉลี่ย</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($daily_sales)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                                    <p>ไม่พบข้อมูลการขาย</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daily_sales as $sale): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    <?php echo number_format($sale['sales_count']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-green-600 font-semibold text-right">
                                    ฿<?php echo number_format($sale['total_sales'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-blue-600 font-semibold text-right">
                                    ฿<?php echo number_format($sale['avg_sales'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">สินค้าขายดี 10 อันดับแรก</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">อันดับ</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ชื่อสินค้า</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">จำนวนที่ขาย</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">ยอดขาย</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                                    <p>ไม่พบข้อมูลสินค้าขายดี</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $index => $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    <?php echo number_format($product['total_quantity']); ?> ชิ้น
                                </td>
                                <td class="px-4 py-3 text-sm text-green-600 font-semibold text-right">
                                    ฿<?php echo number_format($product['total_revenue'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo json_encode($chart_sales); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'จำนวนรายการ',
                    data: <?php echo json_encode($chart_orders); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'ยอดขาย (บาท)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'จำนวนรายการ'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Payment Methods Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    const paymentChart = new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($payment_methods, 'total_sales')); ?>,
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(59, 130, 246, 0.8)'
                ],
                borderColor: [
                    'rgba(34, 197, 94, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(59, 130, 246, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ฿${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>