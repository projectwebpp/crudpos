<?php
require_once '../config/database.php';

// ล้าง session ทั้งหมด
session_unset();
session_destroy();

// รีไดเร็กต์ไปหน้า login
redirect('login.php');
?>