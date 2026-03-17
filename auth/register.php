<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    redirect('../dashboard.php');
}

$page_title = 'สมัครสมาชิก';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    // ตรวจสอบรหัสผ่าน
    if ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว';
        } else {
            // สร้างผู้ใช้ใหม่
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone])) {
                $success = 'สมัครสมาชิกสำเร็จ! <a href="login.php" class="text-blue-500 hover:text-blue-700 font-semibold">เข้าสู่ระบบ</a>';
            } else {
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
            }
        }
    }
}
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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-laptop-code text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">ครบเครื่องเรื่องไอที</h1>
            <p class="text-blue-200 mt-2">ระบบจัดการสินค้า</p>
        </div>

        <!-- Register Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">สมัครสมาชิก</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">
                        <i class="fas fa-user mr-2 text-blue-500"></i>ชื่อ-นามสกุล
                    </label>
                    <input type="text" id="full_name" name="full_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกชื่อ-นามสกุลของคุณ"
                        value="<?php echo isset($_POST['full_name']) ? escape($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        <i class="fas fa-user-tag mr-2 text-blue-500"></i>ชื่อผู้ใช้
                    </label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกชื่อผู้ใช้ของคุณ"
                        value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        <i class="fas fa-envelope mr-2 text-blue-500"></i>อีเมล
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกอีเมลของคุณ"
                        value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                        <i class="fas fa-phone mr-2 text-blue-500"></i>เบอร์โทรศัพท์
                    </label>
                    <input type="tel" id="phone" name="phone"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกเบอร์โทรศัพท์"
                        value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2 text-blue-500"></i>รหัสผ่าน
                    </label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                        <i class="fas fa-lock mr-2 text-blue-500"></i>ยืนยันรหัสผ่าน
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกรหัสผ่านอีกครั้ง">
                </div>
                
                <button type="submit"
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                    <i class="fas fa-user-plus mr-2"></i>สมัครสมาชิก
                </button>
            </form>
            
            <div class="text-center mt-6">
                <p class="text-gray-600">มีบัญชีอยู่แล้ว? 
                    <a href="login.php" class="text-blue-500 hover:text-blue-700 font-semibold">
                        เข้าสู่ระบบ
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>