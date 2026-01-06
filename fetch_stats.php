<?php
require 'config.php';

$type = $_GET['type'] ?? 'custom';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$labels = [];
$values = [];

if ($type == 'week') {
    // 7 วันล่าสุด
    $query = $pdo->query("SELECT DATE(order_date) as date, SUM(total_amount) as total 
                          FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                          GROUP BY DATE(order_date) ORDER BY date ASC")->fetchAll();
    foreach($query as $r) { 
        $labels[] = date('D', strtotime($r['date'])); 
        $values[] = (float)$r['total']; 
    }
} else {
    if ($month == 'all') {
        // ดูทั้งปี (แสดงรายเดือน 1-12)
        $stmt = $pdo->prepare("SELECT MONTH(order_date) as m, SUM(total_amount) as total 
                               FROM orders WHERE YEAR(order_date) = ?
                               GROUP BY MONTH(order_date) ORDER BY m ASC");
        $stmt->execute([$year]);
        $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $months_name = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $months_name[$i-1];
            $values[] = isset($data[$i]) ? (float)$data[$i] : 0;
        }
    } else {
        // ดูรายวันของเดือนและปีที่เลือก
        $stmt = $pdo->prepare("SELECT DAY(order_date) as d, SUM(total_amount) as total 
                               FROM orders WHERE YEAR(order_date) = ? AND MONTH(order_date) = ?
                               GROUP BY DATE(order_date) ORDER BY d ASC");
        $stmt->execute([$year, $month]);
        $query = $stmt->fetchAll();
        
        foreach($query as $r) { 
            $labels[] = "วันที่ " . $r['d']; 
            $values[] = (float)$r['total']; 
        }
        
        // ถ้าไม่มีข้อมูลเลยในเดือนนั้น ให้ส่งค่าว่างไปเพื่อให้กราฟไม่ค้าง
        if(empty($query)) { $labels = ["ไม่มีข้อมูล"]; $values = [0]; }
    }
}

header('Content-Type: application/json');
echo json_encode(['labels' => $labels, 'values' => $values]);