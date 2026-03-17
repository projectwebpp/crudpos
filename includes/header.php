<?php
if (!isset($page_title)) {
    $page_title = 'ระบบจัดการสินค้า';
}

// ตรวจสอบว่า user ล็อกอินแล้วและมี session role หรือไม่
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$user_full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'ผู้ใช้';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ครบเครื่องเรื่องไอที</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <i class="fas fa-laptop-code text-2xl"></i>
                    <div>
                        <h1 class="text-xl font-bold">ครบเครื่องเรื่องไอที</h1>
                        <p class="text-blue-200 text-sm">ระบบจัดการสินค้า</p>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="font-semibold"><?php echo $user_full_name; ?></p>
                        <p class="text-blue-200 text-sm">
                            <?php echo $user_role == 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้'; ?>
                        </p>
                    </div>
                    <div class="relative group">
                        <button class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center hover:bg-blue-400 transition duration-200">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 hidden group-hover:block z-50 border border-gray-200">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50 transition duration-150">
                                <i class="fas fa-user-edit mr-2 text-blue-500"></i>แก้ไขโปรไฟล์
                            </a>
                            <a href="auth/logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50 transition duration-150">
                                <i class="fas fa-sign-out-alt mr-2 text-red-500"></i>ออกจากระบบ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg hidden md:block">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                            <i class="fas fa-tachometer-alt w-6 text-center"></i>
                            <span>แดชบอร์ด</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                            <i class="fas fa-folder w-6 text-center"></i>
                            <span>หมวดหมู่</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                            <i class="fas fa-box w-6 text-center"></i>
                            <span>สินค้า</span>
                        </a>
                    </li>
                    <li>
                    <a href="sale.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'sale.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                        <i class="fas fa-cash-register w-6 text-center"></i>
                        <span>ขายสินค้า</span>
                    </a>
                </li>
                    <?php if ($user_role == 'admin'): ?>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                            <i class="fas fa-users w-6 text-center"></i>
                            <span>ผู้ใช้งาน</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="profile.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-50 text-blue-600 border-r-2 border-blue-600' : ''; ?>">
                            <i class="fas fa-cog w-6 text-center"></i>
                            <span>ตั้งค่า</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Content Area -->
        <main class="flex-1 p-4 md:p-6 min-h-screen">
            <!-- Display Messages -->
            <?php showMessage(); ?>