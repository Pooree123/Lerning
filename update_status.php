<?php
require 'config.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$status = isset($_GET['status']) ? intval($_GET['status']) : 0;

if ($id !== '') {
    try {
        // หากสถานะเป็น -1 เราอาจจะลบออกจากตารางเลย หรือแค่เปลี่ยนสถานะเป็น -1 เพื่อเก็บประวัติก็ได้
        // ในที่นี้แนะนำให้เปลี่ยนสถานะเป็น -1 เพื่อเก็บประวัติการยกเลิกครับ
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $result = $stmt->execute([$status, $id]);

        if ($result) {
            echo "success";
        } else {
            echo "error";
        }
    } catch (PDOException $e) {
        echo "db_error: " . $e->getMessage();
    }
}
?>