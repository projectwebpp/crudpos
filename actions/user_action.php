<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// อนุญาตเฉพาะ admin
checkRole(['admin']);

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Validation
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            setMessage('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            redirect('../users.php');
        }
        
        if (strlen($password) < 6) {
            setMessage('error', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
            redirect('../users.php');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage('error', 'รูปแบบอีเมลไม่ถูกต้อง');
            redirect('../users.php');
        }
        
        // ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            setMessage('error', 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว');
            redirect('../users.php');
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $username, $email, $phone, $hashed_password, $role])) {
            setMessage('success', 'เพิ่มผู้ใช้งานสำเร็จ!');
        } else {
            setMessage('error', 'เกิดข้อผิดพลาดในการเพิ่มผู้ใช้งาน');
        }
    }
    elseif ($action == 'update') {
        $id = intval($_POST['id']);
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Validation
        if (empty($full_name) || empty($username) || empty($email)) {
            setMessage('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            redirect('../users.php');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage('error', 'รูปแบบอีเมลไม่ถูกต้อง');
            redirect('../users.php');
        }
        
        // ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่ (ยกเว้นตัวเอง)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        
        if ($stmt->rowCount() > 0) {
            setMessage('error', 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว');
            redirect('../users.php');
        }
        
        if (!empty($password)) {
            if (strlen($password) < 6) {
                setMessage('error', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                redirect('../users.php');
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, password = ?, role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $phone, $hashed_password, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $phone, $role, $id]);
        }
        
        if ($stmt->rowCount() > 0) {
            setMessage('success', 'อัพเดทผู้ใช้งานสำเร็จ!');
        } else {
            setMessage('error', 'เกิดข้อผิดพลาดในการอัพเดทผู้ใช้งาน');
        }
    }
}
elseif ($action == 'delete') {
    $id = intval($_GET['id']);
    
    // ตรวจสอบว่าไม่ใช่บัญชีตัวเอง
    if ($id == $_SESSION['user_id']) {
        setMessage('error', 'คุณไม่สามารถลบบัญชีของตัวเองได้');
        redirect('../users.php');
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        setMessage('success', 'ลบผู้ใช้งานสำเร็จ!');
    } else {
        setMessage('error', 'เกิดข้อผิดพลาดในการลบผู้ใช้งาน');
    }
}

redirect('../users.php');
?>