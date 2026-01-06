<?php
require 'config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// ดึงข้อมูลหลักของ Order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) { die("ไม่พบข้อมูลใบเสร็จ"); }

// ดึงรายการสินค้า
$stmt_items = $pdo->prepare("SELECT od.*, p.p_name FROM order_details od 
                             JOIN products p ON od.product_id = p.p_id 
                             WHERE od.order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $order_id ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400&display=swap');
        body { font-family: 'Kanit', sans-serif; font-size: 14px; color: #333; }
        .receipt-box { width: 80mm; margin: auto; padding: 10px; border: 1px solid #eee; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-bottom: 1px dashed #ccc; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-weight: 600; border-bottom: 1px solid #eee; padding: 5px 0; }
        td { padding: 5px 0; vertical-align: top; }
        .total-row { font-size: 18px; font-weight: bold; }
        
        /* สั่งพิมพ์: ซ่อนปุ่มต่างๆ */
        @media print {
            .no-print { display: none; }
            .receipt-box { border: none; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor:pointer; background:#8d7b68; color:white; border:none; border-radius:5px;">
            🖨️ พิมพ์ใบเสร็จ / บันทึกเป็น PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor:pointer;">ปิดหน้านี้</button>
    </div>

    <div class="receipt-box">
        <div class="text-center">
            <h2 style="margin:0;">MINIMAL CAFE</h2>
            <p style="margin:5px 0;">ขอบคุณที่ใช้บริการ</p>
            <small>ใบเสร็จรับเงินอย่างย่อ</small>
        </div>

        <div class="line"></div>
        
        <p><strong>เลขที่:</strong> #<?= str_pad($order['order_id'], 5, "0", STR_PAD_LEFT) ?></p>
        <p><strong>วันที่:</strong> <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="text-right">จำนวน</th>
                    <th class="text-right">รวม</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?= $item['p_name'] ?></td>
                    <td class="text-right"><?= $item['qty'] ?></td>
                    <td class="text-right"><?= number_format($item['qty'] * $item['price_at_sale'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="line"></div>

        <table class="total-row">
            <tr>
                <td>ยอดรวมสุทธิ</td>
                <td class="text-right">฿<?= number_format($order['total_amount'], 2) ?></td>
            </tr>
        </table>

        <div class="line"></div>
        <p class="text-center" style="font-size: 12px;">Power by Cafe System</p>
    </div>

    <script>
        // สั่งพิมพ์ทันทีที่เปิดหน้า (ถ้าต้องการ)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>