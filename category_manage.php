<?php
session_start();
require 'config.php';

// ฟังก์ชันเพิ่มหมวดหมู่
if (isset($_POST['add_cat'])) {
    $name = $_POST['cat_name'];
    $stmt = $pdo->prepare("INSERT INTO categories (cat_name) VALUES (?)");
    $stmt->execute([$name]);
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>