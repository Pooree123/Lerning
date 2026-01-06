<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?><?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) { header("Location: login.php"); exit(); }

// --- ส่วนบันทึกข้อมูล (Create) ---
if (isset($_POST['add_product'])) {
    $name = $_POST['p_name'];
    $price = $_POST['p_price'];
    $cat = $_POST['cat_id'];
    $detail = $_POST['p_detail'];
    
    // การจัดการรูปภาพ
    $file = $_FILES['p_image'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION); // นามสกุลไฟล์
    $new_file_name = uniqid('img_', true) . "." . $file_extension; // ตั้งชื่อใหม่ป้องกันชื่อซ้ำ
    $upload_path = "uploads/" . $new_file_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $stmt = $pdo->prepare("INSERT INTO products (p_name, p_price, cat_id, p_detail, p_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $cat, $detail, $new_file_name]);
        header("Location: product_manage.php?success=1");
        exit();
    }
}

// ดึงข้อมูลสินค้าทั้งหมดมาแสดง
$products = $pdo->query("SELECT p.*, c.cat_name FROM products p LEFT JOIN categories c ON p.cat_id = c.cat_id ORDER BY p.p_id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>