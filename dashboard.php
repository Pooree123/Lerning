<?php
session_start();
require 'config.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// 1. สรุปยอดขาย (สไตล์ Minimal Card)
$today_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = CURDATE()")->fetchColumn() ?: 0;
$month_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())")->fetchColumn() ?: 0;
$year_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE YEAR(order_date) = YEAR(CURDATE())")->fetchColumn() ?: 0;

// 2. ข้อมูลสินค้าขายดี (Doughnut Chart)
$pie_query = $pdo->query("SELECT p.p_name, SUM(od.qty) as total_qty 
                          FROM order_details od 
                          JOIN products p ON od.product_id = p.p_id 
                          GROUP BY od.product_id 
                          ORDER BY total_qty DESC LIMIT 5")->fetchAll();
$p_names = []; $p_qtys = [];
foreach($pie_query as $row) {
    $p_names[] = $row['p_name'];
    $p_qtys[] = $row['total_qty'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimal Dashboard - Cafe Statistics</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #fdfaf7; color: #5a5a5a; overflow-x: hidden; }
        
        /* การจัดการ Layout สำหรับ Sidebar */
        .main-content { 
            margin-left: 260px; /* สำหรับจอคอม */
            padding: 30px; 
            transition: 0.3s; 
        }

        @media (max-width: 991px) { 
            .main-content { 
                margin-left: 0; /* จอเล็กไม่ต้องมี margin ซ้าย */
                padding: 20px; 
            } 
        }

        .stat-card { border: none; border-radius: 25px; background: white; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .stat-card h6 { color: #a1a1a1; font-weight: 400; font-size: 0.9rem; }
        .stat-card h2 { color: #4a4a4a; font-weight: 600; margin-top: 5px; }
        .chart-box { background: white; border-radius: 30px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .filter-select { border-radius: 12px; border: 1px solid #eee; padding: 6px 12px; color: #666; font-size: 0.85rem; outline: none; background: white; cursor: pointer; }
        .filter-btn { border-radius: 12px; border: 1px solid #eee; background: white; color: #888; padding: 6px 15px; font-size: 0.85rem; transition: 0.3s; }
        .filter-btn.active { background: #4a4a4a; color: white; border-color: #4a4a4a; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <h3 class="fw-bold mb-5" style="color: #333;">📈 สถิติยอดขาย</h3>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card"><h6>ยอดขายวันนี้</h6><h2>฿<?= number_format($today_sales, 2) ?></h2></div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card"><h6>เดือนนี้</h6><h2>฿<?= number_format($month_sales, 2) ?></h2></div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card"><h6>ยอดรวมรายปี</h6><h2>฿<?= number_format($year_sales, 2) ?></h2></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="chart-box">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                            <h5 class="fw-bold m-0">แนวโน้มยอดขาย</h5>
                            <div class="d-flex align-items-center gap-2">
                                <select id="yearSelect" class="filter-select" onchange="updateMainChart('custom')">
                                    <?php 
                                    $currentYear = date('Y');
                                    for($i=$currentYear; $i>=$currentYear-5; $i--): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select id="monthSelect" class="filter-select" onchange="updateMainChart('custom')">
                                    <option value="all">ทั้งปี (รายเดือน)</option>
                                    <?php 
                                    $months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                                    foreach($months as $index => $m): ?>
                                        <option value="<?= $index + 1 ?>" <?= ($index+1 == date('m')) ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="filter-btn active" id="btn7day" onclick="updateMainChart('week')">7 วันล่าสุด</button>
                            </div>
                        </div>
                        <canvas id="mainChart" style="max-height: 350px;"></canvas>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="chart-box text-center">
                        <h5 class="fw-bold mb-4">5 อันดับสินค้าขายดี</h5>
                        <canvas id="pieChart" style="max-height: 320px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let mainChart;
        const mainCtx = document.getElementById('mainChart').getContext('2d');
        const colors = { primary: '#b59d85', pie: ['#c7b198', '#dfd3c3', '#f0ece3', '#a29b93', '#8d7b68'] };

        async function updateMainChart(type) {
            const year = document.getElementById('yearSelect').value;
            const month = document.getElementById('monthSelect').value;
            const btn7 = document.getElementById('btn7day');

            let url = `fetch_stats.php?type=${type}&year=${year}&month=${month}`;
            
            if(type === 'week') {
                btn7.classList.add('active');
            } else {
                btn7.classList.remove('active');
            }

            const res = await fetch(url);
            const data = await res.json();

            if (mainChart) mainChart.destroy();
            mainChart = new Chart(mainCtx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'ยอดขาย',
                        data: data.values,
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(181, 157, 133, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: colors.primary,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f8f8f8' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });
        }

        // กราฟวงกลมสินค้าขายดี
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($p_names) ?>,
                datasets: [{
                    data: <?= json_encode($p_qtys) ?>,
                    backgroundColor: colors.pie,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 20 } } }
            }
        });

        window.onload = () => updateMainChart('custom');
    </script>
</body>
</html>