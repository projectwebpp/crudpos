<?php
// clear_sale_session.php
session_start();

// ลบ session ข้อมูลการขาย
unset($_SESSION['last_sale_id']);
unset($_SESSION['last_sale_amount']);
unset($_SESSION['last_sale_items']);

echo json_encode(['status' => 'success']);
?>