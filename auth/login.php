<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    redirect('../dashboard.php');
}

$page_title = 'เข้าสู่ระบบ';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // ตั้งค่า session ทั้งหมด
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role']; // ตั้งค่า role
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['avatar'] = $user['avatar'];
        
        setMessage('success', 'เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ ' . $user['full_name']);
        redirect('../dashboard.php');
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
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

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">เข้าสู่ระบบ</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        <i class="fas fa-user mr-2 text-blue-500"></i>ชื่อผู้ใช้
                    </label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกชื่อผู้ใช้ของคุณ"
                        value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2 text-blue-500"></i>รหัสผ่าน
                    </label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                        placeholder="กรอกรหัสผ่านของคุณ">
                </div>
                
                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="text-center mt-6">
                <p class="text-gray-600">ยังไม่มีบัญชี? 
                    <a href="register.php" class="text-blue-500 hover:text-blue-700 font-semibold">
                        สมัครสมาชิก
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Demo Accounts -->
        <div class="mt-8 bg-white bg-opacity-20 rounded-lg p-4 text-white text-sm">
            <p class="font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>บัญชีทดสอบ:</p>
            <p>ผู้ดูแล: admin / password</p>
            <p>ผู้ใช้ทั่วไป: user1 / password</p>
        </div>
    </div>
</body>
</html>