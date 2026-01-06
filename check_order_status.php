<?php
session_start();
require 'config.php';

// รับรายการ Order ID ทั้งหมดที่ลูกค้าสั่งไว้จาก Session
$order_ids = isset($_SESSION['customer_orders']) ? $_SESSION['customer_orders'] : [];
$results = [];

if (!empty($order_ids)) {
    foreach ($order_ids as $key => $id) {
        // ดึงสถานะและวันที่สั่งซื้อจากฐานข้อมูล
        $stmt = $pdo->prepare("SELECT order_status, order_date FROM orders WHERE order_id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $current_status = $order['order_status'];

            // ถ้าพนักงานกดชำระเงินแล้ว (status 3) หรือ ยกเลิกออเดอร์ (status -1) 
            // ให้ลบออกจากรายการติดตามใน Session ของลูกค้าทันที
            if ($current_status == 3 || $current_status == -1) {
                unset($_SESSION['customer_orders'][$key]);
                continue;
            }

            // นับคิวที่สั่งก่อนหน้า โดยนับเฉพาะออเดอร์ที่ยังทำไม่เสร็จ (status < 2) 
            // และสั่งก่อนเวลาออเดอร์นี้
            $stmt_q = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status < 2 AND order_date < ? AND order_id != ? AND order_status >= 0");
            $stmt_q->execute([$order['order_date'], $id]);
            $queue_before = $stmt_q->fetchColumn();

            // ส่งข้อมูลกลับไปแสดงผลที่หน้าจอ
            $results[] = [
                'order_id' => $id,
                'status' => $current_status,
                'queue_before' => $queue_before
            ];
        } else {
            // ถ้าไม่พบออเดอร์ในระบบ (เช่น ข้อมูลถูกลบ) ให้ลบออกจาก Session
            unset($_SESSION['customer_orders'][$key]);
        }
    }
    // จัดเรียงลำดับลำดับ Index ของ Session ใหม่หลังจากมีการลบข้อมูลออก
    $_SESSION['customer_orders'] = array_values($_SESSION['customer_orders']);
}

// ส่งผลลัพธ์กลับในรูปแบบ JSON เพื่อให้ JavaScript นำไปวนลูปสร้างแถบสถานะ
echo json_encode(['orders' => $results]);