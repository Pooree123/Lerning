<?php
require 'config.php';

// ตรวจสอบว่าส่ง id มาไหม
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id > 0) {
    try {
        // ใช้ JOIN เพื่อดึงชื่อสินค้ามาโชว์ด้วย
        $stmt = $pdo->prepare("SELECT od.*, p.p_name 
                               FROM order_details od 
                               LEFT JOIN products p ON od.product_id = p.p_id 
                               WHERE od.order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($items);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}