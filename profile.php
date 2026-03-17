<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$page_title = 'แก้ไขโปรไฟล์';
$error = '';
$success = '';

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    setMessage('error', 'ไม่พบข้อมูลผู้ใช้');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // ตรวจสอบข้อมูลพื้นฐาน
    if (empty($full_name) || empty($email)) {
        $error = 'กรุณากรอกชื่อ-นามสกุลและอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        // ตรวจสอบอีเมลซ้ำ
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'อีเมลนี้มีผู้ใช้งานแล้ว';
        } else {
            // เริ่ม transaction
            $pdo->beginTransaction();
            
            try {
                // อัพเดทข้อมูลพื้นฐาน
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $_SESSION['user_id']]);
                
                // อัพเดทรหัสผ่านถ้ามีการเปลี่ยน
                $password_changed = false;
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        throw new Exception('กรุณากรอกรหัสผ่านปัจจุบัน');
                    }
                    
                    if (!password_verify($current_password, $user['password'])) {
                        throw new Exception('รหัสผ่านปัจจุบันไม่ถูกต้อง');
                    }
                    
                    if ($new_password !== $confirm_password) {
                        throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
                    }
                    
                    if (strlen($new_password) < 6) {
                        throw new Exception('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                    }
                    
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $password_changed = true;
                }
                
                // Commit transaction
                $pdo->commit();
                
                // อัพเดท session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // ดึงข้อมูลใหม่
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($password_changed) {
                    $success = 'อัพเดทโปรไฟล์และเปลี่ยนรหัสผ่านสำเร็จ!';
                } else {
                    $success = 'อัพเดทโปรไฟล์สำเร็จ!';
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">จัดการโปรไฟล์</h1>
                <p class="text-blue-100">อัพเดทข้อมูลส่วนตัวและความปลอดภัยของบัญชีคุณ</p>
            </div>
            <div class="bg-white bg-opacity-20 p-4 rounded-xl">
                <i class="fas fa-user-cog text-3xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800"><?php echo escape($user['full_name']); ?></h2>
                <p class="text-gray-600"><?php echo escape($user['email']); ?></p>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center animate-pulse">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <!-- Personal Information Section -->
            <div class="border-b pb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                    ข้อมูลส่วนตัว
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">
                            <i class="fas fa-user mr-2 text-blue-500"></i>ชื่อ-นามสกุล
                        </label>
                        <input type="text" id="full_name" name="full_name" required
                            value="<?php echo escape($user['full_name']); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="กรอกชื่อ-นามสกุลของคุณ">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                            <i class="fas fa-user-tag mr-2 text-blue-500"></i>ชื่อผู้ใช้
                        </label>
                        <input type="text" id="username" value="<?php echo escape($user['username']); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" 
                            disabled readonly>
                        <p class="text-gray-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>อีเมล
                        </label>
                        <input type="email" id="email" name="email" required
                            value="<?php echo escape($user['email']); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="กรอกอีเมลของคุณ">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            <i class="fas fa-phone mr-2 text-blue-500"></i>เบอร์โทรศัพท์
                        </label>
                        <input type="tel" id="phone" name="phone"
                            value="<?php echo escape($user['phone']); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="กรอกเบอร์โทรศัพท์">
                    </div>
                </div>
            </div>

            <!-- Role Information -->
            <div class="border-b pb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-shield mr-2 text-purple-500"></i>
                    สิทธิ์การใช้งาน
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            บทบาท
                        </label>
                        <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                            <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium 
                                <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200'; ?>">
                                <i class="fas <?php echo $user['role'] == 'admin' ? 'fa-crown' : 'fa-user'; ?> mr-2"></i>
                                <?php echo $user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้ทั่วไป'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            สถานะบัญชี
                        </label>
                        <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                            <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                                <i class="fas fa-check-circle mr-2"></i>
                                เปิดใช้งาน
                            </span>
                        </div>
                    </div>
                </div>
                
                <p class="text-gray-500 text-xs mt-3 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    บทบาทและสถานะบัญชีไม่สามารถเปลี่ยนแปลงได้โดยผู้ใช้
                </p>
            </div>

            <!-- Password Change Section -->
            <div class="border-b pb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-key mr-2 text-yellow-500"></i>
                    เปลี่ยนรหัสผ่าน
                </h3>
                <p class="text-gray-600 text-sm mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">
                            รหัสผ่านปัจจุบัน
                        </label>
                        <input type="password" id="current_password" name="current_password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="กรอกรหัสผ่านปัจจุบัน"
                            autocomplete="current-password">
                        <div id="current-password-feedback" class="mt-1 text-xs"></div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                            รหัสผ่านใหม่
                        </label>
                        <input type="password" id="new_password" name="new_password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="กรอกรหัสผ่านใหม่"
                            autocomplete="new-password">
                        <div id="password-strength" class="mt-1 text-xs"></div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            ยืนยันรหัสผ่านใหม่
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="ยืนยันรหัสผ่านใหม่"
                            autocomplete="new-password">
                        <div id="password-match" class="mt-1 text-xs"></div>
                    </div>
                </div>
                
                <!-- Password Requirements -->
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>
                        ข้อกำหนดรหัสผ่าน:
                    </p>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li class="flex items-center" id="req-length">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            ความยาวอย่างน้อย 6 ตัวอักษร
                        </li>
                        <li class="flex items-center" id="req-complexity">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            ควรประกอบด้วยตัวอักษรและตัวเลข
                        </li>
                        <li class="flex items-center" id="req-special">
                            <i class="fas fa-circle text-xs mr-2"></i>
                            ควรมีอักขระพิเศษ (!@#$%^&*)
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Account Information -->
            <div class="pb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-green-500"></i>
                    ข้อมูลบัญชี
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 flex items-center">
                            <i class="fas fa-calendar-plus mr-2 text-blue-500"></i>
                            วันที่สมัครสมาชิก:
                        </span>
                        <span class="font-semibold text-gray-800">
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 flex items-center">
                            <i class="fas fa-calendar-check mr-2 text-green-500"></i>
                            อัพเดทล่าสุด:
                        </span>
                        <span class="font-semibold text-gray-800">
                            <?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-4 border-t">
                <a href="dashboard.php" 
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center order-2 sm:order-1 transform hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>กลับไปหน้าแดชบอร์ด
                </a>
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 flex items-center justify-center order-1 sm:order-2 transform hover:scale-105 shadow-lg">
                    <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('new_password').addEventListener('input', function(e) {
    const password = e.target.value;
    const indicator = document.getElementById('password-strength');
    const reqLength = document.getElementById('req-length');
    const reqComplexity = document.getElementById('req-complexity');
    const reqSpecial = document.getElementById('req-special');
    
    let strength = 0;
    let feedback = '';
    let color = 'text-gray-400';
    
    // Check password strength
    const hasLength = password.length >= 6;
    const hasUpperLower = password.match(/[a-z]/) && password.match(/[A-Z]/);
    const hasNumber = password.match(/\d/);
    const hasSpecial = password.match(/[!@#$%^&*(),.?":{}|<>]/);
    
    // Update requirements
    reqLength.className = `flex items-center ${hasLength ? 'text-green-600' : 'text-gray-400'}`;
    reqLength.innerHTML = `<i class="fas fa-${hasLength ? 'check' : 'circle'} text-xs mr-2"></i>ความยาวอย่างน้อย 6 ตัวอักษร`;
    
    reqComplexity.className = `flex items-center ${hasUpperLower && hasNumber ? 'text-green-600' : 'text-gray-400'}`;
    reqComplexity.innerHTML = `<i class="fas fa-${hasUpperLower && hasNumber ? 'check' : 'circle'} text-xs mr-2"></i>ควรประกอบด้วยตัวอักษรและตัวเลข`;
    
    reqSpecial.className = `flex items-center ${hasSpecial ? 'text-green-600' : 'text-gray-400'}`;
    reqSpecial.innerHTML = `<i class="fas fa-${hasSpecial ? 'check' : 'circle'} text-xs mr-2"></i>ควรมีอักขระพิเศษ (!@#$%^&*)`;
    
    // Calculate strength
    if (hasLength) strength++;
    if (hasUpperLower) strength++;
    if (hasNumber) strength++;
    if (hasSpecial) strength++;
    
    switch(strength) {
        case 0:
        case 1:
            feedback = '<i class="fas fa-times-circle mr-1"></i>รหัสผ่านอ่อนมาก';
            color = 'text-red-600';
            break;
        case 2:
            feedback = '<i class="fas fa-exclamation-triangle mr-1"></i>รหัสผ่านปานกลาง';
            color = 'text-orange-600';
            break;
        case 3:
            feedback = '<i class="fas fa-check-circle mr-1"></i>รหัสผ่านแข็งแรง';
            color = 'text-blue-600';
            break;
        case 4:
            feedback = '<i class="fas fa-shield-alt mr-1"></i>รหัสผ่านแข็งแรงมาก';
            color = 'text-green-600';
            break;
    }
    
    indicator.innerHTML = `<span class="${color} font-medium">${feedback}</span>`;
});

// Confirm password match
document.getElementById('confirm_password').addEventListener('input', function(e) {
    const confirmPassword = e.target.value;
    const password = document.getElementById('new_password').value;
    const indicator = document.getElementById('password-match');
    
    if (confirmPassword === '') {
        indicator.innerHTML = '';
    } else if (confirmPassword === password) {
        indicator.innerHTML = '<span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>รหัสผ่านตรงกัน</span>';
    } else {
        indicator.innerHTML = '<span class="text-red-600 font-medium"><i class="fas fa-times-circle mr-1"></i>รหัสผ่านไม่ตรงกัน</span>';
    }
});

// Current password validation
document.getElementById('current_password').addEventListener('blur', function(e) {
    const indicator = document.getElementById('current-password-feedback');
    if (e.target.value.length > 0 && e.target.value.length < 6) {
        indicator.innerHTML = '<span class="text-red-600">รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร</span>';
    } else {
        indicator.innerHTML = '';
    }
});

// Form validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const currentPassword = document.getElementById('current_password').value;
    
    // If new password is provided, validate
    if (newPassword) {
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('รหัสผ่านใหม่ไม่ตรงกัน');
            return false;
        }
        
        if (!currentPassword) {
            e.preventDefault();
            alert('กรุณากรอกรหัสผ่านปัจจุบันเพื่อเปลี่ยนรหัสผ่าน');
            return false;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>