<?php
// 1. จัดการ Session ให้ปลอดภัย
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. ตั้งค่า Header ให้เป็น JSON
header('Content-Type: application/json');

// 3. ดึงไฟล์ Config (ซึ่งต้องมี date_default_timezone_set('Asia/Bangkok') อยู่ข้างใน)
require 'config.php';

// รับข้อมูล JSON จากการ Fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลสินค้า']);
    exit();
}

// สร้างตัวแปรเวลาปัจจุบันของไทยไว้ใช้แทน NOW()
$current_datetime = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    // เช็คว่าเป็นการอัปเดตออเดอร์เดิมจากลูกค้าหรือไม่
    $existing_order_id = isset($data['order_id']) ? $data['order_id'] : '';

    if (!empty($existing_order_id)) {
        // --- กรณีที่ 1: พนักงานปิดยอดชำระเงินออเดอร์เดิมของลูกค้า ---
        $order_id = $existing_order_id;
        $order_status = 3; // ปรับสถานะเป็นชำระเงินเรียบร้อย
        $staff_id = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 0;

        $stmt = $pdo->prepare("UPDATE orders SET total_amount = ?, staff_id = ?, order_status = ? WHERE order_id = ?");
        $stmt->execute([$data['total'], $staff_id, $order_status, $order_id]);

        // ลบรายการเก่าทิ้งก่อน เพื่อบันท
        $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$order_id]);

    } else {
        // --- กรณีที่ 2: สร้างออเดอร์ใหม่ (วันที่ prefix จะตรงกับไทยทันทีเพราะ config.php) ---
        $date_prefix = date('y') . date('m') . date('d');

        $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id LIKE ? ORDER BY order_id DESC LIMIT 1");
        $stmt->execute([$date_prefix . '%']);
        $last_order = $stmt->fetchColumn();

        if ($last_order) {
            $last_number = intval(substr($last_order, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        $order_id = $date_prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
        
        // กำหนดสถานะ: 0 = ลูกค้าสั่งเอง, 3 = พนักงานขายหน้าร้าน
        $order_status = isset($data['order_status']) ? intval($data['order_status']) : (isset($_SESSION['staff_id']) ? 3 : 0);
        $staff_id = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : 0;

        // แก้ไข: เปลี่ยนจาก NOW() เป็น ? และส่ง $current_datetime เข้าไปแทน
        $stmt = $pdo->prepare("INSERT INTO orders (order_id, total_amount, staff_id, order_date, order_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $data['total'], $staff_id, $current_datetime, $order_status]);
    }

    // บันทึกรายการสินค้าลงตารางรายละเอียด
    $stmt_detail = $pdo->prepare("INSERT INTO order_details (order_id, product_id, qty, price_at_sale) VALUES (?, ?, ?, ?)");
    foreach ($data['cart'] as $item) {
        $stmt_detail->execute([
            $order_id,
            $item['id'],
            $item['qty'],
            $item['price']
        ]);
    }

    $pdo->commit();

    // หากเป็นการสั่งจากลูกค้า ให้จัดการ Session Tracking
    if ($order_status == 0) {
        if (!isset($_SESSION['customer_orders']) || !is_array($_SESSION['customer_orders'])) {
            $_SESSION['customer_orders'] = [];
        }
        if (!in_array($order_id, $_SESSION['customer_orders'])) {
            $_SESSION['customer_orders'][] = $order_id;
        }
        $_SESSION['customer_last_order'] = $order_id; 
    }

    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>